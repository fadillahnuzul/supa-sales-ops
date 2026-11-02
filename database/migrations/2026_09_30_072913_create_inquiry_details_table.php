<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create table jika belum ada
        if (!Schema::hasTable('sales.inquiry_details')) {
            Schema::create('sales.inquiry_details', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('inquiry_id');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->unsignedBigInteger('grade_id')->nullable();

                $table->decimal('qty', 12, 3)->nullable();

                $table->decimal('product_std_price', 15, 2)->nullable();
                $table->decimal('alternative_price', 15, 2)->nullable();

                $table->unsignedBigInteger('source_ap')->nullable();
                $table->date('date_ap')->nullable();

                $table->decimal('reference_price', 15, 2)->nullable();

                $table->date('last_order_date')->nullable();
                $table->decimal('last_order_price', 15, 2)->nullable();

                $table->date('last_quotation_date')->nullable();
                $table->decimal('last_quotation_price', 15, 2)->nullable();

                $table->decimal('recommended_price', 15, 2)->nullable();

                $table->decimal('approved_price', 15, 2)->nullable();
                $table->date('approved_date')->nullable();

                $table->decimal('offer_1_price', 15, 2)->nullable();
                $table->decimal('offer_2_price', 15, 2)->nullable();
                $table->decimal('offer_3_price', 15, 2)->nullable();

                $table->decimal('final_price', 15, 2)->nullable();

                $table->text('note')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index('inquiry_id');
                $table->index('product_id');
                $table->index('grade_id');
                $table->index('source_ap');
            });
        }

        // Tambahkan kolom jika belum ada
        DB::statement("
        ALTER TABLE sales.inquiry_details
        ADD COLUMN IF NOT EXISTS inquiry_id BIGINT,
        ADD COLUMN IF NOT EXISTS product_id BIGINT,
        ADD COLUMN IF NOT EXISTS grade_id BIGINT,
        ADD COLUMN IF NOT EXISTS qty DECIMAL(12,3),
        ADD COLUMN IF NOT EXISTS product_std_price DECIMAL(15,2),
        ADD COLUMN IF NOT EXISTS alternative_price DECIMAL(15,2),
        ADD COLUMN IF NOT EXISTS source_ap BIGINT,
        ADD COLUMN IF NOT EXISTS date_ap DATE,
        ADD COLUMN IF NOT EXISTS reference_price DECIMAL(15,2),
        ADD COLUMN IF NOT EXISTS last_order_date DATE,
        ADD COLUMN IF NOT EXISTS last_order_price DECIMAL(15,2),
        ADD COLUMN IF NOT EXISTS last_quotation_date DATE,
        ADD COLUMN IF NOT EXISTS last_quotation_price DECIMAL(15,2),
        ADD COLUMN IF NOT EXISTS recommended_price DECIMAL(15,2),
        ADD COLUMN IF NOT EXISTS approved_price DECIMAL(15,2),
        ADD COLUMN IF NOT EXISTS approved_date DATE,
        ADD COLUMN IF NOT EXISTS offer_1_price DECIMAL(15,2),
        ADD COLUMN IF NOT EXISTS offer_2_price DECIMAL(15,2),
        ADD COLUMN IF NOT EXISTS offer_3_price DECIMAL(15,2),
        ADD COLUMN IF NOT EXISTS final_price DECIMAL(15,2),
        ADD COLUMN IF NOT EXISTS note TEXT,
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP
    ");

        // Create index jika belum ada
        DB::statement("
        CREATE INDEX IF NOT EXISTS inquiry_details_inquiry_id_index
        ON sales.inquiry_details (inquiry_id)
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS inquiry_details_product_id_index
        ON sales.inquiry_details (product_id)
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS inquiry_details_grade_id_index
        ON sales.inquiry_details (grade_id)
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS inquiry_details_source_ap_index
        ON sales.inquiry_details (source_ap)
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists('sales.inquiry_details');
    }
};
