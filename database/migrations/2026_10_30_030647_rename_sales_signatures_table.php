<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (
            Schema::hasTable('sales.sales_signature')
            && ! Schema::hasTable('sales.sales_signatures')
        ) {
            Schema::rename('sales.sales_signature', 'sales_signatures');
        }
    }

    /**
     * Reverse the migrations.
     */
    /**
     * The singular table may have existed before this migration.
     */
    public function down(): void {}
};
