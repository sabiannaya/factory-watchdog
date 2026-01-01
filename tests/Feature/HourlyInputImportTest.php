<?php

use App\Models\HourlyLog;
use App\Models\MachineGroup;
use App\Models\Production;
use App\Models\ProductionMachineGroup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role_id' => Role::whereSlug(Role::SUPER)->first()->role_id]);
});

test('can download import template', function () {
    $response = $this->actingAs($this->user)->get('/input/import/template');

    $response->assertStatus(200);
    $response->assertDownload('hourly-input-import-template.xlsx');
});

test('cannot import without authentication', function () {
    $response = $this->get('/input/import/template');

    $response->assertRedirect('/login');
});

test('execute import creates hourly logs', function () {
    // Create necessary data
    $production = Production::factory()->create(['production_name' => 'Plywood', 'status' => 'active']);
    $machineGroup = MachineGroup::factory()->create(['name' => 'db']);
    $pmg = ProductionMachineGroup::factory()->create([
        'production_id' => $production->production_id,
        'machine_group_id' => $machineGroup->machine_group_id,
    ]);

    $rows = [
        [
            'production_machine_group_id' => $pmg->production_machine_group_id,
            'recorded_at' => '2026-01-15 08:00',
            'qty_normal' => 100,
            'qty_reject' => 5,
            'keterangan' => 'Test import',
        ],
        [
            'production_machine_group_id' => $pmg->production_machine_group_id,
            'recorded_at' => '2026-01-15 09:00',
            'qty_normal' => 150,
            'qty_reject' => 10,
            'keterangan' => null,
        ],
    ];

    $response = $this->actingAs($this->user)->postJson('/input/import/execute', [
        'rows' => $rows,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'imported' => 2,
    ]);

    // Verify records were created
    expect(HourlyLog::count())->toBe(2);
    expect(HourlyLog::where('output_qty_normal', 100)->exists())->toBeTrue();
    expect(HourlyLog::where('output_qty_normal', 150)->exists())->toBeTrue();
});

test('execute import prevents duplicates', function () {
    // Create necessary data
    $production = Production::factory()->create(['production_name' => 'Plywood', 'status' => 'active']);
    $machineGroup = MachineGroup::factory()->create(['name' => 'db']);
    $pmg = ProductionMachineGroup::factory()->create([
        'production_id' => $production->production_id,
        'machine_group_id' => $machineGroup->machine_group_id,
    ]);

    // Create existing entry
    HourlyLog::factory()->create([
        'production_machine_group_id' => $pmg->production_machine_group_id,
        'recorded_at' => '2026-01-15 08:00:00',
    ]);

    $rows = [
        [
            'production_machine_group_id' => $pmg->production_machine_group_id,
            'recorded_at' => '2026-01-15 08:00', // Duplicate
            'qty_normal' => 100,
            'qty_reject' => 5,
            'keterangan' => null,
        ],
    ];

    $response = $this->actingAs($this->user)->postJson('/input/import/execute', [
        'rows' => $rows,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'imported' => 0,
        'skipped' => 1,
    ]);

    // Verify no new records were created
    expect(HourlyLog::count())->toBe(1);
});
