<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Production;
use App\Models\ProductionMachineGroup;
use App\Models\MachineGroup;
use App\Models\DailyTargetValue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TargetControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_displays_targets_index_page_with_productions(): void
    {
        Production::factory()->count(3)->create();

        $response = $this->get('/data-management/targets');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Targets/Index')
            ->has('productions', 3)
        );
    }

    public function test_displays_machine_groups_when_production_is_selected(): void
    {
        $production = Production::factory()->create();
        $machineGroup = MachineGroup::factory()->create([
            'input_config' => ['fields' => ['qty', 'qty_normal']],
        ]);
        $pmg = ProductionMachineGroup::factory()->create([
            'production_id' => $production->production_id,
            'machine_group_id' => $machineGroup->machine_group_id,
            'default_targets' => ['qty' => 100, 'qty_normal' => 80],
        ]);

        $response = $this->get('/data-management/targets?production_id=' . $production->production_id);

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('machineGroups', 1)
            ->where('selectedProductionId', $production->production_id)
        );
    }

    public function test_shows_existing_daily_target_values(): void
    {
        $production = Production::factory()->create();
        $machineGroup = MachineGroup::factory()->create([
            'input_config' => ['fields' => ['qty']],
        ]);
        $pmg = ProductionMachineGroup::factory()->create([
            'production_id' => $production->production_id,
            'machine_group_id' => $machineGroup->machine_group_id,
            'default_targets' => ['qty' => 100],
        ]);

        $today = Carbon::now('Asia/Jakarta')->toDateString();
        DailyTargetValue::create([
            'production_machine_group_id' => $pmg->production_machine_group_id,
            'date' => $today,
            'field_name' => 'qty',
            'target_value' => 120,
            'actual_value' => 115,
            'keterangan' => 'Test note',
        ]);

        $response = $this->get("/data-management/targets?production_id={$production->production_id}&date={$today}");

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('machineGroups', 1)
            ->where('machineGroups.0.daily_values.0.target_value', 120)
            ->where('machineGroups.0.daily_values.0.actual_value', 115)
        );
    }

    public function test_stores_daily_target_values(): void
    {
        $production = Production::factory()->create();
        $machineGroup = MachineGroup::factory()->create([
            'input_config' => ['fields' => ['qty', 'qty_normal']],
        ]);
        $pmg = ProductionMachineGroup::factory()->create([
            'production_id' => $production->production_id,
            'machine_group_id' => $machineGroup->machine_group_id,
            'default_targets' => ['qty' => 100, 'qty_normal' => 80],
        ]);

        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $response = $this->post(
            "/data-management/targets/{$pmg->production_machine_group_id}",
            [
                'date' => $today,
                'values' => [
                    [
                        'field_name' => 'qty',
                        'target_value' => 150,
                        'actual_value' => 145,
                        'keterangan' => 'Good day',
                    ],
                    [
                        'field_name' => 'qty_normal',
                        'target_value' => 120,
                        'actual_value' => null,
                        'keterangan' => null,
                    ],
                ],
            ]
        );

        $response->assertRedirect("/data-management/targets");

        $this->assertDatabaseHas('daily_target_values', [
            'production_machine_group_id' => $pmg->production_machine_group_id,
            'date' => $today,
            'field_name' => 'qty',
            'target_value' => 150,
            'actual_value' => 145,
            'keterangan' => 'Good day',
        ]);

        $this->assertDatabaseHas('daily_target_values', [
            'production_machine_group_id' => $pmg->production_machine_group_id,
            'date' => $today,
            'field_name' => 'qty_normal',
            'target_value' => 120,
            'actual_value' => null,
        ]);
    }

    public function test_updates_existing_daily_target_values(): void
    {
        $pmg = ProductionMachineGroup::factory()->create([
            'default_targets' => ['qty' => 100],
        ]);

        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // Create initial value
        DailyTargetValue::create([
            'production_machine_group_id' => $pmg->production_machine_group_id,
            'date' => $today,
            'field_name' => 'qty',
            'target_value' => 100,
            'actual_value' => 95,
            'keterangan' => 'Old note',
        ]);

        // Update it
        $this->post(
            "/data-management/targets/{$pmg->production_machine_group_id}",
            [
                'date' => $today,
                'values' => [
                    [
                        'field_name' => 'qty',
                        'target_value' => 130,
                        'actual_value' => 128,
                        'keterangan' => 'Updated note',
                    ],
                ],
            ]
        );

        $this->assertDatabaseHas('daily_target_values', [
            'production_machine_group_id' => $pmg->production_machine_group_id,
            'date' => $today,
            'field_name' => 'qty',
            'target_value' => 130,
            'actual_value' => 128,
            'keterangan' => 'Updated note',
        ]);

        // Should only have one record, not two
        $count = DailyTargetValue::where([
            ['production_machine_group_id', '=', $pmg->production_machine_group_id],
            ['date', '=', $today],
            ['field_name', '=', 'qty'],
        ])->count();

        $this->assertEquals(1, $count);
    }
}

