<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('core.products')) {
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

        DB::statement("
        ALTER TABLE core.products
        ADD COLUMN IF NOT EXISTS name VARCHAR(255),
        ADD COLUMN IF NOT EXISTS grade_id BIGINT,
        ADD COLUMN IF NOT EXISTS code VARCHAR(50),
        ADD COLUMN IF NOT EXISTS std_price DECIMAL(12,2),
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP
    ");
    }


    public function down(): void
    {
        Schema::dropIfExists('core.products');
    }
};
