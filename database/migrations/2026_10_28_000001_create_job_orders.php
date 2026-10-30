<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales.job_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inquiry_id')->unique();
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

        Schema::create('sales.job_order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_order_id');
            $table->unsignedBigInteger('inquiry_detail_id')->nullable();
            $table->decimal('kg', 15, 3)->default(0);
            $table->string('product_code', 100)->nullable();
            $table->string('product_name')->nullable();
            $table->string('packing')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('job_order_id')->references('id')->on('sales.job_orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales.job_order_details');
        Schema::dropIfExists('sales.job_orders');
    }
};
