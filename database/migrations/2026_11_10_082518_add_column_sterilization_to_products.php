<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('core.products', 'sterilization_id')) {
            Schema::table('core.products', function (Blueprint $table) {
                $table->unsignedInteger('sterilization_id')->nullable();
            });
        }

        if (!Schema::hasTable('core.sterilizations')) {
            Schema::create('core.sterilizations', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('core.products', 'sterilization_id')) {
            Schema::table('core.products', function (Blueprint $table) {
                $table->dropColumn('sterilization_id');
            });
        }

        Schema::dropIfExists('core.sterilizations');
    }
};
