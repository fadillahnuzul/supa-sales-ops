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
        if (!Schema::hasTable('core.top')) {
            Schema::create('core.top', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('core.top');
    }
};
