<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE TYPE core.customer_division AS ENUM (
                'Industri',
                'Low Cost',
                'SME',
                'All'
            );

            CREATE TYPE core.customer_level AS ENUM (
                'Medium',
                'Low',
                'High'
            );

            CREATE TYPE core.sterilization AS ENUM (
                'NS',
                'S',
                'SS'
            );

            
        ");

        Schema::create('core.customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('segmentation_id')->nullable();
        });

        DB::statement("
            ALTER TABLE core.customers
            ADD COLUMN divisi core.customer_division NOT NULL,
            ADD COLUMN level core.customer_level NOT NULL,
            ADD COLUMN sterilization core.sterilization NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core.customers');
    }
};
