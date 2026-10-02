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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales.competitor_products');
    }
};
