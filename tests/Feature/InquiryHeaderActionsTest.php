<?php

use App\Http\Middleware\EmployeeSession;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Core\EmployeeModel;
use App\Models\Sales\UserRoleModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

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

    Schema::create('core.customers', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('name')->nullable();
        $table->unsignedBigInteger('segmentation_id')->nullable();
    });

    Schema::create('core.segmentations', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('name');
    });

    Schema::create('core.employee_segmentations', function (Blueprint $table): void {
        $table->unsignedInteger('employee_id');
        $table->unsignedBigInteger('segmentation_id');
    });

    Schema::create('sales.user_role', function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedInteger('employee_id');
        $table->unsignedInteger('role_id');
        $table->string('sales_code', 2)->nullable();
        $table->unique(['employee_id', 'role_id']);
    });

    Schema::create('sales.inquiries', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('code')->unique();
        $table->date('date');
        $table->date('etd')->nullable();
        $table->unsignedInteger('pic')->nullable();
        $table->unsignedInteger('customer_id')->nullable();
        $table->unsignedBigInteger('segmentation_id')->nullable();
        $table->decimal('shipping_rate', 15, 2)->nullable();
        $table->text('note')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('sales.inquiry_code_counters', function (Blueprint $table): void {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('pic_id');
        $table->date('inquiry_date');
        $table->string('sales_code', 2);
        $table->unsignedInteger('next_sequence')->default(1);
        $table->unsignedInteger('reserved_sequence')->nullable();
        $table->string('reserved_code', 100)->nullable()->unique();
        $table->timestamps();
        $table->unique(['pic_id', 'inquiry_date']);
    });
});

afterEach(function () {
    Schema::dropIfExists('sales.inquiry_code_counters');
    Schema::dropIfExists('sales.inquiries');
    Schema::dropIfExists('sales.user_role');
    Schema::dropIfExists('core.employee_segmentations');
    Schema::dropIfExists('core.segmentations');
    Schema::dropIfExists('core.customers');
    Schema::dropIfExists('core.employees');
    DB::statement('DETACH DATABASE sales');
    DB::statement('DETACH DATABASE core');
});

function createAuthenticatedInquiryEditor(): EmployeeModel
{
    $employee = EmployeeModel::create([
        'id_karyawan' => 'EMP-001',
        'first_name' => 'Inquiry',
        'last_name' => 'Editor',
        'username' => 'inquiry.editor',
        'is_active' => true,
        'password' => Hash::make('secret-password'),
    ]);

    UserRoleModel::create([
        'employee_id' => $employee->id,
        'role_id' => 1,
    ]);
    UserRoleModel::create([
        'employee_id' => $employee->id,
        'role_id' => 2,
        'sales_code' => '01',
    ]);

    return $employee;
}

it('updates inquiry header fields through the existing update route', function () {
    $employee = createAuthenticatedInquiryEditor();
    $this->withoutMiddleware([EmployeeSession::class, HandleInertiaRequests::class])
        ->withSession([
            'employee_password_hash' => hash('sha256', $employee->password),
        ])->actingAs($employee);
    DB::table('sales.inquiries')->insert([
        'code' => 'INQ-OLD',
        'date' => '2026-10-01',
        'pic' => $employee->id,
        'note' => 'Old note',
    ]);
    DB::table('core.customers')->insert([
        'name' => 'Updated customer',
    ]);
    DB::table('core.segmentations')->insert([
        'id' => 12,
        'name' => 'Food Processing',
    ]);
    DB::table('core.employee_segmentations')->insert([
        'employee_id' => $employee->id,
        'segmentation_id' => 12,
    ]);
    $customerId = (int) DB::table('core.customers')->value('id');
    $inquiryId = (int) DB::table('sales.inquiries')->value('id');

    $this->put(route('inquiry.update', $inquiryId), [
        'code' => 'INQ-UPDATED',
        'date' => '2026-10-10',
        'etd' => '2026-10-20',
        'pic' => $employee->id,
        'customer_id' => $customerId,
        'segmentation_id' => 12,
        'shipping_rate' => 12500,
        'note' => 'Updated note',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $inquiry = DB::table('sales.inquiries')->where('id', $inquiryId)->first();

    expect($inquiry->code)->toBe('INQ-UPDATED')
        ->and($inquiry->date)->toBe('2026-10-10')
        ->and($inquiry->etd)->toBe('2026-10-20')
        ->and((int) $inquiry->pic)->toBe($employee->id)
        ->and((int) $inquiry->customer_id)->toBe($customerId)
        ->and((int) $inquiry->segmentation_id)->toBe(12)
        ->and((float) $inquiry->shipping_rate)->toBe(12500.0)
        ->and($inquiry->note)->toBe('Updated note');
});

it('creates an inquiry with its own segmentation independently of its customer segmentation', function () {
    $employee = createAuthenticatedInquiryEditor();
    $this->withoutMiddleware([EmployeeSession::class, HandleInertiaRequests::class])
        ->withSession([
            'employee_password_hash' => hash('sha256', $employee->password),
        ])->actingAs($employee);
    DB::table('core.segmentations')->insert([
        ['id' => 10, 'name' => 'Food Processing'],
        ['id' => 20, 'name' => 'HORECA'],
    ]);
    DB::table('core.employee_segmentations')->insert([
        'employee_id' => $employee->id,
        'segmentation_id' => 10,
    ]);
    $customerId = DB::table('core.customers')->insertGetId([
        'name' => 'HORECA customer',
        'segmentation_id' => 20,
    ]);

    $reservation = $this->postJson(route('inquiry.reserve-code'), [
        'date' => '2026-10-10',
        'pic' => $employee->id,
    ])->assertOk()->json('code');

    expect($reservation)->toBe('PN/SSN/1010.01.01/X/26');

    $this->postJson(route('inquiry.reserve-code'), [
        'date' => '2026-10-10',
        'pic' => $employee->id,
    ])->assertOk()->assertJsonPath('code', $reservation);

    $this->post(route('inquiry.store'), [
        'code' => $reservation,
        'date' => '2026-10-10',
        'pic' => $employee->id,
        'customer_id' => $customerId,
        'segmentation_id' => 10,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('sales.inquiries', [
        'code' => $reservation,
        'customer_id' => $customerId,
        'segmentation_id' => 10,
    ]);

    $nextReservation = $this->postJson(route('inquiry.reserve-code'), [
        'date' => '2026-10-10',
        'pic' => $employee->id,
    ])->assertOk()->json('code');

    expect($nextReservation)->toBe('PN/SSN/1010.01.02/X/26');

    $this->post(route('inquiry.store'), [
        'code' => $nextReservation,
        'date' => '2026-10-10',
        'pic' => $employee->id,
        'segmentation_id' => 10,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('sales.inquiries', [
        'code' => $nextReservation,
        'pic' => $employee->id,
    ]);

    $this->post(route('inquiry.store'), [
        'code' => $reservation,
        'date' => '2026-10-10',
        'pic' => $employee->id,
        'segmentation_id' => 10,
    ])->assertSessionHasErrors('code');
});

it('rejects an inquiry code already used by another inquiry', function () {
    $employee = createAuthenticatedInquiryEditor();
    $this->withoutMiddleware([EmployeeSession::class, HandleInertiaRequests::class])
        ->withSession([
            'employee_password_hash' => hash('sha256', $employee->password),
        ])->actingAs($employee);
    DB::table('sales.inquiries')->insert([
        ['code' => 'INQ-ONE', 'date' => '2026-10-01', 'pic' => $employee->id],
        ['code' => 'INQ-TWO', 'date' => '2026-10-02', 'pic' => $employee->id],
    ]);
    $inquiryId = (int) DB::table('sales.inquiries')
        ->where('code', 'INQ-ONE')
        ->value('id');

    $this->put(route('inquiry.update', $inquiryId), [
        'code' => 'INQ-TWO',
        'date' => '2026-10-10',
        'etd' => null,
        'pic' => $employee->id,
        'customer_id' => null,
        'shipping_rate' => null,
        'note' => null,
    ])->assertSessionHasErrors('code');

    expect(DB::table('sales.inquiries')->where('id', $inquiryId)->value('code'))
        ->toBe('INQ-ONE');
});

it('requires a configured Sales code for the selected Inquiry PIC', function () {
    $employee = createAuthenticatedInquiryEditor();
    $pic = EmployeeModel::create([
        'id_karyawan' => 'EMP-002',
        'first_name' => 'Sales',
        'last_name' => 'Without Code',
        'username' => 'sales.without.code',
        'is_active' => true,
        'password' => Hash::make('secret-password'),
    ]);
    UserRoleModel::create([
        'employee_id' => $pic->id,
        'role_id' => 2,
    ]);
    $this->withoutMiddleware([EmployeeSession::class, HandleInertiaRequests::class])
        ->withSession([
            'employee_password_hash' => hash('sha256', $employee->password),
        ])->actingAs($employee);
    DB::table('core.employee_segmentations')->insert([
        'employee_id' => $employee->id,
        'segmentation_id' => 10,
    ]);
    DB::table('core.segmentations')->insert([
        'id' => 10,
        'name' => 'Food Processing',
    ]);

    $this->postJson(route('inquiry.reserve-code'), [
        'date' => '2026-10-10',
        'pic' => $pic->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('pic');

    $this->assertDatabaseCount('sales.inquiries', 0);
});
