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
        DB::statement("
            CREATE TYPE sales.competitor_division AS ENUM (
                'Industri',
                'Low Cost',
                'SME',
                'All'
            )
        ");

        Schema::create('sales.competitors', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name');

            $table->text('note')->nullable();

            $table->softDeletes();
        });

        DB::statement("
            ALTER TABLE sales.competitors
            ADD COLUMN divisi sales.competitor_division NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core.competitors');
    }
};
