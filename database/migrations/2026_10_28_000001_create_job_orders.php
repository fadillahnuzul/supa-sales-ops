<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales.job_orders')) {
            Schema::create('sales.job_orders', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('inquiry_id')->nullable();
                $table->date('date');
                $table->string('so_no', 100);
                $table->string('po_no', 100);

                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('customer_no', 100)->nullable();
                $table->string('customer_name');
                $table->text('customer_address')->nullable();

                $table->unsignedBigInteger('pic_id')->nullable();
                $table->string('pic_name')->nullable();

                $table->date('shipping_date')->nullable();

                $table->string('status', 20)->default('pending');

                $table->unsignedBigInteger('created_by');
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->unsignedBigInteger('checked_by')->nullable();
                $table->timestamp('checked_at')->nullable();

                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();

                $table->timestamps();

                $table->index(['date', 'status']);
                $table->unique('so_no');
            });
        }

        DB::statement("
        ALTER TABLE sales.job_orders
        ADD COLUMN IF NOT EXISTS inquiry_id BIGINT,
        ADD COLUMN IF NOT EXISTS date DATE,
        ADD COLUMN IF NOT EXISTS so_no VARCHAR(100),
        ADD COLUMN IF NOT EXISTS po_no VARCHAR(100),
        ADD COLUMN IF NOT EXISTS customer_id BIGINT,
        ADD COLUMN IF NOT EXISTS customer_no VARCHAR(100),
        ADD COLUMN IF NOT EXISTS customer_name VARCHAR(255),
        ADD COLUMN IF NOT EXISTS customer_address TEXT,
        ADD COLUMN IF NOT EXISTS pic_id BIGINT,
        ADD COLUMN IF NOT EXISTS pic_name VARCHAR(255),
        ADD COLUMN IF NOT EXISTS shipping_date DATE,
        ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'pending',
        ADD COLUMN IF NOT EXISTS created_by BIGINT,
        ADD COLUMN IF NOT EXISTS updated_by BIGINT,
        ADD COLUMN IF NOT EXISTS checked_by BIGINT,
        ADD COLUMN IF NOT EXISTS checked_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS approved_by BIGINT,
        ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
    ");

        DB::statement("
        CREATE UNIQUE INDEX IF NOT EXISTS
        job_orders_so_no_unique
        ON sales.job_orders (so_no)
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS
        job_orders_date_status_index
        ON sales.job_orders (date, status)
    ");

        if (!Schema::hasTable('sales.job_order_details')) {
            Schema::create('sales.job_order_details', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('job_order_id');

                $table->unsignedBigInteger('inquiry_detail_id')
                    ->nullable();

                $table->decimal('kg', 15, 3)->default(0);

                $table->string('product_code', 100)->nullable();
                $table->string('product_name')->nullable();
                $table->string('packing')->nullable();

                $table->text('note')->nullable();

                $table->unsignedInteger('sort_order')->default(0);

                $table->timestamps();

                $table->foreign('job_order_id')
                    ->references('id')
                    ->on('sales.job_orders')
                    ->cascadeOnDelete();
            });
        }

        DB::statement("
        ALTER TABLE sales.job_order_details
        ADD COLUMN IF NOT EXISTS job_order_id BIGINT,
        ADD COLUMN IF NOT EXISTS inquiry_detail_id BIGINT,
        ADD COLUMN IF NOT EXISTS kg DECIMAL(15,3) DEFAULT 0,
        ADD COLUMN IF NOT EXISTS product_code VARCHAR(100),
        ADD COLUMN IF NOT EXISTS product_name VARCHAR(255),
        ADD COLUMN IF NOT EXISTS packing VARCHAR(255),
        ADD COLUMN IF NOT EXISTS note TEXT,
        ADD COLUMN IF NOT EXISTS sort_order INTEGER DEFAULT 0,
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
    ");

        DB::statement("
        DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1
                FROM pg_constraint c
                JOIN pg_class t ON t.oid = c.conrelid
                JOIN pg_namespace n ON n.oid = t.relnamespace
                WHERE c.conname = 'job_order_details_job_order_id_foreign'
                  AND t.relname = 'job_order_details'
                  AND n.nspname = 'sales'
            ) THEN
                ALTER TABLE sales.job_order_details
                ADD CONSTRAINT job_order_details_job_order_id_foreign
                FOREIGN KEY (job_order_id)
                REFERENCES sales.job_orders(id)
                ON DELETE CASCADE;
            END IF;
        END
        $$;
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists('sales.job_order_details');
        Schema::dropIfExists('sales.job_orders');
    }
};
