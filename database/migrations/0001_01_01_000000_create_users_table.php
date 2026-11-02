<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('public.users')) {
            Schema::create('public.users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        DB::statement("
        ALTER TABLE public.users
        ADD COLUMN IF NOT EXISTS name VARCHAR(255),
        ADD COLUMN IF NOT EXISTS email VARCHAR(255),
        ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS password VARCHAR(255),
        ADD COLUMN IF NOT EXISTS remember_token VARCHAR(100),
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
    ");

        DB::statement("
        CREATE UNIQUE INDEX IF NOT EXISTS
        users_email_unique
        ON public.users (email)
    ");

        if (!Schema::hasTable('public.password_reset_tokens')) {
            Schema::create('public.password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        DB::statement("
        ALTER TABLE public.password_reset_tokens
        ADD COLUMN IF NOT EXISTS email VARCHAR(255),
        ADD COLUMN IF NOT EXISTS token VARCHAR(255),
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP
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
                  AND t.relname = 'password_reset_tokens'
                  AND n.nspname = 'public'
            ) THEN
                ALTER TABLE public.password_reset_tokens
                ADD CONSTRAINT password_reset_tokens_pkey
                PRIMARY KEY (email);
            END IF;
        END
        $$;
    ");

        if (!Schema::hasTable('public.sessions')) {
            Schema::create('public.sessions', function (Blueprint $table) {
                $table->string('id')->primary();

                $table->foreignId('user_id')->nullable()->index();

                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');

                $table->integer('last_activity')->index();
            });
        }

        DB::statement("
        ALTER TABLE public.sessions
        ADD COLUMN IF NOT EXISTS id VARCHAR(255),
        ADD COLUMN IF NOT EXISTS user_id BIGINT,
        ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45),
        ADD COLUMN IF NOT EXISTS user_agent TEXT,
        ADD COLUMN IF NOT EXISTS payload TEXT,
        ADD COLUMN IF NOT EXISTS last_activity INTEGER
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
                  AND t.relname = 'sessions'
                  AND n.nspname = 'public'
            ) THEN
                ALTER TABLE public.sessions
                ADD CONSTRAINT sessions_pkey
                PRIMARY KEY (id);
            END IF;
        END
        $$;
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS
        sessions_user_id_index
        ON public.sessions (user_id)
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS
        sessions_last_activity_index
        ON public.sessions (last_activity)
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
