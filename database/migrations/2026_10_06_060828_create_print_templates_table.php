<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales.print_templates')) {
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
        DB::statement("
        ALTER TABLE sales.print_templates
        ADD COLUMN IF NOT EXISTS name VARCHAR(150),
        ADD COLUMN IF NOT EXISTS code VARCHAR(100),
        ADD COLUMN IF NOT EXISTS division VARCHAR(50),
        ADD COLUMN IF NOT EXISTS template_type VARCHAR(50) DEFAULT 'QUOTATION',
        ADD COLUMN IF NOT EXISTS file_path TEXT,
        ADD COLUMN IF NOT EXISTS is_default BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE,
        ADD COLUMN IF NOT EXISTS created_by BIGINT,
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
    ");

        DB::statement("
        CREATE UNIQUE INDEX IF NOT EXISTS
        print_templates_code_unique
        ON sales.print_templates (code)
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists('sales.print_templates');
    }
};
