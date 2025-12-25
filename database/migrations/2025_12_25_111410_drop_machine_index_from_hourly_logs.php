<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hourly_logs', function (Blueprint $table) {
            if (Schema::hasColumn('hourly_logs', 'machine_index')) {
                $table->dropColumn('machine_index');
            }

            // Deduplicate existing rows before adding a unique index
            $duplicates = DB::table('hourly_logs')
                ->select('production_machine_group_id', 'recorded_at', DB::raw('COUNT(*) as cnt'))
                ->groupBy('production_machine_group_id', 'recorded_at')
                ->having('cnt', '>', 1)
                ->get();

            foreach ($duplicates as $dup) {
                $idsToKeep = DB::table('hourly_logs')
                    ->where('production_machine_group_id', $dup->production_machine_group_id)
                    ->where('recorded_at', $dup->recorded_at)
                    ->orderBy('hourly_log_id', 'asc')
                    ->limit(1)
                    ->pluck('hourly_log_id')
                    ->all();

                DB::table('hourly_logs')
                    ->where('production_machine_group_id', $dup->production_machine_group_id)
                    ->where('recorded_at', $dup->recorded_at)
                    ->whereNotIn('hourly_log_id', $idsToKeep)
                    ->delete();
            }

            // Ensure uniqueness per group per hour moving forward
            $table->unique(['production_machine_group_id', 'recorded_at'], 'hourly_logs_group_hour_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hourly_logs', function (Blueprint $table) {
            // Recreate column if rolling back
            $table->unsignedInteger('machine_index')->nullable()->after('production_machine_group_id');

            // Drop unique index
            $table->dropUnique('hourly_logs_group_hour_unique');
        });
    }
};
