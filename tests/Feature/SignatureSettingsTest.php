<?php

use App\Http\Middleware\EmployeeSession;
use App\Models\Core\EmployeeModel;
use App\Models\Sales\UserRoleModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
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
});

afterEach(function (): void {
    Schema::dropIfExists('sales.sales_signatures');
    Schema::dropIfExists('sales.user_role');
    Schema::dropIfExists('sales.roles');
    Schema::dropIfExists('core.segmentations');
    Schema::dropIfExists('core.employee_segmentations');
    Schema::dropIfExists('core.employees');
    DB::statement('DETACH DATABASE sales');
    DB::statement('DETACH DATABASE core');
});

it('renames the legacy singular signature table to the plural table without losing its files', function (): void {
    Schema::dropIfExists('sales.sales_signature');
    Schema::dropIfExists('sales.sales_signatures');
    Schema::create('sales.sales_signature', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('signature_path');
    });
    DB::table('sales.sales_signature')->insert([
        'signature_path' => 'private/signature.png',
    ]);

    $migration = require database_path('migrations/2026_10_30_030647_rename_sales_signatures_table.php');
    $migration->up();

    expect(Schema::hasTable('sales.sales_signature'))->toBeFalse()
        ->and(DB::table('sales.sales_signatures')->value('signature_path'))
        ->toBe('private/signature.png');
});

it('keeps the existing plural signature table unchanged when running the rename migration', function (): void {
    DB::table('sales.sales_signatures')->insert([
        'name' => 'Existing Sales',
        'signature_path' => 'private/existing-signature.png',
    ]);

    $migration = require database_path('migrations/2026_10_30_030647_rename_sales_signatures_table.php');
    $migration->up();

    expect(Schema::hasTable('sales.sales_signatures'))->toBeTrue()
        ->and(DB::table('sales.sales_signatures')->value('signature_path'))
        ->toBe('private/existing-signature.png');
});

function createEmployeeForSignatureSettings(?int $roleId = null): EmployeeModel
{
    $employee = EmployeeModel::create([
        'id_karyawan' => 'EMP-SIGNATURE-001',
        'first_name' => 'Signature',
        'last_name' => 'Owner',
        'username' => 'signature.owner',
        'is_active' => true,
        'password' => Hash::make('secret-password'),
    ]);

    if ($roleId !== null) {
        DB::table('sales.roles')->insert([
            'id' => $roleId,
            'name' => 'Sales Manager',
        ]);
        UserRoleModel::create([
            'employee_id' => $employee->id,
            'role_id' => $roleId,
        ]);
    }

    return $employee;
}

function actAsEmployeeForSignatureSettings(EmployeeModel $employee): void
{
    test()->withoutMiddleware([EmployeeSession::class])
        ->withSession([
            'employee_password_hash' => hash('sha256', $employee->password),
        ])->actingAs($employee);
}

it('allows a role-assigned user to upload and replace their own signature', function (): void {
    $employee = createEmployeeForSignatureSettings(2);
    actAsEmployeeForSignatureSettings($employee);
    Storage::fake('local');

    $this->get(route('signature-settings'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('SignatureSettings')
            ->where('signature', null));

    $this->post(route('signature-settings.store'), [
        'signature' => UploadedFile::fake()->image('signature.png'),
    ])->assertRedirect(route('signature-settings'));

    $firstSignature = DB::table('sales.sales_signatures')->first();

    expect($firstSignature->name)->toBe('Signature Owner')
        ->and($firstSignature->position)->toBe('Sales Manager')
        ->and((bool) $firstSignature->is_active)->toBeTrue();

    Storage::disk('local')->assertExists($firstSignature->signature_path);

    $this->post(route('signature-settings.store'), [
        'signature' => UploadedFile::fake()->image('replacement.png'),
    ])->assertRedirect(route('signature-settings'));

    $activeSignature = DB::table('sales.sales_signatures')
        ->where('user_id', $employee->id)
        ->where('is_active', true)
        ->first();

    expect(DB::table('sales.sales_signatures')->where('user_id', $employee->id)->count())
        ->toBe(2)
        ->and(DB::table('sales.sales_signatures')->where('user_id', $employee->id)->where('is_active', true)->count())
        ->toBe(1)
        ->and($activeSignature->name)->toBe('Signature Owner');

    $this->get(route('signature-settings'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('SignatureSettings')
            ->where('signature.name', 'Signature Owner')
            ->where('signature.position', 'Sales Manager')
            ->where('signature.imageUrl', route('signature-settings.file', $activeSignature->id)));
});

it('forbids signature settings for users without an assigned role', function (): void {
    $employee = createEmployeeForSignatureSettings();
    actAsEmployeeForSignatureSettings($employee);
    Storage::fake('local');

    $this->get(route('signature-settings'))->assertForbidden();

    $this->post(route('signature-settings.store'), [
        'signature' => UploadedFile::fake()->image('signature.png'),
    ])->assertForbidden();

    expect(DB::table('sales.sales_signatures')->count())->toBe(0);
});

it('updates active sales signature details and replaces the file when one is uploaded', function (): void {
    $employee = createEmployeeForSignatureSettings(2);
    actAsEmployeeForSignatureSettings($employee);
    Storage::fake('local');
    $oldPath = UploadedFile::fake()
        ->image('old-signature.png')
        ->store('private/sales-signatures', 'local');
    $signatureId = DB::table('sales.sales_signatures')->insertGetId([
        'user_id' => $employee->id,
        'name' => 'Old Name',
        'division' => 'DTT',
        'position' => 'Old Position',
        'signature_path' => $oldPath,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->post("/inquiries/print/signatures/{$signatureId}", [
        '_method' => 'PATCH',
        'name' => 'Updated Name',
        'division' => 'GTT',
        'position' => 'Sales Manager',
        'signature' => UploadedFile::fake()->image('new-signature.png'),
    ])
        ->assertOk()
        ->assertJsonPath('id', $signatureId)
        ->assertJsonPath('user_id', $employee->id)
        ->assertJsonPath('name', 'Updated Name')
        ->assertJsonPath('division', 'GTT')
        ->assertJsonPath('position', 'Sales Manager');

    $updatedSignature = DB::table('sales.sales_signatures')
        ->where('id', $signatureId)
        ->first();

    expect($updatedSignature->signature_path)->not->toBe($oldPath)
        ->and($updatedSignature->name)->toBe('Updated Name');
    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($updatedSignature->signature_path);
});

it('keeps the active signature file when only its details are edited', function (): void {
    $employee = createEmployeeForSignatureSettings(2);
    actAsEmployeeForSignatureSettings($employee);
    Storage::fake('local');
    $path = UploadedFile::fake()
        ->image('signature.png')
        ->store('private/sales-signatures', 'local');
    $signatureId = DB::table('sales.sales_signatures')->insertGetId([
        'user_id' => $employee->id,
        'name' => 'Sales',
        'signature_path' => $path,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->post("/inquiries/print/signatures/{$signatureId}", [
        '_method' => 'PATCH',
        'name' => 'Sales Updated',
        'division' => '',
        'position' => '',
    ])
        ->assertOk()
        ->assertJsonPath('name', 'Sales Updated');

    expect(DB::table('sales.sales_signatures')->where('id', $signatureId)->value('signature_path'))
        ->toBe($path);
    Storage::disk('local')->assertExists($path);
});
