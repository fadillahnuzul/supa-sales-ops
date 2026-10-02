<?php

namespace App\Http\Controllers;

use App\Models\Core\ProductModel;
use App\Models\Sales\CompetitorModel;
use App\Models\Sales\CompetitorProductModel;
use App\Support\SpreadsheetFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompetitorController extends Controller
{
    public function template(): StreamedResponse
    {
        $sheet = SpreadsheetFile::buildXlsx([
            [
                'competitor',
                'division',
                'competitor_note',
                'product_code',
                'price',
                'date',
                'product_note',
            ],
        ], 'Competitor Import');

        return response()->streamDownload(function () use ($sheet): void {
            echo $sheet;
        }, 'competitor-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,xlsx',
                'extensions:csv,xlsx',
                'max:10240',
            ],
        ]);

        try {
            $rows = SpreadsheetFile::readRows($request->file('file'));
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors([
                'file' => $exception->getMessage(),
            ]);
        }

        if ($rows === []) {
            return back()->withErrors([
                'file' => 'File Excel/CSV kosong atau tidak terbaca.',
            ]);
        }

        $mappedRows = $this->normalizeImportRows($rows);

        if ($mappedRows === []) {
            return back()->withErrors([
                'file' => 'Kolom file tidak sesuai. Gunakan template dengan atribut: competitor, division, competitor_note, product_code, price, date, product_note.',
            ]);
        }

        $preparedRows = [];
        $productCodes = [];
        $competitorGroups = [];
        $seenProducts = [];

        foreach ($mappedRows as $index => $row) {
            $line = $index + 2;
            $validator = Validator::make($row, [
                'competitor' => ['required', 'string', 'max:255'],
                'division' => [
                    'required',
                    Rule::in(['Industri', 'SME', 'Low Cost', 'All']),
                ],
                'competitor_note' => ['nullable', 'string'],
                'product_code' => ['required', 'string', 'max:50'],
                'price' => ['required', 'numeric', 'min:0'],
                'date' => ['required', 'date'],
                'product_note' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return back()->withErrors([
                    'file' => sprintf('Baris %d: %s', $line, $validator->errors()->first()),
                ]);
            }

            $validated = $validator->validated();
            $validated['competitor'] = trim($validated['competitor']);
            $validated['competitor_note'] = trim($validated['competitor_note'] ?? '');
            $validated['product_code'] = trim($validated['product_code']);
            $validated['product_note'] = trim($validated['product_note'] ?? '');

            $groupKey = mb_strtolower(implode('|', [
                $validated['competitor'],
                $validated['division'],
                $validated['competitor_note'],
            ]));
            $productCodeKey = mb_strtolower($validated['product_code']);
            $duplicateKey = $groupKey.'|'.$productCodeKey;

            if (isset($seenProducts[$duplicateKey])) {
                return back()->withErrors([
                    'file' => sprintf(
                        'Baris %d: produk dengan kode "%s" tercatat lebih dari satu kali untuk competitor ini.',
                        $line,
                        $validated['product_code']
                    ),
                ]);
            }

            $seenProducts[$duplicateKey] = $line;
            $productCodes[$productCodeKey] = $validated['product_code'];
            $competitorGroups[$groupKey] = [
                'name' => $validated['competitor'],
                'division' => $validated['division'],
                'note' => $validated['competitor_note'] ?: null,
            ];
            $validated['group_key'] = $groupKey;
            $validated['line'] = $line;
            $preparedRows[] = $validated;
        }

        $productsByCode = [];

        foreach (
            ProductModel::query()
                ->whereIn(DB::raw('LOWER(code)'), array_keys($productCodes))
                ->get(['id', 'code']) as $product
        ) {
            $productsByCode[mb_strtolower(trim($product->code))][] = $product;
        }

        foreach ($preparedRows as $row) {
            $matches = $productsByCode[mb_strtolower($row['product_code'])] ?? [];

            if (count($matches) !== 1) {
                $reason = $matches === []
                    ? sprintf('Kode produk "%s" tidak ditemukan.', $row['product_code'])
                    : sprintf('Kode produk "%s" tidak unik.', $row['product_code']);

                return back()->withErrors([
                    'file' => sprintf('Baris %d: %s', $row['line'], $reason),
                ]);
            }
        }

        $existingCompetitors = CompetitorModel::query()
            ->orderBy('id')
            ->get(['id', 'name', 'divisi', 'note']);
        $competitorIds = [];

        foreach ($competitorGroups as $groupKey => $group) {
            $existingCompetitor = $existingCompetitors->first(
                fn (CompetitorModel $competitor): bool => mb_strtolower(trim($competitor->name)) === mb_strtolower($group['name'])
                    && $competitor->divisi === $group['division']
                    && trim($competitor->note ?? '') === ($group['note'] ?? '')
            );

            $competitorIds[$groupKey] = $existingCompetitor?->getKey();
        }

        $existingProductPairs = [];

        foreach ($preparedRows as $row) {
            $competitorId = $competitorIds[$row['group_key']];

            if ($competitorId === null) {
                continue;
            }

            $productId = $productsByCode[mb_strtolower($row['product_code'])][0]->getKey();
            $existingProductPairs[$competitorId][$productId] = $row['line'];
        }

        foreach ($existingProductPairs as $competitorId => $productLines) {
            $existingProductIds = CompetitorProductModel::query()
                ->where('competitor_id', $competitorId)
                ->whereIn('product_id', array_keys($productLines))
                ->pluck('product_id')
                ->all();

            if ($existingProductIds !== []) {
                $productId = (int) $existingProductIds[0];

                return back()->withErrors([
                    'file' => sprintf(
                        'Baris %d: produk sudah memiliki data harga untuk competitor tersebut.',
                        $productLines[$productId]
                    ),
                ]);
            }
        }

        DB::transaction(function () use (
            $preparedRows,
            $competitorGroups,
            &$competitorIds,
            $productsByCode
        ): void {
            foreach ($competitorGroups as $groupKey => $group) {
                if ($competitorIds[$groupKey] !== null) {
                    continue;
                }

                $competitorIds[$groupKey] = CompetitorModel::create([
                    'name' => $group['name'],
                    'divisi' => $group['division'],
                    'note' => $group['note'],
                ])->getKey();
            }

            foreach ($preparedRows as $row) {
                $product = $productsByCode[mb_strtolower($row['product_code'])][0];

                CompetitorProductModel::create([
                    'competitor_id' => $competitorIds[$row['group_key']],
                    'product_id' => $product->getKey(),
                    'price' => $row['price'],
                    'date' => $row['date'],
                    'note' => $row['product_note'] ?: null,
                ]);
            }
        });

        return back()->with(
            'success',
            sprintf('%d data harga competitor berhasil diimport.', count($preparedRows))
        );
    }

    private function normalizeImportRows(array $rows): array
    {
        $header = array_shift($rows);

        if ($header === null) {
            return [];
        }

        $aliases = [
            'competitor' => ['competitor', 'competitor name', 'nama competitor', 'nama kompetitor'],
            'division' => ['division', 'divisi'],
            'competitor_note' => ['competitor note', 'keterangan competitor', 'catatan competitor'],
            'product_code' => ['product code', 'item code', 'kode produk', 'kode barang'],
            'price' => ['price', 'competitor price', 'harga', 'harga competitor', 'harga kompetitor'],
            'date' => ['date', 'tanggal', 'recorded date', 'tanggal harga'],
            'product_note' => ['product note', 'notes', 'note', 'keterangan produk', 'catatan produk'],
        ];
        $columns = [];

        foreach ($header as $index => $value) {
            $headerValue = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
            $key = mb_strtolower(trim(preg_replace('/[^a-z0-9]+/i', ' ', $headerValue)));

            foreach ($aliases as $field => $options) {
                if (in_array($key, $options, true)) {
                    $columns[$field] = $index;
                    break;
                }
            }
        }

        if (array_diff(
            ['competitor', 'division', 'product_code', 'price', 'date'],
            array_keys($columns)
        ) !== []) {
            return [];
        }

        $mappedRows = [];

        foreach ($rows as $row) {
            $record = [];

            foreach ($columns as $field => $columnIndex) {
                $record[$field] = trim((string) ($row[$columnIndex] ?? ''));
            }

            if (count(array_filter($record, fn (string $value): bool => $value !== '')) === 0) {
                continue;
            }

            $record['competitor_note'] ??= '';
            $record['product_note'] ??= '';
            $mappedRows[] = $record;
        }

        return $mappedRows;
    }

    public function store(Request $request): RedirectResponse
    {
        $productTable = config('database.default').'.'.(new ProductModel)->getTable();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'divisi' => [
                'required',
                Rule::in([
                    'Industri',
                    'SME',
                    'Low Cost',
                    'All',
                ]),
            ],

            'note' => [
                'nullable',
                'string',
            ],

            'product_id' => [
                'required',
                'integer',
                Rule::exists($productTable, 'id'),
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'date' => [
                'required',
                'date',
            ],

            'product_note' => [
                'nullable',
                'string',
            ],
        ]);

        DB::transaction(function () use ($validated) {
            $competitor = CompetitorModel::create([
                'name' => $validated['name'],
                'divisi' => $validated['divisi'],
                'note' => $validated['note'] ?? null,
            ]);

            CompetitorProductModel::create([
                'competitor_id' => $competitor->id,
                'product_id' => $validated['product_id'],
                'price' => $validated['price'],
                'date' => $validated['date'],
                'note' => $validated['product_note'] ?? null,
            ]);
        });

        return back()->with(
            'success',
            'Competitor berhasil ditambahkan.'
        );
    }

    public function update(
        Request $request,
        CompetitorProductModel $competitorProduct
    ): RedirectResponse {
        $connection = config('database.default');
        $productTable = $connection.'.'.(new ProductModel)->getTable();
        $competitorProductTable = $connection.'.'.(new CompetitorProductModel)->getTable();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'divisi' => [
                'required',
                Rule::in([
                    'Industri',
                    'SME',
                    'Low Cost',
                    'All',
                ]),
            ],

            'note' => [
                'nullable',
                'string',
            ],

            'product_id' => [
                'required',
                'integer',
                Rule::exists($productTable, 'id'),
                Rule::unique(
                    $competitorProductTable,
                    'product_id'
                )
                    ->where(
                        'competitor_id',
                        $competitorProduct->competitor_id
                    )
                    ->ignore($competitorProduct->getKey()),
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'date' => [
                'required',
                'date',
            ],

            'product_note' => [
                'nullable',
                'string',
            ],
        ]);

        DB::transaction(
            function () use (
                $validated,
                $competitorProduct
            ) {
                $competitor = $competitorProduct->competitor;

                $competitor->update([
                    'name' => $validated['name'],
                    'divisi' => $validated['divisi'],
                    'note' => $validated['note'] ?? null,
                ]);

                $competitorProduct->update([
                    'product_id' => $validated['product_id'],
                    'price' => $validated['price'],
                    'note' => $validated['product_note'] ?? null,
                    'date' => $validated['date'],
                ]);
            }
        );

        return back()->with(
            'success',
            'Competitor berhasil diperbarui.'
        );
    }

    public function destroy(CompetitorModel $competitor): RedirectResponse
    {
        DB::transaction(function () use ($competitor) {
            $competitor->products()->delete();

            $competitor->delete();
        });

        return back()->with(
            'success',
            'Competitor berhasil dihapus.'
        );
    }

    public function destroyProduct(
        CompetitorProductModel $competitorProduct
    ): RedirectResponse {
        $competitorProduct->delete();

        return back()->with(
            'success',
            'Data harga competitor berhasil dihapus.'
        );
    }
}
