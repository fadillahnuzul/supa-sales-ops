<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
        DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1
                FROM pg_type t
                JOIN pg_namespace n ON n.oid = t.typnamespace
                WHERE t.typname = 'competitor_division'
                AND n.nspname = 'sales'
            ) THEN
                CREATE TYPE sales.competitor_division AS ENUM (
                    'Industri',
                    'Low Cost',
                    'SME',
                    'All'
                );
            END IF;
        END
        $$;
    ");

        if (!Schema::hasTable('sales.competitors')) {
            Schema::create('sales.competitors', function (Blueprint $table) {
                $table->increments('id');

                $table->string('name');

                $table->text('note')->nullable();

                $table->softDeletes();
            });
        }

        DB::statement("
        ALTER TABLE sales.competitors
        ADD COLUMN IF NOT EXISTS name VARCHAR(255),
        ADD COLUMN IF NOT EXISTS note TEXT,
        ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS divisi sales.competitor_division
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists('core.competitors');
    }
};
