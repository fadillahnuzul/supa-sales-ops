<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales.roles')) {
            Schema::create('sales.roles', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->timestamps();
            });
        }

        DB::statement("
        ALTER TABLE sales.roles
        ADD COLUMN IF NOT EXISTS name VARCHAR(100),
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
    ");

        if (!Schema::hasTable('sales.user_role')) {
            Schema::create('sales.user_role', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('role_id');
                $table->timestamps();

                $table->unique(
                    ['employee_id', 'role_id'],
                    'user_role_employee_id_role_id_unique'
                );
            });
        }

        DB::statement("
        ALTER TABLE sales.user_role
        ADD COLUMN IF NOT EXISTS employee_id BIGINT,
        ADD COLUMN IF NOT EXISTS role_id BIGINT,
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
    ");

        DB::statement("
        CREATE UNIQUE INDEX IF NOT EXISTS
        user_role_user_id_role_id_unique
        ON sales.user_role (user_id, role_id)
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS
        user_role_user_id_index
        ON sales.user_role (user_id)
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS
        user_role_role_id_index
        ON sales.user_role (role_id)
    ");

        DB::statement("
        DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1
                FROM pg_constraint c
                JOIN pg_class t ON t.oid = c.conrelid
                JOIN pg_namespace n ON n.oid = t.relnamespace
                WHERE c.conname = 'user_role_role_id_foreign'
                  AND t.relname = 'user_role'
                  AND n.nspname = 'sales'
            ) THEN
                ALTER TABLE sales.user_role
                ADD CONSTRAINT user_role_role_id_foreign
                FOREIGN KEY (role_id)
                REFERENCES sales.roles(id)
                ON DELETE CASCADE;
            END IF;
        END
        $$;
    ");

        DB::statement("
        DO $$
        BEGIN
            IF NOT EXISTS (
                SELECT 1
                FROM pg_constraint c
                JOIN pg_class t ON t.oid = c.conrelid
                JOIN pg_namespace n ON n.oid = t.relnamespace
                WHERE c.conname = 'user_role_user_id_foreign'
                  AND t.relname = 'user_role'
                  AND n.nspname = 'sales'
            ) THEN
                ALTER TABLE sales.user_role
                ADD CONSTRAINT user_role_user_id_foreign
                FOREIGN KEY (user_id)
                REFERENCES core.employees(id)
                ON DELETE CASCADE;
            END IF;
        END
        $$;
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists('sales.user_role');
        Schema::dropIfExists('sales.roles');
    }
};
