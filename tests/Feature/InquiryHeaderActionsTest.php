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
    });

    Schema::create('sales.user_role', function (Blueprint $table): void {
        $table->unsignedInteger('employee_id')->primary();
        $table->unsignedInteger('role_id');
    });

    Schema::create('sales.inquiries', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('code')->unique();
        $table->date('date');
        $table->date('etd')->nullable();
        $table->unsignedInteger('pic')->nullable();
        $table->unsignedInteger('customer_id')->nullable();
        $table->decimal('shipping_rate', 15, 2)->nullable();
        $table->text('note')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

afterEach(function () {
    Schema::dropIfExists('sales.inquiries');
    Schema::dropIfExists('sales.user_role');
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
    $customerId = (int) DB::table('core.customers')->value('id');
    $inquiryId = (int) DB::table('sales.inquiries')->value('id');

    $this->put(route('inquiry.update', $inquiryId), [
        'code' => 'INQ-UPDATED',
        'date' => '2026-10-10',
        'etd' => '2026-10-20',
        'pic' => $employee->id,
        'customer_id' => $customerId,
        'shipping_rate' => 12500,
        'note' => 'Updated note',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $inquiry = DB::table('sales.inquiries')->where('id', $inquiryId)->first();

    expect($inquiry->code)->toBe('INQ-UPDATED')
        ->and($inquiry->date)->toBe('2026-10-10')
        ->and($inquiry->etd)->toBe('2026-10-20')
        ->and((int) $inquiry->pic)->toBe($employee->id)
        ->and((int) $inquiry->customer_id)->toBe($customerId)
        ->and((float) $inquiry->shipping_rate)->toBe(12500.0)
        ->and($inquiry->note)->toBe('Updated note');
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
