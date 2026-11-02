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
        if (!Schema::hasTable('core.materials')) {
            Schema::create('core.materials', function (Blueprint $table) {
                $table->increments('id');

                $table->string('name');
                $table->string('name_indonesian')->nullable();

                $table->softDeletes();
            });
        }

        DB::statement("
        ALTER TABLE core.materials
        ADD COLUMN IF NOT EXISTS name VARCHAR(255),
        ADD COLUMN IF NOT EXISTS name_indonesian VARCHAR(255),
        ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP
    ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core.materials');
    }
};
