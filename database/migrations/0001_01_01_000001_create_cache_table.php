<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('public.cache')) {
            Schema::create('public.cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->bigInteger('expiration')->index();
            });
        }

        DB::statement("
        ALTER TABLE public.cache
        ADD COLUMN IF NOT EXISTS \"key\" VARCHAR(255),
        ADD COLUMN IF NOT EXISTS \"value\" TEXT,
        ADD COLUMN IF NOT EXISTS expiration BIGINT
    ");

        DB::statement("
        DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1
                FROM pg_constraint c
                JOIN pg_class t ON t.oid = c.conrelid
                JOIN pg_namespace n ON n.oid = t.relnamespace
                WHERE c.contype = 'p'
                  AND t.relname = 'cache'
                  AND n.nspname = 'public'
            ) THEN
                ALTER TABLE public.cache
                ADD CONSTRAINT cache_pkey
                PRIMARY KEY (\"key\");
            END IF;
        END
        $$;
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS cache_expiration_index
        ON public.cache (expiration)
    ");

        if (!Schema::hasTable('public.cache_locks')) {
            Schema::create('public.cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->bigInteger('expiration')->index();
            });
        }

        DB::statement("
        ALTER TABLE public.cache_locks
        ADD COLUMN IF NOT EXISTS \"key\" VARCHAR(255),
        ADD COLUMN IF NOT EXISTS owner VARCHAR(255),
        ADD COLUMN IF NOT EXISTS expiration BIGINT
    ");

        DB::statement("
        DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1
                FROM pg_constraint c
                JOIN pg_class t ON t.oid = c.conrelid
                JOIN pg_namespace n ON n.oid = t.relnamespace
                WHERE c.contype = 'p'
                  AND t.relname = 'cache_locks'
                  AND n.nspname = 'public'
            ) THEN
                ALTER TABLE public.cache_locks
                ADD CONSTRAINT cache_locks_pkey
                PRIMARY KEY (\"key\");
            END IF;
        END
        $$;
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS cache_locks_expiration_index
        ON public.cache_locks (expiration)
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
