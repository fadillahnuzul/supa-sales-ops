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
        Schema::create('core.products', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name');
            $table->unsignedBigInteger('grade_id')->nullable();

            $table->string('code', 50)->nullable();
            $table->decimal('std_price', 12, 2)->nullable();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core.products');
    }
};
