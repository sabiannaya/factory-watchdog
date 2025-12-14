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
        Schema::table('production_machine_groups', function (Blueprint $table) {
            $table->json('default_targets')->nullable()->after('default_target')->comment('JSON object storing default targets for each input field');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_machine_groups', function (Blueprint $table) {
            $table->dropColumn('default_targets');
        });
    }
};

