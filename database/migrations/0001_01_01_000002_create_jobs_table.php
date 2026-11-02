<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('public.jobs')) {
            Schema::create('public.jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedSmallInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        DB::statement("
        ALTER TABLE public.jobs
        ADD COLUMN IF NOT EXISTS queue VARCHAR(255),
        ADD COLUMN IF NOT EXISTS payload TEXT,
        ADD COLUMN IF NOT EXISTS attempts SMALLINT,
        ADD COLUMN IF NOT EXISTS reserved_at INTEGER,
        ADD COLUMN IF NOT EXISTS available_at INTEGER,
        ADD COLUMN IF NOT EXISTS created_at INTEGER
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS jobs_queue_index
        ON public.jobs (queue)
    ");

        if (!Schema::hasTable('public.job_batches')) {
            Schema::create('public.job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');

                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');

                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();

                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        DB::statement("
        ALTER TABLE public.job_batches
        ADD COLUMN IF NOT EXISTS id VARCHAR(255),
        ADD COLUMN IF NOT EXISTS name VARCHAR(255),
        ADD COLUMN IF NOT EXISTS total_jobs INTEGER,
        ADD COLUMN IF NOT EXISTS pending_jobs INTEGER,
        ADD COLUMN IF NOT EXISTS failed_jobs INTEGER,
        ADD COLUMN IF NOT EXISTS failed_job_ids TEXT,
        ADD COLUMN IF NOT EXISTS options TEXT,
        ADD COLUMN IF NOT EXISTS cancelled_at INTEGER,
        ADD COLUMN IF NOT EXISTS created_at INTEGER,
        ADD COLUMN IF NOT EXISTS finished_at INTEGER
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
                  AND t.relname = 'job_batches'
                  AND n.nspname = 'public'
            ) THEN
                ALTER TABLE public.job_batches
                ADD CONSTRAINT job_batches_pkey
                PRIMARY KEY (id);
            END IF;
        END
        $$;
    ");

        if (!Schema::hasTable('public.failed_jobs')) {
            Schema::create('public.failed_jobs', function (Blueprint $table) {
                $table->id();

                $table->string('uuid')->unique();
                $table->string('connection');
                $table->string('queue');

                $table->longText('payload');
                $table->longText('exception');

                $table->timestamp('failed_at')->useCurrent();

                $table->index([
                    'connection',
                    'queue',
                    'failed_at'
                ]);
            });
        }

        DB::statement("
        ALTER TABLE public.failed_jobs
        ADD COLUMN IF NOT EXISTS uuid VARCHAR(255),
        ADD COLUMN IF NOT EXISTS connection VARCHAR(255),
        ADD COLUMN IF NOT EXISTS queue VARCHAR(255),
        ADD COLUMN IF NOT EXISTS payload TEXT,
        ADD COLUMN IF NOT EXISTS exception TEXT,
        ADD COLUMN IF NOT EXISTS failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ");

        DB::statement("
        CREATE UNIQUE INDEX IF NOT EXISTS failed_jobs_uuid_unique
        ON public.failed_jobs (uuid)
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS
        failed_jobs_connection_queue_failed_at_index
        ON public.failed_jobs (
            connection,
            queue,
            failed_at
        )
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
