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
            // Drop generic output_value and target_value - replace with field-specific columns
            $table->dropColumn(['output_value', 'target_value']);

            // Output columns for each field type
            $table->integer('output_qty_normal')->nullable()->comment('Output: normal quantity (SJ type)');
            $table->integer('output_qty_reject')->nullable()->comment('Output: reject quantity (SJ type)');
            $table->json('output_grades')->nullable()->comment('Output: grades object (PD type: faceback, opc, ppc)');
            $table->string('output_grade')->nullable()->comment('Output: grade label (Film type)');
            $table->string('output_ukuran')->nullable()->comment('Output: size/dimension (CNC/DS2 type)');

            // Target columns for each field type
            $table->integer('target_qty_normal')->nullable()->comment('Target: normal quantity (SJ type)');
            $table->integer('target_qty_reject')->nullable()->comment('Target: reject quantity (SJ type)');
            $table->json('target_grades')->nullable()->comment('Target: grades object (PD type)');
            $table->string('target_grade')->nullable()->comment('Target: grade label (Film type)');
            $table->string('target_ukuran')->nullable()->comment('Target: size/dimension (CNC/DS2 type)');

            // Additional fields
            $table->text('keterangan')->nullable()->comment('Description/notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hourly_logs', function (Blueprint $table) {
            // Restore generic columns
            $table->integer('output_value')->default(0);
            $table->integer('target_value')->nullable();

            // Drop field-specific columns
            $table->dropColumn([
                'output_qty_normal',
                'output_qty_reject',
                'output_grades',
                'output_grade',
                'output_ukuran',
                'target_qty_normal',
                'target_qty_reject',
                'target_grades',
                'target_grade',
                'target_ukuran',
                'keterangan',
            ]);
        });
    }
};
