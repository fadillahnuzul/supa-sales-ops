<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create ENUM jika belum ada
        DB::statement("
        DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1 FROM pg_type t
                JOIN pg_namespace n ON n.oid = t.typnamespace
                WHERE t.typname = 'customer_division'
                AND n.nspname = 'core'
            ) THEN
                CREATE TYPE core.customer_division AS ENUM
                ('Industri', 'Low Cost', 'SME', 'All');
            END IF;

            IF NOT EXISTS (
                SELECT 1 FROM pg_type t
                JOIN pg_namespace n ON n.oid = t.typnamespace
                WHERE t.typname = 'customer_level'
                AND n.nspname = 'core'
            ) THEN
                CREATE TYPE core.customer_level AS ENUM
                ('Medium', 'Low', 'High');
            END IF;

            IF NOT EXISTS (
                SELECT 1 FROM pg_type t
                JOIN pg_namespace n ON n.oid = t.typnamespace
                WHERE t.typname = 'sterilization'
                AND n.nspname = 'core'
            ) THEN
                CREATE TYPE core.sterilization AS ENUM
                ('NS', 'S', 'SS');
            END IF;
        END
        $$;
    ");

        // Create table jika belum ada
        if (!Schema::hasTable('core.customers')) {
            Schema::create('core.customers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->integer('segmentation_id')->nullable();
                $table->string('address')->nullable();
                $table->string('pic')->nullable();
                $table->string('phone')->nullable();
            });
        }

        // Tambahkan kolom ENUM hanya jika belum ada
        DB::statement("
        ALTER TABLE core.customers
        ADD COLUMN IF NOT EXISTS divisi core.customer_division,
        ADD COLUMN IF NOT EXISTS level core.customer_level,
        ADD COLUMN IF NOT EXISTS sterilization core.sterilization
    ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core.customers');

        DB::statement('DROP TYPE IF EXISTS core.customer_division CASCADE');
        DB::statement('DROP TYPE IF EXISTS core.customer_level CASCADE');
        DB::statement('DROP TYPE IF EXISTS core.sterilization CASCADE');
    }
};
