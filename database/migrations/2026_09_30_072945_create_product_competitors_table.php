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
        Schema::create(
            'sales.product_competitors',
            function (Blueprint $table) {
                $table->increments('id');

                $table->integer('competitor_id');
                $table->integer('product_id');

                $table->decimal('price', 12, 2)->nullable();
                $table->date('date')->nullable();

                $table->text('note')->nullable();

                $table->softDeletes();
                $table->timestamps();

                $table->index('competitor_id');
                $table->index('product_id');
                $table->index('date');
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales.product_competitors');
    }
};
