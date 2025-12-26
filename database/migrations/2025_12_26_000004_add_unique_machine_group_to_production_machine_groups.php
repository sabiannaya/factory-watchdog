<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Each machine group can only be attached to one production at a time.
     */
    public function up(): void
    {
        // First, clean up duplicate entries by keeping only the first assignment
        $duplicates = DB::select('
            SELECT machine_group_id 
            FROM production_machine_groups 
            GROUP BY machine_group_id 
            HAVING COUNT(*) > 1
        ');

        foreach ($duplicates as $duplicate) {
            // Get all entries for this machine group
            $entries = DB::table('production_machine_groups')
                ->where('machine_group_id', $duplicate->machine_group_id)
                ->orderBy('production_machine_group_id', 'asc')
                ->get();

            // Keep the first one, delete the rest
            $idsToDelete = $entries->skip(1)->pluck('production_machine_group_id')->toArray();

            if (! empty($idsToDelete)) {
                DB::table('production_machine_groups')
                    ->whereIn('production_machine_group_id', $idsToDelete)
                    ->delete();
            }
        }

        Schema::table('production_machine_groups', function (Blueprint $table) {
            // Ensure a machine group can only belong to one production
            $table->unique('machine_group_id', 'unique_machine_group_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_machine_groups', function (Blueprint $table) {
            $table->dropUnique('unique_machine_group_assignment');
        });
    }
};
