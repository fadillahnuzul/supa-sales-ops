<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'core.product_materials',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger(
                    'product_id'
                );
                $table->unsignedBigInteger(
                    'material_id'
                );

                $table->string(
                    'material_type'
                );

                $table->timestamps();

                $table->foreign(
                    'product_id'
                )
                    ->references('id')
                    ->on('core.products')
                    ->cascadeOnDelete();

                $table->index([
                    'material_type',
                    'material_id',
                ]);

                $table->unique(
                    [
                        'product_id',
                        'material_type',
                        'material_id',
                    ],
                    'product_material_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'core.product_materials'
        );
    }
};
