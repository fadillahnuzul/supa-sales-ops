<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core.employee_segmentations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('segmentation_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core.employee_segmentations');
    }
};
