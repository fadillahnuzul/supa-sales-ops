<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('core.grades')) {
            Schema::create('core.grades', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 50);
            });
        }

        DB::statement("
        ALTER TABLE core.grades
        ADD COLUMN IF NOT EXISTS name VARCHAR(50)
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists('core.grades');
    }
};
