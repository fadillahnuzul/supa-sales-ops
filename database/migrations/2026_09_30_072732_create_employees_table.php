<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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

            // marital_status merupakan PostgreSQL ENUM existing

            $table->string('nik', 16)->nullable();

            $table->string('address_ktp')->nullable();
            $table->string('address_residence')->nullable();

            $table->date('hire_date')
                ->nullable()
                ->useCurrent();

            $table->boolean('is_active')
                ->nullable()
                ->default(true);

            $table->string('password')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->string('username', 50)->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core.employees');
    }
};
