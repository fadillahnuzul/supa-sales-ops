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
        Schema::create('sales.inquiries', function (Blueprint $table) {
            $table->id();

            $table->string('code', 100)->unique();
            $table->date('date');
            $table->date('etd')->nullable();

            $table->integer('pic')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();

            $table->decimal('shipping_rate', 15, 2)->nullable();
            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('pic');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales.inquiries');
    }
};
