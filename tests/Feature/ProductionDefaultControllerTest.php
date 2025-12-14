<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Production;
use App\Models\ProductionMachineGroup;
use App\Models\MachineGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionDefaultControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_displays_production_defaults_index_page(): void
    {
        $production = Production::factory()->create();
        $machineGroup = MachineGroup::factory()->create([
            'input_config' => ['fields' => ['qty', 'qty_normal', 'qty_reject']],
        ]);
        ProductionMachineGroup::factory()->create([
            'production_id' => $production->production_id,
            'machine_group_id' => $machineGroup->machine_group_id,
            'default_targets' => ['qty' => 100, 'qty_normal' => 80, 'qty_reject' => 20],
        ]);

        $response = $this->get('/data-management/productions/defaults');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('data-management/Productions/Defaults')
            ->has('productions', 1)
            ->where('productions.0.production_name', $production->production_name)
        );
    }

    public function test_shows_only_active_productions(): void
    {
        Production::factory()->create(['status' => 'active']);
        Production::factory()->create(['status' => 'inactive']);

        $response = $this->get('/data-management/productions/defaults');

        $response->assertInertia(fn ($page) => $page
            ->has('productions', 1)
        );
    }

    public function test_displays_edit_defaults_form(): void
    {
        $production = Production::factory()->create();
        $machineGroup = MachineGroup::factory()->create([
            'input_config' => ['fields' => ['qty', 'qty_normal']],
        ]);
        $pmg = ProductionMachineGroup::factory()->create([
            'production_id' => $production->production_id,
            'machine_group_id' => $machineGroup->machine_group_id,
            'machine_count' => 5,
            'default_targets' => ['qty' => 100, 'qty_normal' => 80],
        ]);

        $response = $this->get("/data-management/productions/{$pmg->production_machine_group_id}/defaults/edit");

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('data-management/Productions/EditDefaults')
            ->where('productionMachineGroup.production_machine_group_id', $pmg->production_machine_group_id)
            ->where('productionMachineGroup.machine_count', 5)
            ->where('defaultTargets.qty', 100)
            ->where('defaultTargets.qty_normal', 80)
        );
    }

    public function test_updates_production_defaults(): void
    {
        $pmg = ProductionMachineGroup::factory()->create([
            'machine_count' => 5,
            'default_targets' => ['qty' => 100, 'qty_normal' => 80],
        ]);

        $response = $this->put(
            "/data-management/productions/{$pmg->production_machine_group_id}/defaults",
            [
                'machine_count' => 8,
                'default_targets' => [
                    'qty' => 150,
                    'qty_normal' => 120,
                ],
            ]
        );

        $response->assertRedirect('/data-management/productions/defaults');

        $this->assertDatabaseHas('production_machine_groups', [
            'production_machine_group_id' => $pmg->production_machine_group_id,
            'machine_count' => 8,
        ]);

        $updated = ProductionMachineGroup::find($pmg->production_machine_group_id);
        $this->assertEquals([
            'qty' => 150,
            'qty_normal' => 120,
        ], $updated->default_targets);
    }

    public function test_validates_machine_count_is_required_and_positive(): void
    {
        $pmg = ProductionMachineGroup::factory()->create([
            'machine_count' => 5,
            'default_targets' => ['qty' => 100],
        ]);

        $this->put(
            "/data-management/productions/{$pmg->production_machine_group_id}/defaults",
            [
                'machine_count' => 0,
                'default_targets' => ['qty' => 100],
            ]
        )->assertInvalid('machine_count');

        $this->put(
            "/data-management/productions/{$pmg->production_machine_group_id}/defaults",
            [
                'default_targets' => ['qty' => 100],
            ]
        )->assertInvalid('machine_count');
    }

    public function test_allows_null_default_targets(): void
    {
        $pmg = ProductionMachineGroup::factory()->create([
            'machine_count' => 5,
            'default_targets' => ['qty' => 100, 'qty_normal' => 80],
        ]);

        $response = $this->put(
            "/data-management/productions/{$pmg->production_machine_group_id}/defaults",
            [
                'machine_count' => 5,
                'default_targets' => [
                    'qty' => null,
                    'qty_normal' => 120,
                ],
            ]
        );

        $response->assertRedirect();

        $updated = ProductionMachineGroup::find($pmg->production_machine_group_id);
        $this->assertNull($updated->default_targets['qty']);
        $this->assertEquals(120, $updated->default_targets['qty_normal']);
    }
}

