<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('core.segmentations')) {
            Schema::create('core.segmentations', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
            });
        }

        DB::statement("
        ALTER TABLE core.segmentations
        ADD COLUMN IF NOT EXISTS name VARCHAR(255)
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists('segmentations');
    }
};
