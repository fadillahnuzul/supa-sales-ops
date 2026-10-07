<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales.sales_signatures', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')
                ->nullable();

            $table->string('name', 150);
            $table->string('division', 50)->nullable();
            $table->string('position', 150)->nullable();

            $table->text('signature_path');

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales.sales_signatures');
    }
};
