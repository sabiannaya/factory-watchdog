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
        Schema::table('glue_spreaders', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('notes');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('glue_spreaders', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['is_active', 'deleted_by']);
        });
    }
};
