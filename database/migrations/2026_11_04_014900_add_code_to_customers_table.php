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
        if (! Schema::hasColumn('core.customers', 'code')) {
            Schema::table('core.customers', function (Blueprint $table) {
                $table->string('code', 50)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('core.customers', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
