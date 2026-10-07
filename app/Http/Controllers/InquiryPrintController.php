<?php

namespace App\Http\Controllers;

use App\Models\Sales\PrintTemplateModel;
use App\Models\Sales\SalesSignatureModel;
use App\Services\InquiryPrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Inertia\Inertia;

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
                '%' .
                strtolower(
                    $request->search
                ) .
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

        $templatePath = Storage::disk('local')->path(
            'inquiry-templates/excel/inquiry_form.xlsx'
        );

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
            'C5',
            $inquiry->date
        );

        $sheet->setCellValue(
            'C6',
            $inquiry->pic
        );

        $sheet->setCellValue(
            'C7',
            $inquiry->customer_name
        );

        $startRow = 15;

        foreach ($details as $index => $detail) {

            $row = $startRow + $index;

            $sheet->setCellValue(
                "A{$row}",
                $index + 1
            );

            $sheet->setCellValue(
                "B{$row}",
                $detail->product_name
            );

            $sheet->setCellValue(
                "C{$row}",
                $detail->product_code
            );

            $sheet->setCellValue(
                "D{$row}",
                $detail->qty
            );

            $sheet->setCellValue(
                "I{$row}",
                $detail->recommended_price
            );

            $sheet->setCellValue(
                "J{$row}",
                $detail->approved_price
            );

            $sheet->setCellValue(
                "K{$row}",
                $detail->approved_date
            );

            $sheet->setCellValue(
                "L{$row}",
                $detail->offer_1_price
            );

            $sheet->setCellValue(
                "M{$row}",
                $detail->offer_2_price
            );

            $sheet->setCellValue(
                "N{$row}",
                $detail->offer_3_price
            );

            $sheet->setCellValue(
                "O{$row}",
                $detail->final_price
            );

            $sheet->setCellValue(
                "P{$row}",
                $detail->note
            );
        }

        $sheet->setCellValue(
            'N38',
            $inquiry->shipping_rate
        );

        $fileName =
            'INQUIRY_FORM_' .
            preg_replace(
                '/[^A-Za-z0-9_-]/',
                '_',
                $inquiry->code
            ) .
            '.xlsx';

        $tempPath = storage_path(
            'app/temp/' . $fileName
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
            new TemplateProcessor(
                $templatePath
            );

        $processor->setValue(
            'quotation_date',
            $request->quotation_date
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
        }

        if ($request->signature_id) {

            $signature =
                SalesSignatureModel::find(
                    $request->signature_id
                );

            if ($signature) {

                $signaturePath =
                    Storage::disk('local')
                    ->path(
                        $signature->signature_path
                    );

                if (
                    file_exists(
                        $signaturePath
                    )
                ) {

                    $processor
                        ->setImageValue(
                            'signature',
                            [
                                'path' => $signaturePath,

                                'width' => 120,

                                'height' => 80,

                                'ratio' => true,
                            ]
                        );
                }

                $processor->setValue(
                    'sales_name',
                    $signature->name
                );

                $processor->setValue(
                    'sales_position',
                    $signature->position
                        ?? ''
                );

                $processor->setValue(
                    'sales_division',
                    $signature->division
                        ?? ''
                );
            }
        } else {

            $processor->setValue(
                'signature',
                ''
            );

            $processor->setValue(
                'sales_name',
                $inquiry->pic ?? ''
            );

            $processor->setValue(
                'sales_position',
                ''
            );

            $processor->setValue(
                'sales_division',
                ''
            );
        }

        $safeCode =
            preg_replace(
                '/[^A-Za-z0-9_-]/',
                '_',
                $inquiry->code
            );

        $fileName =
            'QUOTATION_' .
            $safeCode .
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
            $tempDir .
            DIRECTORY_SEPARATOR .
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
            'template_id' => 'required|integer',

            'signature_id' => 'nullable|integer',

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

        $processor =
            new TemplateProcessor(
                $templatePath
            );

        $processor->setValue(
            'quotation_date',
            $request->quotation_date
                ?? now()->format('d F Y')
        );

        $processor->setValue(
            'customer_name',
            $inquiry->customer_name
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

        $signature =
            SalesSignatureModel::find(
                $request->signature_id
            );

        if ($signature) {

            $path =
                storage_path(
                    'app/' .
                        $signature->signature_path
                );

            if (file_exists($path)) {

                $processor->setImageValue(
                    'signature',
                    [
                        'path' => $path,
                        'width' => 120,
                        'height' => 80,
                    ]
                );
            }

            $processor->setValue(
                'sales_name',
                $signature->name
            );

            $processor->setValue(
                'sales_position',
                $signature->position
            );

            $processor->setValue(
                'sales_division',
                $signature->division
            );
        }

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
            'QUOTATION_' .
            $id .
            '_' .
            time();

        $docxPath =
            $tempDir .
            '/' .
            $fileBase .
            '.docx';

        $processor->saveAs(
            $docxPath
        );

        $process = new Process([
            config('services.libreoffice.binary'),
            '--headless',
            '--convert-to',
            'pdf',
            '--outdir',
            $tempDir,
            $docxPath,
        ]);
        $process->setTimeout(120);

        try {
            $process->run();
        } catch (ProcessStartFailedException $exception) {
            Log::error('Unable to start LibreOffice for PDF conversion.', [
                'binary' => config('services.libreoffice.binary'),
                'exception' => $exception->getMessage(),
            ]);

            abort(
                500,
                'LibreOffice tidak ditemukan. Pastikan LibreOffice terpasang dan LIBREOFFICE_BINARY menunjuk ke executable yang benar.'
            );
        } catch (ProcessTimedOutException $exception) {
            Log::error('LibreOffice PDF conversion timed out.', [
                'inquiry_id' => $id,
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
        }

        if (! $process->isSuccessful()) {
            Log::error('LibreOffice PDF conversion failed.', [
                'inquiry_id' => $id,
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
            $tempDir .
            '/' .
            $fileBase .
            '.pdf';

        if (! file_exists($pdfPath)) {
            Log::error('LibreOffice reported success without creating a PDF.', [
                'inquiry_id' => $id,
                'pdf_path' => $pdfPath,
                'output' => $process->getOutput(),
                'error_output' => $process->getErrorOutput(),
            ]);

            abort(
                500,
                'PDF tidak berhasil dibuat. Periksa log aplikasi untuk detail.'
            );
        }

        return response()
            ->download(
                $pdfPath,
                $fileBase . '.pdf'
            )
            ->deleteFileAfterSend(true);
    }
}
