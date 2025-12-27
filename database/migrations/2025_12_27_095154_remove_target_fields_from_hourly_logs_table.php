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
        Schema::table('hourly_logs', function (Blueprint $table) {
            $table->dropColumn([
                'target_qty_normal',
                'target_qty_reject',
                'target_grades',
                'target_grade',
                'target_ukuran',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hourly_logs', function (Blueprint $table) {
            $table->integer('target_qty_normal')->nullable()->comment('Target: normal quantity (SJ type)');
            $table->integer('target_qty_reject')->nullable()->comment('Target: reject quantity (SJ type)');
            $table->json('target_grades')->nullable()->comment('Target: grades object (PD type)');
            $table->string('target_grade')->nullable()->comment('Target: grade label (Film type)');
            $table->string('target_ukuran')->nullable()->comment('Target: size/dimension (CNC/DS2 type)');
        });
    }
};
