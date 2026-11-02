<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales.inquiries')) {
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

        DB::statement("
        ALTER TABLE sales.inquiries
        ADD COLUMN IF NOT EXISTS code VARCHAR(100),
        ADD COLUMN IF NOT EXISTS date DATE,
        ADD COLUMN IF NOT EXISTS etd DATE,
        ADD COLUMN IF NOT EXISTS pic INTEGER,
        ADD COLUMN IF NOT EXISTS customer_id BIGINT,
        ADD COLUMN IF NOT EXISTS shipping_rate DECIMAL(15,2),
        ADD COLUMN IF NOT EXISTS note TEXT,
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP
    ");

        DB::statement("
        CREATE UNIQUE INDEX IF NOT EXISTS
        inquiries_code_unique
        ON sales.inquiries (code)
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS
        inquiries_customer_id_index
        ON sales.inquiries (customer_id)
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS
        inquiries_pic_index
        ON sales.inquiries (pic)
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS
        inquiries_date_index
        ON sales.inquiries (date)
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists('sales.inquiries');
    }
};
