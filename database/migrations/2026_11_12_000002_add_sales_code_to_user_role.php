<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales.user_role', 'sales_code')) {
            Schema::table('sales.user_role', function (Blueprint $table): void {
                $table->string('sales_code', 2)->nullable();
            });
        }

        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS user_role_sales_code_unique
            ON sales.user_role (sales_code)
            WHERE role_id = 2 AND sales_code IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS sales.user_role_sales_code_unique');

        if (Schema::hasColumn('sales.user_role', 'sales_code')) {
            Schema::table('sales.user_role', function (Blueprint $table): void {
                $table->dropColumn('sales_code');
            });
        }
    }
};
