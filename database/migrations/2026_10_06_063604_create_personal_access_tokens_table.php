<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('public.personal_access_tokens')) {
            Schema::create('public.personal_access_tokens', function (Blueprint $table) {
                $table->id();

                $table->morphs('tokenable');

                $table->text('name');
                $table->string('token', 64)->unique();

                $table->text('abilities')->nullable();

                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();

                $table->timestamps();
            });
        }

        DB::statement("
        ALTER TABLE public.personal_access_tokens
        ADD COLUMN IF NOT EXISTS tokenable_type VARCHAR(255),
        ADD COLUMN IF NOT EXISTS tokenable_id BIGINT,
        ADD COLUMN IF NOT EXISTS name TEXT,
        ADD COLUMN IF NOT EXISTS token VARCHAR(64),
        ADD COLUMN IF NOT EXISTS abilities TEXT,
        ADD COLUMN IF NOT EXISTS last_used_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
    ");

        DB::statement("
        CREATE UNIQUE INDEX IF NOT EXISTS
        personal_access_tokens_token_unique
        ON public.personal_access_tokens (token)
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS
        personal_access_tokens_tokenable_type_tokenable_id_index
        ON public.personal_access_tokens (
            tokenable_type,
            tokenable_id
        )
    ");

        DB::statement("
        CREATE INDEX IF NOT EXISTS
        personal_access_tokens_expires_at_index
        ON public.personal_access_tokens (expires_at)
    ");
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
