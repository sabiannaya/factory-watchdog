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
        Schema::create('production_machine_group_targets', function (Blueprint $table) {
            $table->id('production_machine_group_target_id');
            $table->unsignedBigInteger('production_machine_group_id');
            $table->unsignedBigInteger('daily_target_id');
            $table->index('production_machine_group_id', 'pmgt_pmg_idx');
            $table->index('daily_target_id', 'pmgt_daily_idx');
            $table->unique(['production_machine_group_id', 'daily_target_id'], 'pmgt_pmg_daily_unique');
            $table->timestamps();
            $table->foreign('production_machine_group_id', 'pmgt_pmg_fk')->references('production_machine_group_id')->on('production_machine_groups')->onDelete('cascade');
            $table->foreign('daily_target_id')->references('daily_target_id')->on('daily_targets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_machine_group_targets');
    }
};
