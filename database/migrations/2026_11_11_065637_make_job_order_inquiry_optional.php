<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales.job_orders', function (Blueprint $table): void {
            $table->dropUnique('job_orders_inquiry_id_unique');
            $table->unsignedBigInteger('inquiry_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('sales.job_orders')->whereNull('inquiry_id')->exists()) {
            throw new RuntimeException('Job Order tanpa Inquiry harus dipindahkan atau dihapus sebelum rollback.');
        }

        Schema::table('sales.job_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('inquiry_id')->nullable(false)->change();
            $table->unique('inquiry_id');
        });
    }
};
