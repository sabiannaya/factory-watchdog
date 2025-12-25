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
        Schema::create('production_machine_groups', function (Blueprint $table) {
            $table->id('production_machine_group_id');
            $table->unsignedBigInteger('production_id');
            $table->unsignedBigInteger('machine_group_id');
            $table->string('name')->nullable();
            $table->unsignedInteger('machine_count')->default(1);
            $table->index('production_id', 'pmg_production_idx');
            $table->index('machine_group_id', 'pmg_machine_idx');
            $table->unique(['production_id', 'machine_group_id'], 'pmg_prod_machine_unique');
            $table->timestamps();
            $table->foreign('machine_group_id')->references('machine_group_id')->on('machine_groups')->onDelete('cascade');
            // Assuming there is a 'productions' table with 'production_id' as primary key
            $table->foreign('production_id')->references('production_id')->on('productions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_machine_groups');
    }
};
