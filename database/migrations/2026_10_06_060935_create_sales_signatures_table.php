<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales.sales_signatures')) {
            Schema::create('sales.sales_signatures', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('user_id')->nullable();

                $table->string('name', 150);
                $table->string('division', 50)->nullable();
                $table->string('position', 150)->nullable();

                $table->text('signature_path');

                $table->boolean('is_active')->default(true);

                $table->timestamps();
            });
        }

        DB::statement("
        ALTER TABLE sales.sales_signatures
        ADD COLUMN IF NOT EXISTS user_id BIGINT,
        ADD COLUMN IF NOT EXISTS name VARCHAR(150),
        ADD COLUMN IF NOT EXISTS division VARCHAR(50),
        ADD COLUMN IF NOT EXISTS position VARCHAR(150),
        ADD COLUMN IF NOT EXISTS signature_path TEXT,
        ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE,
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists('sales.sales_signatures');
    }
};
