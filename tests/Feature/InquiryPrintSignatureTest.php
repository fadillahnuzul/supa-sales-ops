<?php

use App\Http\Middleware\EmployeeSession;
use App\Models\Core\EmployeeModel;
use App\Services\InquiryPrintService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use ZipArchive;

beforeEach(function (): void {
    DB::statement("ATTACH DATABASE ':memory:' AS core");
    DB::statement("ATTACH DATABASE ':memory:' AS sales");

    Schema::create('core.employees', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('id_karyawan')->unique();
        $table->string('first_name');
        $table->string('last_name');
        $table->string('username')->unique();
        $table->boolean('is_active')->default(true);
        $table->string('password');
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('core.positions', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('name');
    });

    Schema::create('core.employee_positions', function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedInteger('employee_id');
        $table->unsignedInteger('position_id');
    });

    Schema::create('core.employee_segmentations', function (Blueprint $table): void {
        $table->unsignedInteger('employee_id');
        $table->unsignedInteger('segmentation_id');
    });

    Schema::create('core.segmentations', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('name');
    });

    Schema::create('sales.roles', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('name');
    });

    Schema::create('sales.user_role', function (Blueprint $table): void {
        $table->unsignedInteger('employee_id')->primary();
        $table->unsignedInteger('role_id');
    });

    Schema::create('sales.inquiries', function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedInteger('pic')->nullable();
        $table->softDeletes();
    });

    Schema::create('sales.sales_signatures', function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedInteger('user_id')->nullable();
        $table->string('name');
        $table->string('division')->nullable();
        $table->string('position')->nullable();
        $table->string('signature_path');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('sales.print_templates', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('name');
        $table->string('code');
        $table->string('division')->nullable();
        $table->string('template_type')->nullable();
        $table->string('file_path');
        $table->boolean('is_default')->default(false);
        $table->boolean('is_active')->default(true);
        $table->unsignedInteger('created_by')->nullable();
        $table->timestamps();
    });

    DB::table('sales.roles')->insert([
        ['id' => 2, 'name' => 'Sales'],
        ['id' => 3, 'name' => 'Sales Coordinator'],
        ['id' => 4, 'name' => 'Sales Manager'],
    ]);
});

afterEach(function (): void {
    Schema::dropIfExists('sales.print_templates');
    Schema::dropIfExists('sales.sales_signatures');
    Schema::dropIfExists('sales.inquiries');
    Schema::dropIfExists('sales.user_role');
    Schema::dropIfExists('sales.roles');
    Schema::dropIfExists('core.segmentations');
    Schema::dropIfExists('core.employee_segmentations');
    Schema::dropIfExists('core.employee_positions');
    Schema::dropIfExists('core.positions');
    Schema::dropIfExists('core.employees');
    DB::statement('DETACH DATABASE sales');
    DB::statement('DETACH DATABASE core');
});

function createInquiryPrintEmployee(string $firstName, string $lastName): EmployeeModel
{
    return EmployeeModel::create([
        'id_karyawan' => 'EMP-'.$firstName.'-'.$lastName,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'username' => strtolower($firstName.'.'.$lastName),
        'is_active' => true,
        'password' => Hash::make('secret-password'),
    ]);
}

function assignInquiryPrintRole(EmployeeModel $employee, int $roleId): void
{
    DB::table('sales.user_role')->insert([
        'employee_id' => $employee->id,
        'role_id' => $roleId,
    ]);
}

function actAsInquiryPrintEmployee(EmployeeModel $employee): void
{
    test()->withoutMiddleware([EmployeeSession::class])
        ->withSession([
            'employee_password_hash' => hash('sha256', $employee->password),
        ])->actingAs($employee);
}

it('lists role 2 3 4 employees and inquiry PICs without requiring signatures', function (): void {
    $roleTwo = createInquiryPrintEmployee('Sales', 'Two');
    $roleThreePic = createInquiryPrintEmployee('Sales', 'Three');
    $roleFour = createInquiryPrintEmployee('Sales', 'Four');
    $picWithoutRole = createInquiryPrintEmployee('PIC', 'NoRole');
    $outsider = createInquiryPrintEmployee('Other', 'Employee');

    assignInquiryPrintRole($roleTwo, 2);
    assignInquiryPrintRole($roleThreePic, 3);
    assignInquiryPrintRole($roleFour, 4);
    DB::table('sales.inquiries')->insert([
        ['pic' => $roleThreePic->id],
        ['pic' => $picWithoutRole->id],
    ]);
    actAsInquiryPrintEmployee($outsider);

    $this->get('/inquiries/print/signatures')
        ->assertOk()
        ->assertJsonCount(4)
        ->assertJsonFragment([
            'id' => null,
            'user_id' => $roleThreePic->id,
            'name' => 'Sales Three',
        ])
        ->assertJsonFragment([
            'id' => null,
            'user_id' => $picWithoutRole->id,
            'name' => 'PIC NoRole',
        ])
        ->assertJsonMissing([
            'user_id' => $outsider->id,
            'name' => 'Other Employee',
        ]);
});

it('uses only the saved signature position instead of the employee position', function (): void {
    $employee = createInquiryPrintEmployee('Nina', 'Sales');
    assignInquiryPrintRole($employee, 3);
    DB::table('sales.inquiries')->insert(['pic' => $employee->id]);

    DB::table('core.positions')->insert([
        ['id' => 1, 'name' => 'Regional Sales'],
        ['id' => 2, 'name' => 'Secondary Position'],
    ]);
    DB::table('core.employee_positions')->insert([
        ['employee_id' => $employee->id, 'position_id' => 1],
        ['employee_id' => $employee->id, 'position_id' => 2],
    ]);

    Storage::fake('local');
    $templatePath = Storage::disk('local')->path('private/quotation.docx');
    $word = new PhpWord;
    $section = $word->addSection();
    $section->addText('${sales_name}');
    $section->addText('${sales_position}');
    $section->addText('${sales_division}');
    $section->addText('${quotation_date}');
    $section->addText('${customer_name}');
    $section->addText('${customer_address}');
    $section->addText('${inquiry_code}');
    $section->addText('${signature}');
    $table = $section->addTable();
    $table->addRow();
    $table->addCell()->addText('${no}');

    $writer = WordIOFactory::createWriter($word, 'Word2007');
    $templateTemporaryPath = tempnam(sys_get_temp_dir(), 'quotation-template-');
    $writer->save($templateTemporaryPath);
    Storage::disk('local')->put('private/quotation.docx', file_get_contents($templateTemporaryPath));
    unlink($templateTemporaryPath);

    DB::table('sales.print_templates')->insert([
        'id' => 1,
        'name' => 'Quotation',
        'code' => 'QUOTATION',
        'file_path' => 'private/quotation.docx',
        'is_default' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $printService = Mockery::mock(InquiryPrintService::class);
    $printService->shouldReceive('getFullInquiry')
        ->twice()
        ->with(1)
        ->andReturn([
            'inquiry' => (object) [
                'pic' => $employee->id,
                'pic_name' => 'Nina Sales',
                'code' => 'INQ-1',
                'date' => '2025-02-15',
                'customer_name' => 'Example Customer',
                'customer_address' => 'Example Address',
            ],
            'details' => collect(),
        ]);
    $this->app->instance(InquiryPrintService::class, $printService);
    actAsInquiryPrintEmployee($employee);

    $response = $this->post('/inquiries/print/1/word', [
        'template_id' => 1,
        'signature_user_id' => $employee->id,
    ]);

    $response->assertOk()
        ->assertHeader(
            'content-disposition',
            'attachment; filename=QUOTATION_INQ-1_15-02-2025.docx'
        );
    $outputPath = $response->baseResponse->getFile()->getPathname();
    $archive = new ZipArchive;
    expect($archive->open($outputPath))->toBeTrue();
    $document = $archive->getFromName('word/document.xml');
    $archive->close();
    unlink($outputPath);

    expect($document)
        ->toContain('Nina Sales')
        ->not->toContain('Regional Sales')
        ->not->toContain('${sales_name}')
        ->not->toContain('${sales_position}');

    $signaturePath = UploadedFile::fake()
        ->image('nina-signature.png')
        ->store('private/sales-signatures', 'local');
    DB::table('sales.sales_signatures')->insert([
        'user_id' => $employee->id,
        'name' => 'Nina Sales',
        'division' => 'DTT',
        'position' => 'Sales Coordinator',
        'signature_path' => $signaturePath,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $signatureResponse = $this->post('/inquiries/print/1/word', [
        'template_id' => 1,
        'signature_user_id' => $employee->id,
    ]);

    $signatureResponse->assertOk();
    $signatureOutputPath = $signatureResponse->baseResponse->getFile()->getPathname();
    $signatureArchive = new ZipArchive;
    expect($signatureArchive->open($signatureOutputPath))->toBeTrue();
    $signatureDocument = $signatureArchive->getFromName('word/document.xml');
    $signatureArchive->close();
    unlink($signatureOutputPath);

    expect($signatureDocument)
        ->toContain('Sales Coordinator')
        ->not->toContain('Regional Sales');
});
