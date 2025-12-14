<?php

namespace Database\Seeders;

use App\Models\DailyTarget;
use App\Models\MachineGroup;
use App\Models\Production;
use App\Models\ProductionMachineGroup;
use App\Models\ProductionMachineGroupTarget;
use App\Models\HourlyLog;
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
            'name' => $mgA->name,
            'machine_count' => 3,
        ]);
        $pmg[] = ProductionMachineGroup::factory()->create([
            'production_id' => $p1->production_id,
            'machine_group_id' => $mgB->machine_group_id,
            'name' => $mgB->name,
            'machine_count' => 5,
        ]);
        $pmg[] = ProductionMachineGroup::factory()->create([
            'production_id' => $p1->production_id,
            'machine_group_id' => $mgC->machine_group_id,
            'name' => $mgC->name,
            'machine_count' => 1,
        ]);

        $pmg[] = ProductionMachineGroup::factory()->create([
            'production_id' => $p2->production_id,
            'machine_group_id' => $mgA->machine_group_id,
            'name' => $mgA->name,
            'machine_count' => 2,
        ]);
        $pmg[] = ProductionMachineGroup::factory()->create([
            'production_id' => $p2->production_id,
            'machine_group_id' => $mgB->machine_group_id,
            'name' => $mgB->name,
            'machine_count' => 4,
        ]);
        $pmg[] = ProductionMachineGroup::factory()->create([
            'production_id' => $p2->production_id,
            'machine_group_id' => $mgC->machine_group_id,
            'name' => $mgC->name,
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
            }
        }

        // Create hourly logs for each date in our date range (00:00 - 23:00 Asia/Jakarta)
        foreach ($dates as $date) {
            foreach ($pmg as $entry) {
                for ($m = 1; $m <= max(1, $entry->machine_count); $m++) {
                    for ($h = 0; $h < 24; $h++) {
                        $recorded = Carbon::parse(sprintf('%s %02d:00:00', $date, $h), 'Asia/Jakarta');
                        // simple per-hour target (randomized)
                        $perMachineTarget = (int) max(1, round(($entry->machine_count * rand(5, 30)) / max(1, $entry->machine_count)));
                        $output = max(0, (int) ($perMachineTarget * (0.5 + rand(0, 60) / 100)));

                        HourlyLog::factory()->create([
                            'production_machine_group_id' => $entry->production_machine_group_id,
                            'machine_index' => $m,
                            'recorded_at' => $recorded,
                            'output_value' => $output,
                            'target_value' => $perMachineTarget,
                        ]);
                    }
                }
            }
        }
    }
}
