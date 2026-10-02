<?php

namespace App\Http\Controllers;

use App\Models\Core\GradeModel;
use App\Models\Core\MaterialModel;
use App\Models\Core\ProductMaterialModel;
use App\Models\Core\ProductModel;
use App\Support\SpreadsheetFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function store(
        Request $request
    ): RedirectResponse {
        $validated =
            $this->validateProduct(
                $request
            );

        DB::transaction(
            function () use (
                $validated
            ) {
                $product =
                    ProductModel::create([
                        'name' => $validated['name'],

                        'code' => $validated['code'],

                        'std_price' => $validated['std_price'],

                        'grade_id' => $validated['grade_id']
                            ?? null,
                    ]);

                $this->syncMaterials(
                    $product,
                    $validated['materials']
                    ?? []
                );
            }
        );

        return back()->with(
            'success',
            'Product berhasil ditambahkan.'
        );
    }

    public function update(
        Request $request,
        ProductModel $product
    ): RedirectResponse {
        $validated =
            $this->validateProduct(
                $request,
                $product
            );

        DB::transaction(
            function () use (
                $validated,
                $product
            ) {
                $product->update([
                    'name' => $validated['name'],

                    'code' => $validated['code'],

                    'std_price' => $validated['std_price'],

                    'grade_id' => $validated['grade_id']
                        ?? null,
                ]);

                $this->syncMaterials(
                    $product,
                    $validated['materials']
                    ?? []
                );
            }
        );

        return back()->with(
            'success',
            'Product berhasil diperbarui.'
        );
    }

    public function destroy(
        ProductModel $product
    ): RedirectResponse {
        /*
         * Product mungkin dipakai
         * sebagai material product lain.
         */
        $productMorphClass =
            $product->getMorphClass();

        $usedAsMaterial =
            ProductMaterialModel::query()
                ->where(
                    'material_type',
                    $productMorphClass
                )
                ->where(
                    'material_id',
                    $product->getKey()
                )
                ->exists();

        if ($usedAsMaterial) {
            return back()->withErrors([
                'product' => 'Product tidak dapat dihapus karena masih digunakan sebagai material product lain.',
            ]);
        }

        DB::transaction(
            function () use (
                $product
            ) {
                /*
                 * Sebenarnya cascade FK juga
                 * akan menghapusnya.
                 */
                $product
                    ->materials()
                    ->delete();

                $product->delete();
            }
        );

        return back()->with(
            'success',
            'Product berhasil dihapus.'
        );
    }

    public function template(): StreamedResponse
    {
        $sheet = SpreadsheetFile::buildXlsx([
            [
                'name',
                'code',
                'std_price',
                'grade',
                'materials',
            ],
            [
                'Racikan Lada Kapulaga',
                'PROD-DEMO-001',
                '125000',
                '',
                'Lada; Kapulaga',
            ],
        ], 'Product Import');

        return response()->streamDownload(function () use ($sheet): void {
            echo $sheet;
        }, 'product-import-template.xlsx', [
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

        $mappedRows = $this->normalizeProductImportRows($rows);

        if ($mappedRows === []) {
            return back()->withErrors([
                'file' => 'Kolom file tidak sesuai. Gunakan template dengan atribut: name, code, std_price, grade, materials.',
            ]);
        }

        $preparedRows = [];
        $seenCodes = [];
        $gradesByName = GradeModel::query()
            ->get(['id', 'name'])
            ->groupBy(fn (GradeModel $grade): string => mb_strtolower(trim($grade->name)));

        foreach ($mappedRows as $index => $row) {
            try {
                $prepared = $this->validateProductImportRow($row, $gradesByName);
                $codeKey = mb_strtolower($prepared['code']);

                if (isset($seenCodes[$codeKey])) {
                    throw new \InvalidArgumentException('Kode produk duplikat di dalam file.');
                }

                $seenCodes[$codeKey] = true;
                $preparedRows[] = $prepared;
            } catch (\InvalidArgumentException $exception) {
                return back()->withErrors([
                    'file' => sprintf('Baris %d: %s', $index + 2, $exception->getMessage()),
                ]);
            }
        }

        $materialNames = [];

        foreach ($preparedRows as $row) {
            foreach ($row['material_names'] as $name) {
                $materialNames[mb_strtolower($name)] = $name;
            }
        }

        $materialSourcesByName = [];

        foreach (MaterialModel::query()->orderBy('id')->get(['id', 'name']) as $material) {
            $key = mb_strtolower(trim($material->name));

            if (isset($materialNames[$key])) {
                $materialSourcesByName[$key][] = [
                    'type' => 'material',
                    'id' => $material->getKey(),
                ];
            }
        }

        $productSourcesByName = [];

        foreach (ProductModel::query()->get(['id', 'name']) as $product) {
            $key = mb_strtolower(trim($product->name));

            if (isset($materialNames[$key])) {
                $productSourcesByName[$key][] = [
                    'type' => 'product',
                    'id' => $product->getKey(),
                ];
            }
        }

        $importedProductsByName = [];

        foreach ($preparedRows as $index => $row) {
            $importedProductsByName[mb_strtolower($row['name'])][] = $index;
        }

        foreach ($preparedRows as $index => &$row) {
            $row['material_sources'] = [];

            foreach ($row['material_names'] as $name) {
                $key = mb_strtolower($name);

                if (isset($materialSourcesByName[$key])) {
                    $row['material_sources'][] = $materialSourcesByName[$key][0];

                    continue;
                }

                $sources = $productSourcesByName[$key] ?? [];

                foreach ($importedProductsByName[$key] ?? [] as $importedIndex) {
                    $sources[] = [
                        'type' => 'imported_product',
                        'index' => $importedIndex,
                    ];
                }

                if (count($sources) !== 1) {
                    $reason = $sources === []
                        ? sprintf('Material/produk "%s" tidak ditemukan.', $name)
                        : sprintf('Nama material/produk "%s" tidak unik.', $name);

                    return back()->withErrors([
                        'file' => sprintf('Baris %d: %s', $index + 2, $reason),
                    ]);
                }

                $row['material_sources'][] = $sources[0];
            }
        }
        unset($row);

        try {
            DB::transaction(function () use ($preparedRows): void {
                $products = [];

                foreach ($preparedRows as $index => $row) {
                    $products[$index] = ProductModel::create([
                        'name' => $row['name'],
                        'code' => $row['code'],
                        'std_price' => $row['std_price'],
                        'grade_id' => $row['grade_id'],
                    ]);
                }

                foreach ($preparedRows as $index => $row) {
                    $sources = [];

                    foreach ($row['material_sources'] as $source) {
                        $sourceId = $source['type'] === 'imported_product'
                            ? $products[$source['index']]->getKey()
                            : $source['id'];

                        $sourceType = $source['type'] === 'imported_product'
                            ? 'product'
                            : $source['type'];

                        $sources[] = $sourceType.':'.$sourceId;
                    }

                    try {
                        $this->syncMaterials($products[$index], $sources);
                    } catch (ValidationException $exception) {
                        $message = collect($exception->errors())->flatten()->first()
                            ?? 'Material produk tidak valid.';

                        throw new \InvalidArgumentException(
                            sprintf('Baris %d: %s', $index + 2, $message),
                            previous: $exception
                        );
                    }
                }
            });
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors([
                'file' => $exception->getMessage(),
            ]);
        }

        return back()->with(
            'success',
            sprintf('%d produk berhasil diimport.', count($preparedRows))
        );
    }

    private function normalizeProductImportRows(array $rows): array
    {
        $header = array_shift($rows);

        if ($header === null) {
            return [];
        }

        $aliases = [
            'name' => ['name', 'product name', 'nama produk'],
            'code' => ['code', 'item code', 'product code', 'kode produk'],
            'std_price' => ['std price', 'standard price', 'harga standar', 'harga'],
            'grade' => ['grade', 'mutu'],
            'materials' => ['materials', 'material', 'bahan baku'],
        ];
        $mappedColumns = [];

        foreach ($header as $index => $value) {
            $key = mb_strtolower(trim(preg_replace('/[^a-z0-9]+/i', ' ', (string) $value)));

            foreach ($aliases as $field => $options) {
                if (in_array($key, $options, true)) {
                    $mappedColumns[$field] = $index;
                    break;
                }
            }
        }

        if (array_diff(['name', 'code', 'std_price'], array_keys($mappedColumns)) !== []) {
            return [];
        }

        $mappedRows = [];

        foreach ($rows as $row) {
            $record = [];

            foreach ($mappedColumns as $field => $columnIndex) {
                $record[$field] = trim((string) ($row[$columnIndex] ?? ''));
            }

            if (count(array_filter($record, fn (string $value): bool => $value !== '')) === 0) {
                continue;
            }

            $mappedRows[] = $record;
        }

        return $mappedRows;
    }

    private function validateProductImportRow(array $row, Collection $gradesByName): array
    {
        $name = trim((string) ($row['name'] ?? ''));
        $code = trim((string) ($row['code'] ?? ''));
        $gradeName = trim((string) ($row['grade'] ?? ''));
        $gradeMatches = $gradeName === ''
            ? collect()
            : ($gradesByName[mb_strtolower($gradeName)] ?? collect());

        if ($gradeName !== '' && $gradeMatches->count() !== 1) {
            throw new \InvalidArgumentException(
                $gradeMatches->isEmpty()
                    ? sprintf('Grade "%s" tidak ditemukan.', $gradeName)
                    : sprintf('Nama grade "%s" tidak unik.', $gradeName)
            );
        }

        $validator = Validator::make([
            'name' => $name,
            'code' => $code,
            'std_price' => trim((string) ($row['std_price'] ?? '')),
            'grade_id' => $gradeMatches->first()?->getKey(),
        ], [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique(
                    config('database.default').'.'.(new ProductModel)->getTable(),
                    'code'
                ),
            ],
            'std_price' => ['required', 'numeric', 'min:0'],
            'grade_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    config('database.default').'.'.(new GradeModel)->getTable(),
                    'id'
                ),
            ],
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        $validated = $validator->validated();
        $materialNames = [];
        $materials = trim((string) ($row['materials'] ?? ''));

        if ($materials !== '') {
            foreach (explode(';', $materials) as $materialName) {
                $materialName = trim($materialName);

                if ($materialName === '') {
                    throw new \InvalidArgumentException(
                        'Kolom materials harus berisi nama yang dipisahkan dengan titik koma (;).'
                    );
                }

                $key = mb_strtolower($materialName);

                if (isset($materialNames[$key])) {
                    throw new \InvalidArgumentException(
                        sprintf('Material "%s" ditulis lebih dari satu kali.', $materialName)
                    );
                }

                $materialNames[$key] = $materialName;
            }
        }

        return [
            'name' => $validated['name'],
            'code' => $validated['code'],
            'std_price' => $validated['std_price'],
            'grade_id' => $validated['grade_id'] ?? null,
            'material_names' => array_values($materialNames),
        ];
    }

    private function validateProduct(
        Request $request,
        ?ProductModel $product = null
    ): array {
        $connection = config('database.default');

        $productTable =
            $connection.'.'.(new ProductModel)
                ->getTable();

        $gradeTable =
            $connection.'.'.(new GradeModel)
                ->getTable();

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',

                Rule::unique(
                    $productTable,
                    'code'
                )->ignore(
                    $product?->getKey()
                ),
            ],

            'std_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'grade_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    $gradeTable,
                    'id'
                ),
            ],

            /*
             * Product bisa punya
             * banyak material.
             */
            'materials' => [
                'nullable',
                'array',
            ],

            'materials.*' => [
                'required',
                'string',
                'distinct',
                'regex:/^(material|product):\d+$/',
            ],
        ]);
    }

    private function syncMaterials(
        ProductModel $product,
        array $sources
    ): void {
        $resolvedSources = [];

        foreach (
            $sources as $source
        ) {
            [
                $sourceType,
                $sourceId,
            ] = explode(
                ':',
                $source,
                2
            );

            $sourceId =
                (int) $sourceId;

            /*
             * =====================
             * MATERIAL MODEL
             * =====================
             */
            if (
                $sourceType ===
                'material'
            ) {
                $material =
                    MaterialModel::query()
                        ->find(
                            $sourceId
                        );

                if (! $material) {
                    throw ValidationException::withMessages([
                        'materials' => "Material {$sourceId} tidak ditemukan.",
                    ]);
                }

                $resolvedSources[] = [
                    'material_id' => $material->getKey(),

                    'material_type' => $material->getMorphClass(),
                ];

                continue;
            }

            /*
             * =====================
             * PRODUCT MODEL
             * =====================
             */
            $sourceProduct =
                ProductModel::query()
                    ->find(
                        $sourceId
                    );

            if (! $sourceProduct) {
                throw ValidationException::withMessages([
                    'materials' => "Product {$sourceId} tidak ditemukan.",
                ]);
            }

            /*
             * Product tidak boleh
             * menggunakan dirinya sendiri.
             */
            if (
                $sourceProduct->is(
                    $product
                )
            ) {
                throw ValidationException::withMessages([
                    'materials' => 'Product tidak dapat menggunakan dirinya sendiri sebagai material.',
                ]);
            }

            /*
             * Cek circular dependency.
             */
            if (
                $this->createsCircularReference(
                    $product->getKey(),
                    $sourceProduct->getKey()
                )
            ) {
                throw ValidationException::withMessages([
                    'materials' => "Product {$sourceProduct->name} menyebabkan circular dependency.",
                ]);
            }

            $resolvedSources[] = [
                'material_id' => $sourceProduct->getKey(),

                'material_type' => $sourceProduct->getMorphClass(),
            ];
        }

        /*
         * Setelah semua valid,
         * hapus material lama.
         */
        $product
            ->materials()
            ->delete();

        /*
         * Insert material baru.
         */
        foreach (
            $resolvedSources as $source
        ) {
            $product
                ->materials()
                ->create(
                    $source
                );
        }
    }

    private function createsCircularReference(
        int $currentProductId,
        int $sourceProductId
    ): bool {
        $visited = [];

        $queue = [
            $sourceProductId,
        ];

        $productMorphClass =
            (new ProductModel)
                ->getMorphClass();

        while (
            count($queue) > 0
        ) {
            $productId =
                array_shift(
                    $queue
                );

            if (
                $productId ===
                $currentProductId
            ) {
                return true;
            }

            if (
                in_array(
                    $productId,
                    $visited,
                    true
                )
            ) {
                continue;
            }

            $visited[] =
                $productId;

            /*
             * Ambil semua ProductModel
             * yang digunakan product ini.
             */
            $childProductIds =
                ProductMaterialModel::query()
                    ->where(
                        'product_id',
                        $productId
                    )
                    ->where(
                        'material_type',
                        $productMorphClass
                    )
                    ->pluck(
                        'material_id'
                    )
                    ->map(
                        fn ($id) => (int) $id
                    )
                    ->all();

            foreach (
                $childProductIds as $childProductId
            ) {
                $queue[] =
                    $childProductId;
            }
        }

        return false;
    }
}
