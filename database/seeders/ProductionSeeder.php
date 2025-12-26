<?php

namespace Database\Seeders;

use App\Models\DailyTarget;
use App\Models\DailyTargetValue;
use App\Models\HourlyLog;
use App\Models\MachineGroup;
use App\Models\Production;
use App\Models\ProductionMachineGroup;
use App\Models\ProductionMachineGroupTarget;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // Create machine group types (processes) using factory-generated creative names
        $mgA = MachineGroup::factory()->create();
        $mgB = MachineGroup::factory()->create();
        $mgC = MachineGroup::factory()->create();

        // Create productions
        $p1 = Production::factory()->create(['production_name' => 'Production 1']);
        $p2 = Production::factory()->create(['production_name' => 'Production 2']);

        // Map machine groups to productions with machine counts per user's example
        $pmg = [];
        $pmg[] = ProductionMachineGroup::factory()->create([
            'production_id' => $p1->production_id,
            'machine_group_id' => $mgA->machine_group_id,
            'machine_count' => 3,
        ]);

        $pmg[] = ProductionMachineGroup::factory()->create([
            'production_id' => $p2->production_id,
            'machine_group_id' => $mgB->machine_group_id,
            'machine_count' => 4,
        ]);
        $pmg[] = ProductionMachineGroup::factory()->create([
            'production_id' => $p2->production_id,
            'machine_group_id' => $mgC->machine_group_id,
            'machine_count' => 2,
        ]);

        // Create daily targets for yesterday -> next day (e.g., 10 Dec -> 12 Dec)
        $dates = collect([-1, 0, 1])->map(function ($d) {
            return Carbon::now('Asia/Jakarta')->addDays($d)->toDateString();
        });

        foreach ($dates as $date) {
            $daily = DailyTarget::factory()->create([
                'date' => $date,
            ]);

            foreach ($pmg as $entry) {
                // Create somewhat realistic target/actual values based on machine_count
                $target = $entry->machine_count * rand(5, 30);
                $actual = max(0, (int) ($target * (0.6 + rand(0, 40) / 100)));

                $daily->update(['target_value' => $daily->target_value ?? $target]);

                ProductionMachineGroupTarget::factory()->create([
                    'production_machine_group_id' => $entry->production_machine_group_id,
                    'daily_target_id' => $daily->daily_target_id,
                ]);

                // Create DailyTargetValue entries for common fields so UI can compute hourly targets
                $dailyTargetTotal = (int) max(0, round($target * 8)); // approximate daily target
                $dailyTargetNormal = (int) round($dailyTargetTotal * 0.9);
                $dailyTargetReject = max(0, $dailyTargetTotal - $dailyTargetNormal);

                DailyTargetValue::create([
                    'production_machine_group_id' => $entry->production_machine_group_id,
                    'date' => $date,
                    'field_name' => 'qty_normal',
                    'target_value' => $dailyTargetNormal,
                    'actual_value' => null,
                ]);

                DailyTargetValue::create([
                    'production_machine_group_id' => $entry->production_machine_group_id,
                    'date' => $date,
                    'field_name' => 'qty_reject',
                    'target_value' => $dailyTargetReject,
                    'actual_value' => null,
                ]);
            }
        }

        // Create hourly logs (group-level, not per machine)
        foreach ($dates as $date) {
            foreach ($pmg as $entry) {
                for ($h = 0; $h < 24; $h++) {
                    $recorded = Carbon::parse(sprintf('%s %02d:00:00', $date, $h), 'Asia/Jakarta');

                    // Generate realistic hourly targets and outputs per machine group
                    $baseTarget = (int) max(5, round($entry->machine_count * rand(10, 25)));

                    // Generate normal/reject breakdown (90% normal, 10% reject roughly)
                    $targetQtyNormal = (int) round($baseTarget * 0.9);
                    $targetQtyReject = max(1, $baseTarget - $targetQtyNormal);

                    $outputQtyNormal = max(0, (int) ($targetQtyNormal * (0.7 + rand(0, 40) / 100)));
                    $outputQtyReject = max(0, (int) ($targetQtyReject * (0.5 + rand(0, 50) / 100)));

                    HourlyLog::create([
                        'production_machine_group_id' => $entry->production_machine_group_id,
                        'recorded_at' => $recorded,
                        'output_qty_normal' => $outputQtyNormal,
                        'output_qty_reject' => $outputQtyReject,
                        'target_qty_normal' => $targetQtyNormal,
                        'target_qty_reject' => $targetQtyReject,
                    ]);
                }
            }
        }
    }
}
