<?php

namespace App\Http\Controllers;

use App\Models\Core\CustomerModel;
use App\Models\Core\SegmentationModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use ZipArchive;

class CustomerController extends Controller
{
    public function index(): Response
    {
        $customers = CustomerModel::query()
            ->with('segmentation:id,name')
            ->orderBy('name')
            ->get()
            ->map(function (CustomerModel $customer) {
                return [
                    'id' => $customer->id,

                    // Mapping database -> frontend
                    'customerId' => $customer->id,
                    'company' => $customer->name,
                    'address' => $customer->address,

                    'segmentationId' =>
                        $customer->segmentation_id,

                    'segmentation' =>
                        $customer->segmentation?->name ?? '-',

                    'level' => $customer->level,

                    'division' => $customer->divisi,

                    'pic' => $customer->pic,
                    'phone' => $customer->phone,

                    'sterilization' =>
                        $customer->sterilization,
                ];
            });

        $segmentations = SegmentationModel::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render(
            'DatabaseCenter',
            [
                'customers' => $customers,
                'segmentations' => $segmentations,
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

        $sheet = $this->buildXlsxSheet($rows);

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
                'required_without:division',
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
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv') {
            return $this->readCsvRows($file->getRealPath());
        }

        if ($extension === 'xlsx') {
            return $this->readXlsxRows($file->getRealPath());
        }

        throw new \InvalidArgumentException('Format file tidak didukung. Gunakan CSV atau Excel (.xlsx).');
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');
        $rows = [];

        if ($handle === false) {
            return $rows;
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedStringXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedStringXml !== false) {
            $shared = simplexml_load_string($sharedStringXml, options: LIBXML_NONET);

            if ($shared !== false) {
                $namespace = $shared->getNamespaces(true)[''] ?? '';

                foreach ($shared->children($namespace)->si as $item) {
                    $itemChildren = $item->children($namespace);
                    $text = '';

                    if (isset($itemChildren->t)) {
                        $text = (string) $itemChildren->t;
                    }

                    if (isset($itemChildren->r)) {
                        foreach ($itemChildren->r as $run) {
                            $text .= (string) $run->children($namespace)->t;
                        }
                    }

                    $sharedStrings[] = $text;
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml, options: LIBXML_NONET);

        if ($sheet === false) {
            return [];
        }

        $namespace = $sheet->getNamespaces(true)[''] ?? '';
        $rows = [];

        foreach ($sheet->children($namespace)->sheetData->row as $row) {
            $values = [];

            foreach ($row->children($namespace)->c as $cell) {
                $attributes = $cell->attributes();
                $cellRef = (string) ($attributes['r'] ?? '');
                $columnIndex = $this->columnNameFromReference($cellRef);
                $type = (string) ($attributes['t'] ?? '');
                $value = '';
                $cellChildren = $cell->children($namespace);

                if (isset($cellChildren->v)) {
                    $value = (string) $cellChildren->v;
                }

                if ($type === 's' && isset($sharedStrings[(int) $value])) {
                    $value = $sharedStrings[(int) $value];
                }

                if ($type === 'inlineStr' && isset($cellChildren->is)) {
                    $inlineChildren = $cellChildren->is->children($namespace);
                    $value = (string) ($inlineChildren->t ?? '');

                    foreach ($inlineChildren->r as $run) {
                        $value .= (string) $run->children($namespace)->t;
                    }
                }

                if (isset($values[$columnIndex])) {
                    $values[$columnIndex] .= ' ' . $value;
                } else {
                    $values[$columnIndex] = $value;
                }
            }

            $ordered = [];
            $maxColumn = empty($values) ? 0 : max(array_keys($values));

            for ($i = 1; $i <= $maxColumn; $i++) {
                $ordered[] = $values[$i] ?? '';
            }

            $rows[] = $ordered;
        }

        return $rows;
    }

    private function columnNameFromReference(string $reference): int
    {
        preg_match('/[A-Z]+/', $reference, $matches);

        if (! isset($matches[0])) {
            return 1;
        }

        $letters = $matches[0];
        $value = 0;

        foreach (str_split($letters) as $char) {
            $value = $value * 26 + (ord(strtoupper($char)) - 64);
        }

        return $value;
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
        $validator = \Illuminate\Support\Facades\Validator::make($validated, [
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

    private function buildXlsxSheet(array $rows): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'customer-xlsx-');
        $zip = new ZipArchive();

        if ($zip->open($tempFile, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Tidak dapat membuat template Excel.');
        }

        $contentTypes = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
XML;

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML);

        $zip->addFromString('docProps/core.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:creator>Sales Ops</dc:creator>
  <cp:lastModifiedBy>Sales Ops</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">2026-10-01T00:00:00Z</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">2026-10-01T00:00:00Z</dcterms:modified>
</cp:coreProperties>
XML);

        $zip->addFromString('docProps/app.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Sales Ops</Application>
</Properties>
XML);

        $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Customer Import" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML);

        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML);

        $sheetXml = $this->buildWorksheetXml($rows);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $binary = file_get_contents($tempFile);
        unlink($tempFile);

        return $binary;
    }

    private function buildWorksheetXml(array $rows): string
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
XML;

        foreach ($rows as $rowIndex => $row) {
            $xml .= '    <row r="'.($rowIndex + 1).'">';

            foreach ($row as $cellIndex => $value) {
                $column = $this->excelColumnName($cellIndex + 1);
                $cellValue = $this->escapeXml((string) $value);
                $xml .= '<c r="'.$column.($rowIndex + 1).'" t="inlineStr"><is><t>'.$cellValue.'</t></is></c>';
            }

            $xml .= '</row>';
        }

        $xml .= <<<'XML'
  </sheetData>
</worksheet>
XML;

        return $xml;
    }

    private function excelColumnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function escapeXml(string $value): string
    {
        return str_replace(
            ['&', '<', '>', '"', "'"],
            ['&amp;', '&lt;', '&gt;', '&quot;', '&apos;'],
            $value
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