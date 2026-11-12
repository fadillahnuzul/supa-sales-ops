<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales.inquiry_code_counters')) {
            return;
        }

        Schema::create('sales.inquiry_code_counters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pic_id');
            $table->date('inquiry_date');
            $table->string('sales_code', 2);
            $table->unsignedInteger('next_sequence')->default(1);
            $table->unsignedInteger('reserved_sequence')->nullable();
            $table->string('reserved_code', 100)->nullable()->unique();
            $table->timestamps();
            $table->unique(['pic_id', 'inquiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales.inquiry_code_counters');
    }
};
