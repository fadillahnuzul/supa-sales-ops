<?php

namespace App\Http\Controllers;

use App\Models\Core\CustomerModel;
use App\Models\Core\GradeModel;
use App\Models\Core\MaterialModel;
use App\Models\Core\ProductModel;
use App\Models\Core\SegmentationModel;
use App\Models\Sales\CompetitorModel;
use App\Support\SpreadsheetFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        $products =
            ProductModel::query()
                ->with([
                    'grade',
                    'materials.material',
                ])
                ->orderBy('name')
                ->get();

        $materialOptions =
            MaterialModel::query()
                ->orderBy('name')
                ->get()
                ->map(
                    fn ($material) => [
                        'id' => $material->getKey(),

                        'name' => $material->name,

                        'code' => $material->code
                            ?? null,

                        'source_type' => 'material',

                        'source_key' => 'material:'.
                            $material->getKey(),
                    ]
                );

        $productOptions =
            ProductModel::query()
                ->orderBy('name')
                ->get()
                ->map(
                    fn ($product) => [
                        'id' => $product->getKey(),

                        'name' => $product->name,

                        'code' => $product->code,

                        'source_type' => 'product',

                        'source_key' => 'product:'.
                            $product->getKey(),
                    ]
                );

        $materials =
            $materialOptions
                ->concat(
                    $productOptions
                )
                ->values();

        $grades =
            GradeModel::query()
                ->select([
                    'id',
                    'name',
                ])
                ->orderBy('name')
                ->get();
        $customers = CustomerModel::query()
            ->with('segmentation:id,name')
            ->orderBy('name')
            ->get()
            ->map(function (CustomerModel $customer) {
                return [
                    'id' => $customer->id,
                    'customerId' => $customer->id,
                    'company' => $customer->name,
                    'address' => $customer->address,

                    'segmentationId' => $customer->segmentation_id,

                    'segmentation' => $customer->segmentation?->name ?? '-',

                    'level' => $customer->level,

                    'division' => $customer->divisi,

                    'pic' => $customer->pic,
                    'phone' => $customer->phone,

                    'sterilization' => $customer->sterilization,
                ];
            });

        $segmentations = SegmentationModel::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $competitors = CompetitorModel::query()
            ->with([
                'products.product',
            ])
            ->orderBy('name')
            ->get()
            ->flatMap(function ($competitor) {
                return $competitor->products->map(
                    function ($competitorProduct) use ($competitor) {
                        return [
                            'id' => $competitorProduct->id,

                            'competitor_id' => $competitor->id,

                            'competitor' => $competitor->name,

                            'division' => $competitor->divisi,

                            'competitor_note' => $competitor->note,

                            'product_id' => $competitorProduct->product_id,

                            'product' => $competitorProduct->product?->name,

                            'price' => (float) $competitorProduct->price,

                            'date' => $competitorProduct->date?->format('Y-m-d'),

                            'notes' => $competitorProduct->note,
                        ];
                    }
                );
            })
            ->values();

        return Inertia::render(
            'DatabaseCenter',
            [

                'customers' => $customers,
                'products' => $products,
                'competitors' => $competitors,

                'segmentations' => $segmentations,
                'materials' => $materials,
                'grades' => $grades,
            ]
        );
    }

    /**
     * Store new customer.
     */
    public function store(Request $request)
    {
        $validated = $this->validateCustomer(
            $request
        );

        CustomerModel::create($validated);

        return back()->with(
            'success',
            'Customer successfully added.'
        );
    }

    /**
     * Update customer.
     */
    public function update(
        Request $request,
        CustomerModel $customer
    ) {
        $validated = $this->validateCustomer(
            $request
        );

        $customer->update($validated);

        return back()->with(
            'success',
            'Customer successfully updated.'
        );
    }

    /**
     * Delete customer.
     */
    public function destroy(CustomerModel $customer)
    {
        $customer->delete();

        return back()->with(
            'success',
            'Customer successfully deleted.'
        );
    }

    public function template()
    {
        $header = [
            'company',
            'address',
            'segmentation',
            'level',
            'division',
            'sterilization',
            'pic',
            'phone',
        ];

        $rows = [
            $header,
            [
                'PT Maju Jaya',
                'Jl. Merdeka No. 10, Bandung',
                'General',
                'Low',
                'Industri',
                'S',
                'Budi',
                '081234567890',
            ],
        ];

        $sheet = SpreadsheetFile::buildXlsx($rows, 'Customer Import');

        return response()->streamDownload(function () use ($sheet) {
            echo $sheet;
        }, 'customer-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx', 'extensions:csv,xlsx', 'max:10240'],
        ]);

        $file = $request->file('file');
        try {
            $rows = $this->readImportRows($file);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors([
                'file' => $exception->getMessage(),
            ]);
        }

        if (empty($rows)) {
            return back()->withErrors([
                'file' => 'File Excel/CSV kosong atau tidak terbaca.',
            ]);
        }

        $mappedRows = $this->normalizeImportRows($rows);

        if (empty($mappedRows)) {
            return back()->withErrors([
                'file' => 'Kolom file tidak sesuai. Gunakan template dengan atribut: company, address, segmentation, level, division, pic, phone. Sterilization bersifat opsional untuk template lama.',
            ]);
        }

        $validatedRows = [];

        foreach ($mappedRows as $index => $row) {
            try {
                $validatedRows[] = $this->validateImportRecord($row);
            } catch (\InvalidArgumentException $exception) {
                return back()->withErrors([
                    'file' => sprintf('Baris %d: %s', $index + 2, $exception->getMessage()),
                ]);
            }
        }

        DB::transaction(function () use ($validatedRows): void {
            foreach ($validatedRows as $validated) {
                CustomerModel::create($validated);
            }
        });

        return back()->with(
            'success',
            sprintf('%d customer berhasil diimport.', count($validatedRows))
        );
    }

    /**
     * Customer validation.
     */
    private function validateCustomer(
        Request $request
    ): array {
        $validated = $request->validate([
            'name' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'company' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'address' => [
                'nullable',
                'string',
                'max:255',
            ],
            'segmentation_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    SegmentationModel::class,
                    'id'
                ),
            ],
            'segmentationId' => [
                'nullable',
                'integer',
                Rule::exists(
                    SegmentationModel::class,
                    'id'
                ),
            ],
            'segmentation' => [
                'nullable',
                'string',
                'max:255',
            ],
            'level' => [
                'required',
                Rule::in([
                    'Low',
                    'Medium',
                    'High',
                ]),
            ],
            'divisi' => [
                'required_without:divisi',
                'nullable',
                Rule::in([
                    'Industri',
                    'SME',
                    'Low Cost',
                    'All',
                ]),
            ],
            'division' => [
                'nullable',
                Rule::in([
                    'Industri',
                    'SME',
                    'Low Cost',
                    'All',
                ]),
            ],
            'pic' => [
                'nullable',
                'string',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],
            'sterilization' => [
                'required',
                Rule::in(['NS', 'S', 'SS']),
            ],
        ]);

        $validated['name'] ??= $validated['company'] ?? null;
        $validated['segmentation_id'] ??= $this->resolveSegmentationId(
            $validated['segmentationId'] ?? $validated['segmentation'] ?? null
        );
        $validated['divisi'] ??= $validated['division'] ?? null;

        unset(
            $validated['segmentation'],
            $validated['segmentationId'],
            $validated['company'],
            $validated['division']
        );

        return array_filter(
            $validated,
            fn ($value) => $value !== null
        );
    }

    private function resolveSegmentationId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $name = trim((string) $value);

        $segmentation = SegmentationModel::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if (! $segmentation) {
            abort(422, sprintf('Segmentasi "%s" tidak ditemukan.', $name));
        }

        return $segmentation->id;
    }

    private function readImportRows($file): array
    {
        return SpreadsheetFile::readRows($file);
    }

    private function normalizeImportRows(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $header = $this->normalizeImportHeaderRow(array_shift($rows));
        $required = ['company', 'address', 'segmentation', 'level', 'division', 'pic', 'phone'];
        $fields = [...$required, 'sterilization'];

        if (empty($header)) {
            return [];
        }

        $mapped = [];

        foreach ($header as $index => $field) {
            if ($field !== null) {
                $mapped[$field] = $index;
            }
        }

        $missing = array_diff($required, array_keys($mapped));

        if (! empty($missing)) {
            return [];
        }

        $normalized = [];

        foreach ($rows as $row) {
            $record = [];

            foreach ($fields as $field) {
                $index = $mapped[$field] ?? ($field === 'segmentation' ? ($mapped['segmentationId'] ?? null) : null);
                $record[$field] = $index !== null && isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            $record['sterilization'] = $record['sterilization'] ?: 'S';

            if (empty(array_filter($record, fn ($value) => $value !== ''))) {
                continue;
            }

            $normalized[] = $record;
        }

        return $normalized;
    }

    private function normalizeImportHeaderRow(array $header): array
    {
        $normalized = [];

        foreach ($header as $index => $value) {
            $normalized[$index] = $this->matchImportHeader((string) $value);
        }

        return $normalized;
    }

    private function matchImportHeader(string $value): ?string
    {
        $key = strtolower(trim(preg_replace('/[^a-z0-9]+/i', ' ', $value)));

        $aliases = [
            'company' => ['company', 'nama perusahaan', 'nama perusahaan customer', 'perusahaan'],
            'address' => ['address', 'alamat', 'domisili', 'lokasi'],
            'segmentation' => ['segmentation', 'segmentationid', 'segmentation id', 'segmentation_id', 'segmentasi', 'segmentasi id', 'sementation'],
            'segmentationId' => ['segmentationid', 'segmentation id', 'segmentation_id', 'segmentasi', 'segmentasi id', 'sementation'],
            'level' => ['level', 'level risiko', 'risk level', 'risiko'],
            'division' => ['division', 'divisi', 'customer division', 'jenis divisi'],
            'sterilization' => ['sterilization', 'sterilisation', 'sterilisasi'],
            'pic' => ['pic', 'nama pic', 'pic name', 'person in charge'],
            'phone' => ['phone', 'telephone', 'telp', 'nomor telepon', 'phone number'],
        ];

        foreach ($aliases as $field => $options) {
            if (in_array($key, $options, true)) {
                return $field;
            }
        }

        return null;
    }

    private function validateImportRecord(array $row): array
    {
        $company = trim((string) ($row['company'] ?? ''));
        $address = trim((string) ($row['address'] ?? ''));
        $division = $this->normalizeImportDivision((string) ($row['division'] ?? ''));
        $level = $this->normalizeImportLevel((string) ($row['level'] ?? ''));
        $sterilization = $this->normalizeImportSterilization((string) ($row['sterilization'] ?? ''));
        $pic = trim((string) ($row['pic'] ?? ''));
        $phone = trim((string) ($row['phone'] ?? ''));
        $segmentationId = $this->normalizeImportSegmentationId((string) ($row['segmentation'] ?? $row['segmentationId'] ?? ''));

        $validated = [
            'name' => $company,
            'address' => $address,
            'segmentation_id' => $segmentationId,
            'level' => $level,
            'divisi' => $division,
            'sterilization' => $sterilization,
            'pic' => $pic,
            'phone' => $phone,
        ];

        return $this->validateCustomerData($validated);
    }

    private function validateCustomerData(array $validated): array
    {
        $validator = Validator::make($validated, [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'segmentation_id' => ['required', 'integer', Rule::exists(SegmentationModel::class, 'id')],
            'level' => ['required', Rule::in(['Low', 'Medium', 'High'])],
            'divisi' => ['required', Rule::in(['Industri', 'SME', 'Low Cost', 'All'])],
            'sterilization' => ['required', Rule::in(['NS', 'S', 'SS'])],
            'pic' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        return array_filter(
            $validated,
            fn ($value) => $value !== null && $value !== ''
        );
    }

    private function normalizeImportSegmentationId(string $value): ?int
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $segmentation = SegmentationModel::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($value)])
            ->first();

        if (! $segmentation) {
            throw new \InvalidArgumentException(sprintf('Segmentasi "%s" tidak ditemukan.', $value));
        }

        return $segmentation->id;
    }

    private function normalizeImportLevel(string $value): ?string
    {
        $key = strtolower(trim($value));

        return match ($key) {
            'low risk', 'low' => 'Low',
            'medium risk', 'medium' => 'Medium',
            'high risk', 'high' => 'High',
            default => null,
        };
    }

    private function normalizeImportDivision(string $value): ?string
    {
        $key = strtolower(trim($value));

        return match ($key) {
            'industri', 'industry' => 'Industri',
            'sme' => 'SME',
            'low cost', 'low-cost', 'lowcost' => 'Low Cost',
            'all' => 'All',
            default => null,
        };
    }

    private function normalizeImportSterilization(string $value): ?string
    {
        $key = strtolower(trim($value));

        return match ($key) {
            'ns', 'non-steril', 'non steril', 'ns (non-steril)' => 'NS',
            's', 'steril', 's (steril)' => 'S',
            'ss', 'super steril', 'ss (super steril)' => 'SS',
            default => null,
        };
    }
}
