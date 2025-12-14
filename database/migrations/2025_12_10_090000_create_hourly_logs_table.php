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
        Schema::create('hourly_logs', function (Blueprint $table) {
            $table->id('hourly_log_id');
            $table->unsignedBigInteger('production_machine_group_id');
            $table->unsignedInteger('machine_index')->nullable();
            $table->timestamp('recorded_at');
            $table->integer('output_value')->default(0);
            $table->integer('target_value')->nullable();
            $table->timestamps();

            $table->index(['production_machine_group_id', 'recorded_at']);
            $table->foreign('production_machine_group_id')->references('production_machine_group_id')->on('production_machine_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hourly_logs');
    }
};
