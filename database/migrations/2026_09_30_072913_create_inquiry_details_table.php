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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales.inquiry_details');
    }
};
