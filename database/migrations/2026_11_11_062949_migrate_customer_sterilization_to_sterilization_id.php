<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('core.sterilizations')) {
            Schema::create('core.sterilizations', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
            });
        }

        foreach (['NS', 'S', 'SS'] as $name) {
            if (! DB::table('core.sterilizations')->where('name', $name)->exists()) {
                DB::table('core.sterilizations')->insert(['name' => $name]);
            }
        }

        if (! Schema::hasColumn('core.customers', 'sterilization_id')) {
            Schema::table('core.customers', function (Blueprint $table) {
                $table->foreignId('sterilization_id')->nullable();
            });
        }

        if (Schema::hasColumn('core.customers', 'sterilization')) {
            foreach (['NS', 'S', 'SS'] as $name) {
                $sterilizationId = DB::table('core.sterilizations')
                    ->where('name', $name)
                    ->value('id');

                DB::table('core.customers')
                    ->whereNull('sterilization_id')
                    ->whereRaw('sterilization::text = ?', [$name])
                    ->update(['sterilization_id' => $sterilizationId]);
            }

            Schema::table('core.customers', function (Blueprint $table) {
                $table->dropColumn('sterilization');
            });
        }

        Schema::table('core.customers', function (Blueprint $table) {
            $table->foreign('sterilization_id')
                ->references('id')
                ->on('core.sterilizations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('core.customers', 'sterilization_id')) {
            return;
        }

        $hasUnmappedSterilizations = DB::table('core.customers as customers')
            ->join(
                'core.sterilizations as sterilizations',
                'sterilizations.id',
                '=',
                'customers.sterilization_id'
            )
            ->whereNotIn('sterilizations.name', ['NS', 'S', 'SS'])
            ->exists();

        if ($hasUnmappedSterilizations) {
            throw new RuntimeException(
                'Cannot roll back customer sterilization IDs because some values have no legacy enum equivalent.'
            );
        }

        DB::statement(
            'ALTER TABLE core.customers ADD COLUMN IF NOT EXISTS sterilization core.sterilization'
        );

        DB::statement(
            "UPDATE core.customers AS customers SET sterilization = sterilizations.name::core.sterilization FROM core.sterilizations AS sterilizations WHERE sterilizations.id = customers.sterilization_id AND sterilizations.name IN ('NS', 'S', 'SS')"
        );

        Schema::table('core.customers', function (Blueprint $table) {
            $table->dropForeign(['sterilization_id']);
            $table->dropColumn('sterilization_id');
        });
    }
};
