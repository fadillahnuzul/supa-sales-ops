<?php

use App\Http\Middleware\EmployeeSession;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Core\EmployeeModel;
use App\Models\Sales\UserRoleModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    DB::statement("ATTACH DATABASE ':memory:' AS core");
    DB::statement("ATTACH DATABASE ':memory:' AS sales");

    Schema::create('core.employees', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('id_karyawan');
        $table->string('first_name');
        $table->string('last_name');
        $table->string('username')->nullable();
        $table->boolean('is_active')->default(true);
        $table->string('password')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('core.employee_segmentations', function (Blueprint $table): void {
        $table->unsignedInteger('employee_id');
        $table->unsignedInteger('segmentation_id');
    });

    Schema::create('core.customers', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('name')->nullable();
    });

    Schema::create('sales.user_role', function (Blueprint $table): void {
        $table->unsignedInteger('employee_id')->primary();
        $table->unsignedInteger('role_id');
    });

    Schema::create('sales.inquiries', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('code')->unique();
        $table->date('date');
        $table->unsignedInteger('pic')->nullable();
        $table->unsignedInteger('customer_id')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('sales.job_orders', function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedInteger('inquiry_id')->unique();
        $table->date('date');
        $table->string('so_no')->unique();
        $table->string('po_no');
        $table->unsignedInteger('customer_id')->nullable();
        $table->string('customer_no')->nullable();
        $table->string('customer_name');
        $table->text('customer_address')->nullable();
        $table->unsignedInteger('pic_id')->nullable();
        $table->string('pic_name')->nullable();
        $table->date('shipping_date')->nullable();
        $table->string('status');
        $table->unsignedInteger('created_by');
        $table->unsignedInteger('updated_by')->nullable();
        $table->unsignedInteger('checked_by')->nullable();
        $table->timestamp('checked_at')->nullable();
        $table->unsignedInteger('approved_by')->nullable();
        $table->timestamp('approved_at')->nullable();
        $table->timestamps();
    });

    Schema::create('sales.sales_signatures', function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedInteger('user_id')->nullable();
        $table->string('name', 150);
        $table->string('division', 50)->nullable();
        $table->string('position', 150)->nullable();
        $table->text('signature_path');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('sales.job_order_details', function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedInteger('job_order_id');
        $table->unsignedInteger('inquiry_detail_id')->nullable();
        $table->decimal('kg', 15, 3)->default(0);
        $table->string('product_code')->nullable();
        $table->string('product_name');
        $table->string('packing')->nullable();
        $table->text('note')->nullable();
        $table->unsignedInteger('sort_order')->default(0);
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('sales.job_order_details');
    Schema::dropIfExists('sales.sales_signatures');
    Schema::dropIfExists('sales.job_orders');
    Schema::dropIfExists('sales.inquiries');
    Schema::dropIfExists('sales.user_role');
    Schema::dropIfExists('core.customers');
    Schema::dropIfExists('core.employee_segmentations');
    Schema::dropIfExists('core.employees');
    DB::statement('DETACH DATABASE sales');
    DB::statement('DETACH DATABASE core');
});

it('lists unconverted inquiries alongside job orders assigned to the employee', function () {
    $employee = EmployeeModel::create([
        'id_karyawan' => 'EMP-JO-001',
        'first_name' => 'Job',
        'last_name' => 'Order Admin',
        'username' => 'job.order.admin',
        'is_active' => true,
        'password' => Hash::make('secret-password'),
    ]);

    UserRoleModel::create([
        'employee_id' => $employee->id,
        'role_id' => 5,
    ]);

    $this->withoutMiddleware([EmployeeSession::class, HandleInertiaRequests::class])
        ->withSession([
            'employee_password_hash' => hash('sha256', $employee->password),
        ])->actingAs($employee);

    $customerId = DB::table('core.customers')->insertGetId([
        'name' => 'Customer JO',
    ]);
    $date = now()->toDateString();
    $unconvertedInquiryId = DB::table('sales.inquiries')->insertGetId([
        'code' => 'INQ-OPEN',
        'date' => $date,
        'pic' => $employee->id,
        'customer_id' => $customerId,
    ]);
    $convertedInquiryId = DB::table('sales.inquiries')->insertGetId([
        'code' => 'INQ-CONVERTED',
        'date' => $date,
        'pic' => $employee->id,
        'customer_id' => $customerId,
    ]);
    DB::table('sales.inquiries')->insert([
        'code' => 'INQ-OTHER-PIC',
        'date' => $date,
        'pic' => $employee->id + 1,
    ]);
    $jobOrderId = DB::table('sales.job_orders')->insertGetId([
        'inquiry_id' => $convertedInquiryId,
        'date' => $date,
        'so_no' => 'SO-001',
        'po_no' => 'PO-001',
        'customer_name' => 'Customer JO',
        'status' => 'pending',
        'created_by' => $employee->id,
    ]);

    $response = $this->get(route('job-orders.list', [
        'start_date' => $date,
        'end_date' => $date,
    ]));

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'inquiry_id' => $unconvertedInquiryId,
            'job_order_id' => null,
            'so_no' => null,
            'po_no' => null,
        ])
        ->assertJsonFragment([
            'inquiry_id' => $convertedInquiryId,
            'job_order_id' => $jobOrderId,
            'so_no' => 'SO-001',
            'po_no' => 'PO-001',
        ]);
});

it('creates a job order from an inquiry without a database connection error', function () {
    $employee = EmployeeModel::create([
        'id_karyawan' => 'EMP-JO-002',
        'first_name' => 'Job',
        'last_name' => 'Order Creator',
        'username' => 'job.order.creator',
        'is_active' => true,
        'password' => Hash::make('secret-password'),
    ]);

    UserRoleModel::create([
        'employee_id' => $employee->id,
        'role_id' => 1,
    ]);

    $this->withoutMiddleware([EmployeeSession::class, HandleInertiaRequests::class])
        ->withSession([
            'employee_password_hash' => hash('sha256', $employee->password),
        ])->actingAs($employee);

    $inquiryId = DB::table('sales.inquiries')->insertGetId([
        'code' => 'INQ-CREATE-JO',
        'date' => now()->toDateString(),
        'pic' => $employee->id,
    ]);

    $response = $this->postJson(route('job-orders.store'), [
        'inquiry_id' => $inquiryId,
        'date' => now()->toDateString(),
        'so_no' => 'SO-CREATE-001',
        'po_no' => 'PO-CREATE-001',
        'customer_name' => 'Customer JO',
        'details' => [
            [
                'kg' => '125.25',
                'product_name' => 'Soybean',
            ],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('message', 'JO berhasil dibuat');

    $jobOrderId = $response->json('id');

    expect(DB::table('sales.job_orders')->where('id', $jobOrderId)->value('so_no'))
        ->toBe('SO-CREATE-001')
        ->and((float) DB::table('sales.job_order_details')->where('job_order_id', $jobOrderId)->value('kg'))
        ->toBe(125.25)
        ->and(DB::table('sales.job_order_details')->where('job_order_id', $jobOrderId)->value('product_name'))
        ->toBe('Soybean');
});

it('returns job order details to an authorized approver', function () {
    $employee = EmployeeModel::create([
        'id_karyawan' => 'EMP-JO-003',
        'first_name' => 'Job',
        'last_name' => 'Order Approver',
        'username' => 'job.order.approver',
        'is_active' => true,
        'password' => Hash::make('secret-password'),
    ]);

    UserRoleModel::create([
        'employee_id' => $employee->id,
        'role_id' => 5,
    ]);

    $this->withoutMiddleware([EmployeeSession::class, HandleInertiaRequests::class])
        ->withSession([
            'employee_password_hash' => hash('sha256', $employee->password),
        ])->actingAs($employee);

    $inquiryId = DB::table('sales.inquiries')->insertGetId([
        'code' => 'INQ-APPROVER-JO',
        'date' => now()->toDateString(),
        'pic' => $employee->id,
    ]);
    $jobOrderId = DB::table('sales.job_orders')->insertGetId([
        'inquiry_id' => $inquiryId,
        'date' => now()->toDateString(),
        'so_no' => 'SO-APPROVER-001',
        'po_no' => 'PO-APPROVER-001',
        'customer_name' => 'Customer JO',
        'status' => 'pending',
        'created_by' => $employee->id,
    ]);
    DB::table('sales.job_order_details')->insert([
        'job_order_id' => $jobOrderId,
        'kg' => 125,
        'product_name' => 'Soybean',
        'sort_order' => 0,
    ]);

    $response = $this->getJson('/job-orders/'.$jobOrderId);

    $response->assertOk()
        ->assertJsonPath('id', $jobOrderId)
        ->assertJsonPath('so_no', 'SO-APPROVER-001')
        ->assertJsonPath('details.0.product_name', 'Soybean');
});

it('prints the creator, checker, and approver names with signature spaces', function () {
    $creator = EmployeeModel::create([
        'id_karyawan' => 'EMP-JO-PRINT-001',
        'first_name' => 'Creator',
        'last_name' => 'Name',
        'username' => 'job.order.print.creator',
        'is_active' => true,
        'password' => Hash::make('secret-password'),
    ]);
    $checker = EmployeeModel::create([
        'id_karyawan' => 'EMP-JO-PRINT-002',
        'first_name' => 'Checker',
        'last_name' => 'Name',
        'username' => 'job.order.print.checker',
        'is_active' => true,
        'password' => Hash::make('secret-password'),
    ]);
    $approver = EmployeeModel::create([
        'id_karyawan' => 'EMP-JO-PRINT-003',
        'first_name' => 'Approver',
        'last_name' => 'Name',
        'username' => 'job.order.print.approver',
        'is_active' => true,
        'password' => Hash::make('secret-password'),
    ]);

    UserRoleModel::create([
        'employee_id' => $creator->id,
        'role_id' => 1,
    ]);

    $this->withoutMiddleware([EmployeeSession::class, HandleInertiaRequests::class])
        ->withSession([
            'employee_password_hash' => hash('sha256', $creator->password),
        ])->actingAs($creator);
    Storage::fake('local');

    $inquiryId = DB::table('sales.inquiries')->insertGetId([
        'code' => 'INQ-PRINT-SIGNERS',
        'date' => now()->toDateString(),
        'pic' => $creator->id,
    ]);
    $jobOrderId = DB::table('sales.job_orders')->insertGetId([
        'inquiry_id' => $inquiryId,
        'date' => now()->toDateString(),
        'so_no' => 'SO-PRINT-SIGNERS',
        'po_no' => 'PO-PRINT-SIGNERS',
        'customer_name' => 'Customer JO',
        'status' => 'approved',
        'created_by' => $creator->id,
        'checked_by' => $checker->id,
        'approved_by' => $approver->id,
    ]);
    $signatureIds = [];
    foreach ([$creator, $checker, $approver] as $employee) {
        $path = UploadedFile::fake()
            ->image('signature-'.$employee->id.'.png')
            ->store('private/sales-signatures', 'local');
        $signatureIds[] = DB::table('sales.sales_signatures')->insertGetId([
            'user_id' => $employee->id,
            'name' => $employee->full_name,
            'signature_path' => $path,
            'is_active' => true,
        ]);
    }

    $response = $this->get('/job-orders/'.$jobOrderId.'/print');

    $response->assertOk()
        ->assertSee('Creator Name')
        ->assertSee('Checker Name')
        ->assertSee('Approver Name')
        ->assertSee('Dibuat Oleh')
        ->assertSee('Diperiksa Oleh')
        ->assertSee('Disetujui Oleh')
        ->assertSee('signature-space')
        ->assertSee(route('signature-settings.file', $signatureIds[0]), false)
        ->assertSee(route('signature-settings.file', $signatureIds[1]), false)
        ->assertSee(route('signature-settings.file', $signatureIds[2]), false);

    $this->get(route('signature-settings.file', $signatureIds[1]))->assertOk();
});
