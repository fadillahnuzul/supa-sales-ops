<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('core.product_materials')) {
            Schema::create('core.product_materials', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('material_id');
                $table->string('material_type');

                $table->timestamps();

                $table->foreign('product_id')
                    ->references('id')
                    ->on('core.products')
                    ->cascadeOnDelete();

                $table->index(['material_type', 'material_id']);

                $table->unique(
                    ['product_id', 'material_type', 'material_id'],
                    'product_material_unique'
                );
            });
        }

        DB::statement("
        ALTER TABLE core.product_materials
        ADD COLUMN IF NOT EXISTS product_id BIGINT,
        ADD COLUMN IF NOT EXISTS material_id BIGINT,
        ADD COLUMN IF NOT EXISTS material_type VARCHAR(255),
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS
        product_materials_material_type_material_id_index
        ON core.product_materials (material_type, material_id)
    ");

        DB::statement("
        CREATE UNIQUE INDEX IF NOT EXISTS
        product_material_unique
        ON core.product_materials (
            product_id,
            material_type,
            material_id
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
                WHERE c.conname = 'product_materials_product_id_foreign'
                  AND t.relname = 'product_materials'
                  AND n.nspname = 'core'
            ) THEN
                IF EXISTS (
                    SELECT 1
                    FROM core.product_materials AS product_material
                    LEFT JOIN core.products AS product
                        ON product.id = product_material.product_id
                    WHERE product_material.product_id IS NOT NULL
                      AND product.id IS NULL
                ) THEN
                    ALTER TABLE core.product_materials
                    ADD CONSTRAINT product_materials_product_id_foreign
                    FOREIGN KEY (product_id)
                    REFERENCES core.products(id)
                    ON DELETE CASCADE
                    NOT VALID;

                    RAISE WARNING
                        'Added product_materials_product_id_foreign as NOT VALID because product_materials rows reference missing products. Reconcile those rows, then run ALTER TABLE core.product_materials VALIDATE CONSTRAINT product_materials_product_id_foreign.';
                ELSE
                    ALTER TABLE core.product_materials
                    ADD CONSTRAINT product_materials_product_id_foreign
                    FOREIGN KEY (product_id)
                    REFERENCES core.products(id)
                    ON DELETE CASCADE;
                END IF;
            END IF;
        END
        $$;
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'core.product_materials'
        );
    }
};
