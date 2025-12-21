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
        Schema::create('glue_spreaders', function (Blueprint $table) {
            $table->id('glue_spreader_id');
            $table->string('name');
            $table->string('model')->nullable();
            $table->unsignedInteger('capacity_ml')->nullable();
            $table->unsignedInteger('speed_mpm')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glue_spreaders');
    }
};
