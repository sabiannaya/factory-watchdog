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

            // Measurements (English column names)
            $table->decimal('glue_kg', 8, 2)->nullable();
            $table->decimal('hardener_kg', 8, 2)->nullable();
            $table->decimal('powder_kg', 8, 2)->nullable();
            $table->decimal('colorant_kg', 8, 2)->nullable();
            $table->decimal('anti_termite_kg', 8, 2)->nullable();
            $table->string('viscosity')->nullable();
            $table->unsignedInteger('washes_per_day')->nullable();
            $table->decimal('glue_loss_kg', 8, 2)->nullable();

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
