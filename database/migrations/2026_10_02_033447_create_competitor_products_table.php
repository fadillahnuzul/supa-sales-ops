<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales.competitor_products')) {
            Schema::create('sales.competitor_products', function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->unsignedBigInteger('competitor_id');
                $table->unsignedBigInteger('product_id');

                $table->decimal('price', 15, 2)->nullable();
                $table->date('date')->nullable();
                $table->text('note')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->foreign('competitor_id')
                    ->references('id')
                    ->on('sales.competitors')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreign('product_id')
                    ->references('id')
                    ->on('core.products')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $table->unique(
                    ['competitor_id', 'product_id'],
                    'competitor_products_competitor_product_unique'
                );
            });
        }

        DB::statement("
        ALTER TABLE sales.competitor_products
        ADD COLUMN IF NOT EXISTS competitor_id BIGINT,
        ADD COLUMN IF NOT EXISTS product_id BIGINT,
        ADD COLUMN IF NOT EXISTS price DECIMAL(15,2),
        ADD COLUMN IF NOT EXISTS date DATE,
        ADD COLUMN IF NOT EXISTS note TEXT,
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP
    ");

        DB::statement("
        CREATE UNIQUE INDEX IF NOT EXISTS
        competitor_products_competitor_product_unique
        ON sales.competitor_products (
            competitor_id,
            product_id
        )
    ");

        DB::statement("
        DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1
                FROM pg_constraint c
                JOIN pg_class t ON t.oid = c.conrelid
                JOIN pg_namespace n ON n.oid = t.relnamespace
                WHERE c.conname = 'competitor_products_competitor_id_foreign'
                  AND t.relname = 'competitor_products'
                  AND n.nspname = 'sales'
            ) THEN
                ALTER TABLE sales.competitor_products
                ADD CONSTRAINT competitor_products_competitor_id_foreign
                FOREIGN KEY (competitor_id)
                REFERENCES sales.competitors(id)
                ON UPDATE CASCADE
                ON DELETE CASCADE;
            END IF;
        END
        $$;
    ");

        DB::statement("
        DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1
                FROM pg_constraint c
                JOIN pg_class t ON t.oid = c.conrelid
                JOIN pg_namespace n ON n.oid = t.relnamespace
                WHERE c.conname = 'competitor_products_product_id_foreign'
                  AND t.relname = 'competitor_products'
                  AND n.nspname = 'sales'
            ) THEN
                ALTER TABLE sales.competitor_products
                ADD CONSTRAINT competitor_products_product_id_foreign
                FOREIGN KEY (product_id)
                REFERENCES core.products(id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT;
            END IF;
        END
        $$;
    ");
    }


    public function down(): void
    {
        Schema::dropIfExists('sales.competitor_products');
    }
};
