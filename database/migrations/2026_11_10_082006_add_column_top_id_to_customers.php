<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('core.customers', 'top_id')) {
            Schema::table('core.customers', function (Blueprint $table) {
                $table->unsignedBigInteger('top_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('core.customers', 'top_id')) {
            Schema::table('core.customers', function (Blueprint $table) {
                $table->dropColumn('top_id');
            });
        }
    }
};
