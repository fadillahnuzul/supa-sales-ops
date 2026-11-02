<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    if (
        Schema::hasTable('sales.sales_signature') &&
        !Schema::hasTable('sales.sales_signatures')
    ) {
        DB::statement("
            ALTER TABLE sales.sales_signature
            RENAME TO sales_signatures
        ");
    }
}

    public function down(): void {}
};
