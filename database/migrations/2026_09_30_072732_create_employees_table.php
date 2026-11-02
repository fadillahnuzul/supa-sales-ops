<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    if (!Schema::hasTable('core.employees')) {
        Schema::create('core.employees', function (Blueprint $table) {
            $table->increments('id');

            $table->string('id_karyawan', 50);
            $table->string('first_name', 50);
            $table->string('last_name', 50);

            $table->string('email', 100)->nullable();
            $table->string('no_telepon', 50)->nullable();
            $table->string('no_telepon_alt', 50)->nullable();

            $table->string('birth_place', 50)->nullable();
            $table->date('birth_date')->nullable();

            $table->string('nik', 16)->nullable();

            $table->string('address_ktp')->nullable();
            $table->string('address_residence')->nullable();

            $table->date('hire_date')->nullable()->useCurrent();

            $table->boolean('is_active')->nullable()->default(true);

            $table->string('password')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->string('username', 50)->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    DB::statement("
        ALTER TABLE core.employees
        ADD COLUMN IF NOT EXISTS id_karyawan VARCHAR(50),
        ADD COLUMN IF NOT EXISTS first_name VARCHAR(50),
        ADD COLUMN IF NOT EXISTS last_name VARCHAR(50),
        ADD COLUMN IF NOT EXISTS email VARCHAR(100),
        ADD COLUMN IF NOT EXISTS no_telepon VARCHAR(50),
        ADD COLUMN IF NOT EXISTS no_telepon_alt VARCHAR(50),
        ADD COLUMN IF NOT EXISTS birth_place VARCHAR(50),
        ADD COLUMN IF NOT EXISTS birth_date DATE,
        ADD COLUMN IF NOT EXISTS nik VARCHAR(16),
        ADD COLUMN IF NOT EXISTS address_ktp VARCHAR(255),
        ADD COLUMN IF NOT EXISTS address_residence VARCHAR(255),
        ADD COLUMN IF NOT EXISTS hire_date DATE DEFAULT CURRENT_DATE,
        ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE,
        ADD COLUMN IF NOT EXISTS password VARCHAR(255),
        ADD COLUMN IF NOT EXISTS profile_photo_path VARCHAR(255),
        ADD COLUMN IF NOT EXISTS username VARCHAR(50),
        ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
    ");
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core.employees');
    }
};
