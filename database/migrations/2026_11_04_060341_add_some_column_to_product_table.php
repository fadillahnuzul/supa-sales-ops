<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('core.products', 'production_name')) {
            Schema::table('core.products', function (Blueprint $table) {
                $table->string('production_name', 100)->nullable();
            });
        }

        if (! Schema::hasColumn('core.products', 'botanical_name')) {
            Schema::table('core.products', function (Blueprint $table) {
                $table->string('botanical_name', 100)->nullable();
            });
        }

        if (! Schema::hasColumn('core.products', 'fda')) {
            Schema::table('core.products', function (Blueprint $table) {
                $table->string('fda', 50)->nullable();
            });
        }

        if (! Schema::hasColumn('core.products', 'fssc')) {
            Schema::table('core.products', function (Blueprint $table) {
                $table->string('fssc', 50)->nullable();
            });
        }

        if (! Schema::hasColumn('core.products', 'kosher')) {
            Schema::table('core.products', function (Blueprint $table) {
                $table->string('kosher', 50)->nullable();
            });
        }

        if (! Schema::hasColumn('core.products', 'halal')) {
            Schema::table('core.products', function (Blueprint $table) {
                $table->string('halal', 50)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('core.products', function (Blueprint $table) {
            $table->dropColumn('production_name');
            $table->dropColumn('botanical_name');
            $table->dropColumn('fda');
            $table->dropColumn('fssc');
            $table->dropColumn('kosher');
            $table->dropColumn('halal');
        });
    }
};
