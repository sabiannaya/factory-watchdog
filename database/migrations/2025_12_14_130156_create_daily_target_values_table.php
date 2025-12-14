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
        Schema::create('daily_target_values', function (Blueprint $table) {
            $table->id('daily_target_value_id');
            $table->unsignedBigInteger('production_machine_group_id');
            $table->date('date');
            $table->string('field_name'); // qty, qty_normal, qty_reject, etc.
            $table->integer('target_value')->nullable()->comment('Daily override of target');
            $table->integer('actual_value')->nullable()->comment('Actual output recorded');
            $table->text('keterangan')->nullable()->comment('Description/notes for this field on this date');
            $table->index(['production_machine_group_id', 'date'], 'dtv_pmg_date_idx');
            $table->unique(['production_machine_group_id', 'date', 'field_name'], 'dtv_pmg_date_field_unique');
            $table->timestamps();
            $table->foreign('production_machine_group_id', 'dtv_pmg_fk')
                ->references('production_machine_group_id')
                ->on('production_machine_groups')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_target_values');
    }
};
