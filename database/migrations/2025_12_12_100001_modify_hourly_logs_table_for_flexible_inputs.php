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
            // Keep output_value for backward compatibility and simple cases
            // Add flexible input fields
            $table->integer('qty')->nullable()->after('output_value')->comment('Simple quantity input');
            $table->integer('qty_normal')->nullable()->after('qty')->comment('Normal quantity (for SJ type)');
            $table->integer('qty_reject')->nullable()->after('qty_normal')->comment('Reject quantity (for SJ type)');
            $table->json('grades')->nullable()->after('qty_reject')->comment('Multiple grades (for PD type: faceback, opc, ppc)');
            $table->string('grade')->nullable()->after('grades')->comment('Single grade (for Film type)');
            $table->string('ukuran')->nullable()->after('grade')->comment('Size/dimension (for CNC/DS2 type)');
            $table->text('keterangan')->nullable()->after('ukuran')->comment('Description/notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hourly_logs', function (Blueprint $table) {
            $table->dropColumn([
                'qty',
                'qty_normal',
                'qty_reject',
                'grades',
                'grade',
                'ukuran',
                'keterangan',
            ]);
        });
    }
};

