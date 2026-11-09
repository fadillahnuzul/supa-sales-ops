<?php

use App\Http\Controllers\RoleManagementController;
use App\Models\Core\EmployeeModel;
use App\Models\Sales\UserRoleModel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
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

    Schema::create('core.segmentations', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('name');
    });

    Schema::create('core.employee_segmentations', function (Blueprint $table): void {
        $table->unsignedInteger('employee_id');
        $table->unsignedInteger('segmentation_id');
    });

    Schema::create('sales.roles', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('name');
    });

    Schema::create('sales.user_role', function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedInteger('employee_id');
        $table->unsignedInteger('role_id');
        $table->unique(['employee_id', 'role_id']);
    });
});

afterEach(function () {
    Schema::dropIfExists('sales.user_role');
    Schema::dropIfExists('sales.roles');
    Schema::dropIfExists('core.employee_segmentations');
    Schema::dropIfExists('core.segmentations');
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

it('redirects expired Inertia requests to login with a session-expired notice', function () {
    $employee = createEmployeeForAccessTest(1);
    Auth::guard('web')->login($employee);
    $request = Request::create('/logout', 'POST');
    $request->headers->set('X-Inertia', 'true');
    $request->setLaravelSession(app('session')->driver());
    $request->session()->start();

    $response = app(ExceptionHandler::class)->render(
        $request,
        new TokenMismatchException
    );

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toBe(url('/login?session_expired=1'));
    $this->assertGuest('web');
});

it('keeps expired API requests as JSON 419 responses', function () {
    $request = Request::create('/api/example', 'POST');

    $response = app(ExceptionHandler::class)->render(
        $request,
        new TokenMismatchException
    );

    expect($response->getStatusCode())->toBe(419)
        ->and($response->headers->get('Content-Type'))->toContain('application/json');
});

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

it('supports multiple roles for an employee and shares all role ids', function () {
    $employee = createEmployeeForAccessTest(2);
    UserRoleModel::create([
        'employee_id' => $employee->id,
        'role_id' => 3,
    ]);

    $this->withSession([
        'employee_password_hash' => hash('sha256', $employee->password),
    ])->actingAs($employee)->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.role_id', 2)
            ->where('auth.user.role_ids', [2, 3]));

    expect($employee->roleIds())->toBe([2, 3])
        ->and($employee->canManageRoleManagement())->toBeTrue();
});

it('saves and updates several roles for one employee from role management', function () {
    $manager = createEmployeeForAccessTest(3);
    $employee = EmployeeModel::create([
        'id_karyawan' => 'EMP-002',
        'first_name' => 'Sales',
        'last_name' => 'Employee',
        'username' => 'sales.employee',
        'is_active' => true,
        'password' => Hash::make('secret-password'),
    ]);

    DB::table('sales.roles')->insert([
        ['id' => 2, 'name' => 'Sales'],
        ['id' => 3, 'name' => 'Sales Coordinator'],
        ['id' => 4, 'name' => 'Sales Manager'],
    ]);
    $this->withSession([
        'employee_password_hash' => hash('sha256', $manager->password),
    ])->actingAs($manager);

    $this->post(route('role-management.store'), [
        'employee_id' => $employee->id,
        'role_ids' => [2, 3],
        'segmentation_ids' => [],
    ])->assertRedirect(route('role-management'));

    expect($employee->fresh()->roleIds())->toBe([2, 3]);

    $this->get(route('role-management'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('InquiryRoleManagement')
            ->where('users.1.role_ids', [2, 3])
            ->where('users.1.role_names', ['Sales', 'Sales Coordinator']));

    $this->put(route('role-management.update', $employee->id), [
        'employee_id' => $employee->id,
        'role_ids' => [4],
        'segmentation_ids' => [],
    ])->assertRedirect(route('role-management'));

    expect($employee->fresh()->roleIds())->toBe([4]);
});
