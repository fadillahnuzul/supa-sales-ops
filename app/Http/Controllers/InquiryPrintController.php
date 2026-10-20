<?php

namespace App\Http\Controllers;

use App\Models\Sales\PrintTemplateModel;
use App\Models\Sales\SalesSignatureModel;
use App\Services\InquiryPrintService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class InquiryPrintController extends Controller
{
    public function page()
    {
        $pics = DB::table('core.employees as e')
            ->join(
                'core.employee_positions as ep',
                'ep.employee_id',
                '=',
                'e.id'
            )
            ->join(
                'core.positions as p',
                'p.id',
                '=',
                'ep.position_id'
            )
            ->where(
                'p.division_id',
                2
            )
            ->select([
                'e.id',
                DB::raw("
                CONCAT(
                    e.first_name,
                    ' ',
                    e.last_name
                ) as name
            "),
            ])
            ->distinct()
            ->orderBy('name')
            ->get();

        return Inertia::render(
            'InquiryPrintCenter',
            [
                'pics' => $pics,
            ]
        );
    }

    protected InquiryPrintService $printService;

    public function __construct(
        InquiryPrintService $printService
    ) {
        $this->printService = $printService;
    }

    public function index(Request $request)
    {
        $query = DB::table('sales.inquiries as i')

            ->leftJoin(
                'core.customers as c',
                'c.id',
                '=',
                'i.customer_id'
            )

            ->leftJoin(
                'sales.inquiry_details as d',
                'd.inquiry_id',
                '=',
                'i.id'
            )

            ->leftJoin(
                'core.employees as e',
                'e.id',
                '=',
                'i.pic'
            )

            ->select([
                'i.id',
                'i.code',
                'i.date',
                'i.pic as pic_id',
                'e.first_name as pic_first_name',

                'c.name as customer_name',

                DB::raw(
                    'COUNT(d.id) as total_items'
                ),

                DB::raw("
            CASE
                WHEN e.id IS NULL THEN NULL
                ELSE TRIM(
                    CONCAT(
                        COALESCE(e.first_name, ''),
                        ' ',
                        COALESCE(e.last_name, '')
                    )
                )
            END as pic_name
        "),
            ])

            ->groupBy(
                'i.id',
                'i.code',
                'i.date',
                'i.pic',
                'c.name',
                'e.id',
                'e.first_name',
                'e.last_name'
            );

        if ($request->filled('year')) {
            $query->whereYear(
                'i.date',
                $request->year
            );
        }

        if ($request->filled('month')) {
            $query->whereMonth(
                'i.date',
                $request->month
            );
        }

        if ($request->filled('day')) {
            $query->whereDay(
                'i.date',
                $request->day
            );
        }

        if ($request->filled('search')) {

            $search =
                '%'.
                strtolower(
                    $request->search
                ).
                '%';

            $query->where(function ($q) use ($search) {

                $q->whereRaw(
                    'LOWER(i.code) LIKE ?',
                    [$search]
                );

                $q->orWhereRaw(
                    'LOWER(c.name) LIKE ?',
                    [$search]
                );

                $q->orWhereRaw(
                    "
            LOWER(
                CONCAT(
                    COALESCE(e.first_name, ''),
                    ' ',
                    COALESCE(e.last_name, '')
                )
            ) LIKE ?
            ",
                    [$search]
                );
            });
        }

        return response()->json(
            $query
                ->orderByDesc('i.date')
                ->orderByDesc('i.id')
                ->paginate(20)
        );
    }

    public function show(int $id)
    {
        return response()->json(
            $this->printService
                ->getFullInquiry($id)
        );
    }

    public function exportExcel(int $id)
    {
        $data = $this->printService
            ->getFullInquiry($id);

        $inquiry = $data['inquiry'];
        $details = $data['details'];

        $templateRelativePath =
            'inquiry-templates/excel/inquiry_form.xlsx';

        $templatePath = Storage::disk('local')->path(
            $templateRelativePath
        );

        if (! file_exists($templatePath)) {
            $templatePath = storage_path(
                'app/'.$templateRelativePath
            );
        }

        if (! file_exists($templatePath)) {
            abort(
                404,
                'Template Inquiry Form tidak ditemukan.'
            );
        }

        $spreadsheet =
            IOFactory::load($templatePath);

        $sheet =
            $spreadsheet->getSheetByName(
                'PRINT_FORM'
            );

        if (! $sheet) {
            abort(
                500,
                'Sheet PRINT_FORM tidak ditemukan.'
            );
        }

        $sheet->setCellValue(
            'E9',
            Carbon::parse($inquiry->date)->locale('id')
                ->translatedFormat('d F Y')
        );

        $sheet->setCellValue(
            'E10',
            $inquiry->pic_name ?? '-'
        );

        $sheet->setCellValue(
            'E11',
            $inquiry->customer_name
        );

        $sheet->setCellValue(
            'E12',
            Carbon::parse($inquiry->etd)->locale('id')
                ->translatedFormat('d F Y')
        );

        $sheet->setCellValue(
            'E13',
            $inquiry->segmentation ?? '-'
        );

        $sheet->setCellValue(
            'E14',
            $inquiry->level ?? '-'
        );

        $sheet->setCellValue(
            'E15',
            $inquiry->sterilization ?? '-'
        );

        $startRow = 18;

        foreach ($details as $index => $detail) {

            $row = $startRow + $index;

            $sheet->setCellValue(
                "B{$row}",
                $index + 1
            );

            $sheet->setCellValue(
                "C{$row}",
                $detail->product_name
            );

            $sheet->setCellValue(
                "D{$row}",
                $detail->product_code
            );

            $sheet->setCellValue(
                "F{$row}",
                $detail->qty
            );

            $sheet->setCellValue(
                "K{$row}",
                $detail->recommended_price
            );

            $sheet->setCellValue(
                "L{$row}",
                $detail->approved_price
            );

            $sheet->setCellValue(
                "M{$row}",
                $detail->approved_date
            );

            $sheet->setCellValue(
                "N{$row}",
                $detail->offer_1_price
            );

            $sheet->setCellValue(
                "O{$row}",
                $detail->offer_2_price
            );

            $sheet->setCellValue(
                "P{$row}",
                $detail->offer_3_price
            );

            $sheet->setCellValue(
                "Q{$row}",
                $detail->final_price
            );

            $sheet->setCellValue(
                "R{$row}",
                $detail->note
            );
        }

        $sheet->setCellValue(
            'D44',
            $inquiry->shipping_rate
        );

        $fileName =
            'INQUIRY_FORM_'.
            preg_replace(
                '/[^A-Za-z0-9_-]/',
                '_',
                $inquiry->code
            ).
            '.xlsx';

        $tempPath = storage_path(
            'app/temp/'.$fileName
        );

        if (! is_dir(dirname($tempPath))) {
            mkdir(
                dirname($tempPath),
                0755,
                true
            );
        }

        $writer =
            IOFactory::createWriter(
                $spreadsheet,
                'Xlsx'
            );

        $writer->save($tempPath);

        return response()
            ->download(
                $tempPath,
                $fileName
            )
            ->deleteFileAfterSend(true);
    }

    public function exportWord(
        Request $request,
        int $id
    ) {
        $request->validate([
            'template_id' => [
                'required',
                'integer',
            ],

            'signature_id' => [
                'nullable',
                'integer',
            ],

            'quotation_date' => [
                'nullable',
                'date',
            ],
        ]);

        /*
    |--------------------------------------------------------------------------
    | Inquiry
    |--------------------------------------------------------------------------
    */

        $data =
            $this->printService
                ->getFullInquiry($id);

        $inquiry =
            $data['inquiry'];

        $details =
            $data['details'];

        /*
    |--------------------------------------------------------------------------
    | Template
    |--------------------------------------------------------------------------
    */

        $template =
            PrintTemplateModel::findOrFail(
                $request->template_id
            );

        $templatePath =
            Storage::disk('local')
                ->path(
                    $template->file_path
                );

        if (! file_exists($templatePath)) {
            abort(
                404,
                'File template tidak ditemukan.'
            );
        }

        $processor =
            $this->makeQuotationTemplateProcessor(
                $templatePath
            );

        $processor->setValue(
            'quotation_date',
            Carbon::parse($request->quotation_date)->locale('id')
                ->translatedFormat('d F Y')
                ?? now()->format('d F Y')
        );

        $processor->setValue(
            'customer_name',
            $inquiry->customer_name ?? '-'
        );

        $processor->setValue(
            'customer_address',
            $inquiry->customer_address ?? '-'
        );

        $processor->setValue(
            'inquiry_code',
            $inquiry->code
        );

        $rows = [];

        foreach (
            $details as $index => $detail
        ) {

            $price =
                $detail->final_price
                ?? $detail->offer_3_price
                ?? $detail->offer_2_price
                ?? $detail->offer_1_price
                ?? $detail->approved_price
                ?? 0;

            $rows[] = [
                'no' => $index + 1,

                'product_name' => $detail->product_name,

                'price' => number_format(
                    $price,
                    0,
                    ',',
                    '.'
                ),
            ];
        }

        if (count($rows) > 0) {
            $processor
                ->cloneRowAndSetValues(
                    'no',
                    $rows
                );
        } else {
            $processor->deleteRow('no');
        }

        $this->applySignature(
            $processor,
            $request->filled('signature_id')
                ? (int) $request->input('signature_id')
                : null,
            $inquiry->pic ?? ''
        );

        $safeCode =
            preg_replace(
                '/[^A-Za-z0-9_-]/',
                '_',
                $inquiry->code
            );

        $fileName =
            'QUOTATION_'.
            $inquiry->code.
            '.docx';

        $tempDir =
            storage_path(
                'app/temp'
            );

        if (! is_dir($tempDir)) {
            mkdir(
                $tempDir,
                0755,
                true
            );
        }

        $outputPath =
            $tempDir.
            DIRECTORY_SEPARATOR.
            $fileName;

        $processor
            ->saveAs(
                $outputPath
            );

        return response()
            ->download(
                $outputPath,
                $fileName
            )
            ->deleteFileAfterSend(true);
    }

    public function exportPdf(
        Request $request,
        int $id
    ) {
        $request->validate([
            'template_id' => [
                'required',
                'integer',
            ],

            'signature_id' => [
                'nullable',
                'integer',
            ],

            'quotation_date' => 'nullable|date',
        ]);

        $data =
            $this->printService
                ->getFullInquiry($id);

        $inquiry =
            $data['inquiry'];

        $details =
            $data['details'];

        $template =
            PrintTemplateModel::findOrFail(
                $request->template_id
            );

        $templatePath =
            Storage::disk('local')
                ->path(
                    $template->file_path
                );

        if (! file_exists($templatePath)) {
            abort(
                404,
                'File template tidak ditemukan.'
            );
        }

        $processor =
            $this->makeQuotationTemplateProcessor(
                $templatePath
            );

        $processor->setValue(
            'quotation_date',
            Carbon::parse($request->quotation_date)->locale('id')
                ->translatedFormat('d F Y')
                ?? now()->format('d F Y')
        );

        $processor->setValue(
            'customer_name',
            $inquiry->customer_name
                ?? '-'
        );

        $processor->setValue(
            'inquiry_code',
            $inquiry->code
                ?? '-'
        );

        $processor->setValue(
            'customer_address',
            $inquiry->customer_address
                ?? '-'
        );

        $rows = [];

        foreach (
            $details as $index => $detail
        ) {

            $price =
                $detail->final_price
                ?? $detail->offer_3_price
                ?? $detail->offer_2_price
                ?? $detail->offer_1_price
                ?? $detail->approved_price
                ?? 0;

            $rows[] = [
                'no' => $index + 1,

                'product_name' => $detail->product_name,

                'price' => number_format(
                    $price,
                    0,
                    ',',
                    '.'
                ),
            ];
        }

        $processor->cloneRowAndSetValues(
            'no',
            $rows
        );

        $this->applySignature(
            $processor,
            $request->filled('signature_id')
                ? (int) $request->input('signature_id')
                : null,
            $inquiry->pic ?? ''
        );

        $tempDir =
            storage_path('app/temp');

        if (! is_dir($tempDir)) {
            mkdir(
                $tempDir,
                0755,
                true
            );
        }

        $fileBase =
            'QUOTATION_'.
            $inquiry->code.
            '_'.
            Str::uuid();

        $docxPath =
            $tempDir.
            DIRECTORY_SEPARATOR.
            $fileBase.
            '.docx';

        $profileDirectory =
            $tempDir.
            DIRECTORY_SEPARATOR.
            'libreoffice-profile-'.
            Str::uuid();

        $processor->saveAs(
            $docxPath
        );

        if (
            ! mkdir(
                $profileDirectory,
                0755,
                true
            )
            && ! is_dir($profileDirectory)
        ) {
            throw new \RuntimeException(
                'Tidak dapat membuat profil LibreOffice sementara.'
            );
        }

        $profilePath =
            str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                $profileDirectory
            );

        $profileUri =
            (PHP_OS_FAMILY === 'Windows'
                ? 'file:///'
                : 'file://').
            str_replace(
                ' ',
                '%20',
                $profilePath
            );

        $process = new Process([
            config('services.libreoffice.binary'),
            '--headless',
            '--nologo',
            '--nodefault',
            '--nofirststartwizard',
            '-env:UserInstallation='.$profileUri,
            '--convert-to',
            'pdf',
            '--outdir',
            $tempDir,
            $docxPath,
        ]);
        $process->setTimeout(120);

        $conversionStartedAt = microtime(true);

        try {
            $process->run();
        } catch (ProcessStartFailedException $exception) {
            Log::error('Unable to start LibreOffice for PDF conversion.', [
                'binary' => config('services.libreoffice.binary'),
                'duration_ms' => (int) ((microtime(true) - $conversionStartedAt) * 1000),
                'exception' => $exception->getMessage(),
            ]);

            abort(
                500,
                'LibreOffice tidak ditemukan. Pastikan LibreOffice terpasang dan LIBREOFFICE_BINARY menunjuk ke executable yang benar.'
            );
        } catch (ProcessTimedOutException $exception) {
            Log::error('LibreOffice PDF conversion timed out.', [
                'inquiry_id' => $id,
                'duration_ms' => (int) ((microtime(true) - $conversionStartedAt) * 1000),
                'exception' => $exception->getMessage(),
            ]);

            abort(
                500,
                'Konversi PDF melewati batas waktu. Periksa log aplikasi untuk detail.'
            );
        } finally {
            if (is_file($docxPath)) {
                unlink($docxPath);
            }

            File::deleteDirectory($profileDirectory);
        }

        if (! $process->isSuccessful()) {
            Log::error('LibreOffice PDF conversion failed.', [
                'inquiry_id' => $id,
                'duration_ms' => (int) ((microtime(true) - $conversionStartedAt) * 1000),
                'exit_code' => $process->getExitCode(),
                'output' => $process->getOutput(),
                'error_output' => $process->getErrorOutput(),
            ]);

            abort(
                500,
                'Gagal melakukan konversi PDF. Periksa log aplikasi untuk detail.'
            );
        }

        $pdfPath =
            $tempDir.
            DIRECTORY_SEPARATOR.
            $fileBase.
            '.pdf';

        if (! file_exists($pdfPath)) {
            Log::error('LibreOffice reported success without creating a PDF.', [
                'inquiry_id' => $id,
                'duration_ms' => (int) ((microtime(true) - $conversionStartedAt) * 1000),
                'pdf_path' => $pdfPath,
                'output' => $process->getOutput(),
                'error_output' => $process->getErrorOutput(),
            ]);

            abort(
                500,
                'PDF tidak berhasil dibuat. Periksa log aplikasi untuk detail.'
            );
        }

        Log::debug('LibreOffice PDF conversion completed.', [
            'inquiry_id' => $id,
            'duration_ms' => (int) ((microtime(true) - $conversionStartedAt) * 1000),
        ]);

        return response()
            ->download(
                $pdfPath,
                $fileBase.'.pdf'
            )
            ->deleteFileAfterSend(true);
    }

    private function applySignature(
        TemplateProcessor $processor,
        ?int $signatureId,
        ?string $fallbackName = null
    ): void {
        if ($signatureId === null) {
            $processor->setValue('signature', '');
            $processor->setValue('sales_name', $fallbackName ?? '');
            $processor->setValue('sales_position', '');
            $processor->setValue('sales_division', '');

            return;
        }

        $signature = SalesSignatureModel::query()
            ->where('is_active', true)
            ->findOrFail($signatureId);

        $signaturePath = Storage::disk('local')
            ->path($signature->signature_path);

        if (! is_file($signaturePath)) {
            Log::error('Sales signature file is missing.', [
                'signature_id' => $signature->id,
                'signature_path' => $signature->signature_path,
            ]);

            abort(404, 'File signature tidak ditemukan.');
        }

        $processor->setImageValue('signature', [
            'path' => $signaturePath,
            'width' => 160,
            'height' => 160,
            'ratio' => true,
        ]);

        $processor->setValue('sales_name', $signature->name);
        $processor->setValue('sales_position', $signature->position ?? '');
        $processor->setValue('sales_division', $signature->division ?? '');
    }

    private function makeQuotationTemplateProcessor(
        string $templatePath
    ): TemplateProcessor {
        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $workingTemplatePath =
            $tempDir.
            DIRECTORY_SEPARATOR.
            'quotation-template-'.
            Str::uuid().
            '.docx';

        if (! copy($templatePath, $workingTemplatePath)) {
            throw new \RuntimeException(
                'Tidak dapat menyiapkan template quotation.'
            );
        }

        try {
            $this->adjustQuotationTemplateLayout(
                $workingTemplatePath
            );

            return new TemplateProcessor(
                $workingTemplatePath
            );
        } finally {
            if (is_file($workingTemplatePath)) {
                unlink($workingTemplatePath);
            }
        }
    }

    private function adjustQuotationTemplateLayout(
        string $templatePath
    ): void {
        $archive = new \ZipArchive;

        if ($archive->open($templatePath) !== true) {
            throw new \RuntimeException(
                'Tidak dapat membaca struktur template quotation.'
            );
        }

        try {
            $documentXml = $archive->getFromName(
                'word/document.xml'
            );

            if ($documentXml === false) {
                throw new \RuntimeException(
                    'Struktur dokumen template quotation tidak lengkap.'
                );
            }

            $document = new \DOMDocument;
            $document->preserveWhiteSpace = true;

            if (! $document->loadXML($documentXml, LIBXML_NONET)) {
                throw new \RuntimeException(
                    'Struktur XML template quotation tidak valid.'
                );
            }

            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace(
                'w',
                'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
            );
            $xpath->registerNamespace(
                'wp',
                'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing'
            );
            $xpath->registerNamespace(
                'v',
                'urn:schemas-microsoft-com:vml'
            );

            $tables = $xpath->query(
                '/w:document/w:body/w:tbl'
            );

            if ($tables === false) {
                throw new \RuntimeException(
                    'Tidak dapat membaca tabel template quotation.'
                );
            }

            foreach ($tables as $table) {
                $tableText = $table->textContent;

                if (
                    ! str_contains($tableText, 'product_name')
                    || ! str_contains($tableText, 'price')
                    || ! str_contains($tableText, 'no')
                ) {
                    continue;
                }

                $positioning = $xpath->query(
                    './w:tblPr/w:tblpPr',
                    $table
                );

                if ($positioning !== false) {
                    foreach ($positioning as $position) {
                        $position->parentNode?->removeChild(
                            $position
                        );
                    }
                }

                $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

                $tblPr = $xpath->query('./w:tblPr', $table)->item(0);

                if ($tblPr instanceof \DOMElement) {
                    $alignment = $xpath->query('./w:jc', $tblPr)->item(0);

                    if (! $alignment instanceof \DOMElement) {
                        $alignment = $document->createElementNS(
                            $namespace,
                            'w:jc'
                        );

                        $tblInd = $xpath->query('./w:tblInd', $tblPr)->item(0);

                        if ($tblInd !== null) {
                            $tblPr->insertBefore($alignment, $tblInd);
                        } else {
                            $tblPr->appendChild($alignment);
                        }
                    }

                    $alignment->setAttributeNS(
                        $namespace,
                        'w:val',
                        'center'
                    );
                }

                $nextNode = $table->nextSibling;

                while ($nextNode !== null) {
                    if (
                        $nextNode instanceof \DOMText
                        && trim($nextNode->textContent) === ''
                    ) {
                        $nextNode = $nextNode->nextSibling;

                        continue;
                    }

                    if (
                        ! $nextNode instanceof \DOMElement
                        || $nextNode->localName !== 'p'
                        || trim($nextNode->textContent) !== ''
                    ) {
                        break;
                    }

                    $hasLayoutContent = $xpath->query(
                        './/w:br | .//w:drawing | .//w:pict | .//w:sectPr',
                        $nextNode
                    );

                    if (
                        $hasLayoutContent === false
                        || $hasLayoutContent->length > 0
                    ) {
                        break;
                    }

                    $followingNode = $nextNode->nextSibling;
                    $nextNode->parentNode?->removeChild(
                        $nextNode
                    );
                    $nextNode = $followingNode;
                }
            }

            $signatureTextBoxes = $xpath->query(
                '//w:txbxContent[contains(., "sales_name") and contains(., "signature")]'
            );

            if ($signatureTextBoxes !== false) {
                foreach ($signatureTextBoxes as $textBox) {
                    $ancestor = $textBox->parentNode;

                    while (
                        $ancestor instanceof \DOMElement
                        && $ancestor->localName !== 'anchor'
                    ) {
                        $ancestor = $ancestor->parentNode;
                    }

                    if (! $ancestor instanceof \DOMElement) {
                        continue;
                    }

                    $extentNodes = $xpath->query(
                        './wp:extent',
                        $ancestor
                    );

                    if ($extentNodes === false) {
                        continue;
                    }

                    foreach ($extentNodes as $extent) {
                        $extent->setAttribute('cy', '2286000');
                    }
                }
            }

            $signatureShapes = $xpath->query(
                '//v:shape[.//w:txbxContent[contains(., "sales_name") and contains(., "signature")]]'
            );

            if ($signatureShapes !== false) {
                foreach ($signatureShapes as $shape) {
                    $style = $shape->getAttribute('style');

                    if (preg_match('/(^|;)\s*height\s*:[^;]*/i', $style) === 1) {
                        $style = preg_replace(
                            '/(^|;)\s*height\s*:[^;]*/i',
                            '$1height:180pt',
                            $style
                        ) ?? $style;
                    } else {
                        $style .= ';height:180pt';
                    }

                    $shape->setAttribute('style', $style);
                }
            }

            $updatedXml = $document->saveXML();

            if (
                $updatedXml === false
                || ! $archive->addFromString(
                    'word/document.xml',
                    $updatedXml
                )
            ) {
                throw new \RuntimeException(
                    'Tidak dapat memperbarui tata letak template quotation.'
                );
            }
        } finally {
            $archive->close();
        }
    }
}
