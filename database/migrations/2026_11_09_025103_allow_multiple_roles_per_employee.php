<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sales.user_role')) {
            return;
        }

        if (! Schema::hasColumn('sales.user_role', 'id')) {
            Schema::table('sales.user_role', function (Blueprint $table): void {
                $table->bigInteger('id')->nullable();
            });

            DB::statement('CREATE SEQUENCE IF NOT EXISTS sales.user_role_id_seq');
            DB::statement('ALTER SEQUENCE sales.user_role_id_seq OWNED BY sales.user_role.id');
            DB::statement("ALTER TABLE sales.user_role ALTER COLUMN id SET DEFAULT nextval('sales.user_role_id_seq')");
            DB::statement('UPDATE sales.user_role SET id = nextval(\'sales.user_role_id_seq\') WHERE id IS NULL');
            DB::statement("SELECT setval('sales.user_role_id_seq', COALESCE((SELECT MAX(id) FROM sales.user_role), 0) + 1, false)");
            DB::statement('ALTER TABLE sales.user_role ALTER COLUMN id SET NOT NULL');
        }

        DB::statement(<<<'SQL'
            DO $$
            DECLARE
                constraint_record RECORD;
            BEGIN
                FOR constraint_record IN
                    SELECT constraint_info.conname
                    FROM pg_constraint AS constraint_info
                    WHERE constraint_info.conrelid = 'sales.user_role'::regclass
                      AND (
                        (
                            constraint_info.contype = 'p'
                            AND (
                                SELECT array_agg(attribute.attname::text ORDER BY key.position)
                                FROM unnest(constraint_info.conkey)
                                    WITH ORDINALITY AS key(attribute_number, position)
                                JOIN pg_attribute AS attribute
                                  ON attribute.attrelid = constraint_info.conrelid
                                 AND attribute.attnum = key.attribute_number
                            ) <> ARRAY['id']::text[]
                        )
                        OR (
                            constraint_info.contype = 'u'
                            AND (
                                SELECT array_agg(attribute.attname::text ORDER BY key.position)
                                FROM unnest(constraint_info.conkey)
                                    WITH ORDINALITY AS key(attribute_number, position)
                                JOIN pg_attribute AS attribute
                                  ON attribute.attrelid = constraint_info.conrelid
                                 AND attribute.attnum = key.attribute_number
                            ) = ARRAY['employee_id']::text[]
                        )
                      )
                LOOP
                    EXECUTE format(
                        'ALTER TABLE sales.user_role DROP CONSTRAINT %I',
                        constraint_record.conname
                    );
                END LOOP;

                IF NOT EXISTS (
                    SELECT 1
                    FROM pg_constraint
                    WHERE conrelid = 'sales.user_role'::regclass
                      AND contype = 'p'
                ) THEN
                    ALTER TABLE sales.user_role
                    ADD CONSTRAINT user_role_pkey PRIMARY KEY (id);
                END IF;
            END
            $$;
        SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX IF NOT EXISTS
                user_role_employee_id_role_id_unique
            ON sales.user_role (employee_id, role_id)
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(<<<'SQL'
            DO $$
            DECLARE
                primary_key_name text;
            BEGIN
                IF EXISTS (
                    SELECT 1
                    FROM sales.user_role
                    GROUP BY employee_id
                    HAVING COUNT(*) > 1
                ) THEN
                    RAISE EXCEPTION
                        'Cannot roll back multiple employee roles while duplicate employee assignments exist.';
                END IF;

                SELECT conname
                INTO primary_key_name
                FROM pg_constraint
                WHERE conrelid = 'sales.user_role'::regclass
                  AND contype = 'p';

                IF primary_key_name IS NOT NULL THEN
                    EXECUTE format(
                        'ALTER TABLE sales.user_role DROP CONSTRAINT %I',
                        primary_key_name
                    );
                END IF;

                ALTER TABLE sales.user_role
                ADD CONSTRAINT user_role_pkey PRIMARY KEY (employee_id);
            END
            $$;
        SQL);
    }
};
