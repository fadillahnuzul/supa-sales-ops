<?php

use App\Http\Controllers\RoleManagementController;
use App\Models\Core\EmployeeModel;
use App\Models\Sales\UserRoleModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

beforeEach(function () {
    DB::statement("ATTACH DATABASE ':memory:' AS core");
    DB::statement("ATTACH DATABASE ':memory:' AS sales");

    Schema::create('core.employees', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('id_karyawan');
        $table->string('first_name');
        $table->string('last_name');
        $table->string('email')->nullable();
        $table->string('username')->nullable();
        $table->boolean('is_active')->default(true);
        $table->string('password')->nullable();
        $table->string('profile_photo_path')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('sales.user_role', function (Blueprint $table): void {
        $table->unsignedInteger('employee_id')->primary();
        $table->unsignedInteger('role_id');
    });
});

afterEach(function () {
    Schema::dropIfExists('sales.user_role');
    Schema::dropIfExists('core.employees');
    DB::statement('DETACH DATABASE sales');
    DB::statement('DETACH DATABASE core');
});

function createEmployeeForAccessTest(?int $roleId = null): EmployeeModel
{
    $employee = EmployeeModel::create([
        'id_karyawan' => 'EMP-001',
        'first_name' => 'Test',
        'last_name' => 'Employee',
        'email' => 'test@example.com',
        'username' => 'test.employee',
        'is_active' => true,
        'password' => Hash::make('secret-password'),
    ]);

    if ($roleId !== null) {
        UserRoleModel::create([
            'employee_id' => $employee->id,
            'role_id' => $roleId,
        ]);
    }

    return $employee;
}

it('allows employees with an assigned role to log in', function (int $roleId) {
    $employee = createEmployeeForAccessTest($roleId);

    $this->post('/login', [
        'username' => 'test.employee',
        'password' => 'secret-password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($employee);
})->with([1, 2, 3]);

it('explains that an employee without an assigned role has no access', function () {
    createEmployeeForAccessTest();

    $this->post('/login', [
        'username' => 'test.employee',
        'password' => 'secret-password',
    ])->assertSessionHasErrors([
        'username' => 'Oops! Kamu belum punya akses. Hubungi Digicom atau Sales Coordinator ya!',
    ]);

    $this->assertGuest();
});

it('removes a session when the employee loses system access', function () {
    $employee = createEmployeeForAccessTest(1);

    $this->post('/login', [
        'username' => 'test.employee',
        'password' => 'secret-password',
    ])->assertRedirect('/dashboard');

    UserRoleModel::query()->where('employee_id', $employee->id)->delete();
    Auth::forgetGuards();

    $this->get('/dashboard')
        ->assertRedirect('/login')
        ->assertSessionHasErrors([
            'username' => 'Oops! Kamu belum punya akses. Hubungi Digicom atau Sales Coordinator ya!',
        ]);

    $this->assertGuest();
});

it('allows system admins and sales coordinators to use role management', function (int $roleId) {
    $employee = createEmployeeForAccessTest($roleId);

    $this->mock(
        RoleManagementController::class,
        function (MockInterface $mock): void {
            $mock->shouldReceive('index')->once()->andReturn(response('ok'));
        },
    );

    $this->withSession([
        'employee_password_hash' => hash('sha256', $employee->password),
    ])->actingAs($employee)->get('/role-management')->assertOk();
})->with([1, 3]);

it('forbids sales from using role management', function () {
    $employee = createEmployeeForAccessTest(2);

    $this->mock(
        RoleManagementController::class,
        function (MockInterface $mock): void {
            $mock->shouldNotReceive('index');
        },
    );

    $this->withSession([
        'employee_password_hash' => hash('sha256', $employee->password),
    ])->actingAs($employee)->get('/role-management')->assertForbidden();
});

it('shares the assigned role id with authenticated Inertia pages', function () {
    $employee = createEmployeeForAccessTest(2);

    $this->withSession([
        'employee_password_hash' => hash('sha256', $employee->password),
    ])->actingAs($employee)->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('auth.user.role_id', 2));
});
