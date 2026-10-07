<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales.print_templates', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('code', 100)->unique();

            $table->string('division', 50)->nullable();

            $table->string('template_type', 50)
                ->default('QUOTATION');

            $table->text('file_path');

            $table->boolean('is_default')
                ->default(false);

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedBigInteger('created_by')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales.print_templates');
    }
};
