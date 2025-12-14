<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\ProductionMachineGroup;
use App\Models\DailyTargetValue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class TargetController extends Controller
{
    /**
     * Show targets view for a specific date
     */
    public function index(Request $request)
    {
        $date = $request->query('date', now('Asia/Jakarta')->toDateString());
        $productionId = $request->query('production_id');

        $productions = Production::query()
            ->where('status', 'active')
            ->orderBy('production_name')
            ->get(['production_id', 'production_name']);

        $machineGroups = null;
        if ($productionId) {
            $machineGroups = ProductionMachineGroup::where('production_id', $productionId)
                ->with('machineGroup')
                ->get()
                ->map(function ($pmg) use ($date) {
                    $fields = $pmg->machineGroup->getInputFields();

                    $values = DailyTargetValue::where([
                        ['production_machine_group_id', '=', $pmg->production_machine_group_id],
                        ['date', '=', $date],
                    ])->get()->keyBy('field_name');

                    return [
                        'production_machine_group_id' => $pmg->production_machine_group_id,
                        'name' => $pmg->name,
                        'machine_count' => $pmg->machine_count,
                        'fields' => $fields,
                        'default_targets' => $pmg->default_targets ?? [],
                        'daily_values' => $values->map(function ($val) {
                            return [
                                'field_name' => $val->field_name,
                                'target_value' => $val->target_value,
                                'actual_value' => $val->actual_value,
                                'keterangan' => $val->keterangan,
                            ];
                        })->values()->all(),
                    ];
                });
        }

        return Inertia::render('data-management/Target', [
            'productions' => $productions,
            'machineGroups' => $machineGroups,
            'selectedProductionId' => $productionId ? (int) $productionId : null,
            'selectedDate' => $date,
        ]);
    }

    /**
     * Show the form for creating daily targets for all machine groups
     */
    public function create()
    {
        $productions = Production::query()
            ->where('status', 'active')
            ->with(['productionMachineGroups.machineGroup'])
            ->orderBy('production_name')
            ->get()
            ->map(function ($production) {
                return [
                    'production_id' => $production->production_id,
                    'production_name' => $production->production_name,
                    'machine_groups' => $production->productionMachineGroups->map(function ($pmg) {
                        return [
                            'production_machine_group_id' => $pmg->production_machine_group_id,
                            'name' => $pmg->name,
                            'machine_count' => $pmg->machine_count,
                            'default_targets' => $pmg->default_targets ?? [],
                            'fields' => $pmg->machineGroup->getInputFields(),
                            'machineGroup' => [
                                'name' => $pmg->machineGroup->name,
                                'description' => $pmg->machineGroup->description,
                                'input_config' => $pmg->machineGroup->input_config,
                            ],
                        ];
                    })->all(),
                ];
            });

        return Inertia::render('data-management/Targets/Create', [
            'productions' => $productions,
        ]);
    }

    /**
     * Show edit form for daily targets for a specific machine group
     */
    public function edit(Request $request, $productionMachineGroupId)
    {
        $date = $request->query('date', now('Asia/Jakarta')->toDateString());

        $pmg = ProductionMachineGroup::with(['machineGroup', 'production'])
            ->findOrFail($productionMachineGroupId);

        $fields = $pmg->machineGroup->getInputFields();

        // Get existing values for the date
        $existingValues = DailyTargetValue::where([
            ['production_machine_group_id', '=', $productionMachineGroupId],
            ['date', '=', $date],
        ])->get()->keyBy('field_name');

        $values = [];
        foreach ($fields as $field) {
            $existing = $existingValues->get($field);
            $values[] = [
                'field_name' => $field,
                'target_value' => $existing?->target_value ?? $pmg->default_targets[$field] ?? null,
                'actual_value' => $existing?->actual_value ?? null,
                'keterangan' => $existing?->keterangan ?? null,
            ];
        }

        return Inertia::render('data-management/Targets/Edit', [
            'productionMachineGroup' => [
                'production_machine_group_id' => $pmg->production_machine_group_id,
                'name' => $pmg->name,
                'machine_count' => $pmg->machine_count,
                'default_targets' => $pmg->default_targets ?? [],
                'production' => [
                    'production_id' => $pmg->production->production_id,
                    'production_name' => $pmg->production->production_name,
                ],
                'machineGroup' => [
                    'name' => $pmg->machineGroup->name,
                    'description' => $pmg->machineGroup->description,
                ],
            ],
            'fields' => $fields,
            'values' => $values,
            'date' => $date,
        ]);
    }

    /**
     * Store daily target values for all machine groups in a production
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'production_id' => 'required|integer|exists:productions,production_id',
            'date' => 'required|date',
            'machine_groups' => 'required|array',
            'machine_groups.*.production_machine_group_id' => 'required|integer|exists:production_machine_groups,production_machine_group_id',
            'machine_groups.*.values' => 'required|array',
            'machine_groups.*.values.*.field_name' => 'required|string',
            'machine_groups.*.values.*.target_value' => 'nullable|integer|min:0',
            'machine_groups.*.values.*.actual_value' => 'nullable|integer|min:0',
            'machine_groups.*.values.*.keterangan' => 'nullable|string|max:500',
        ]);

        $date = Carbon::parse($validated['date'], 'Asia/Jakarta')->toDateString();

        foreach ($validated['machine_groups'] as $machineGroup) {
            $pmgId = $machineGroup['production_machine_group_id'];

            foreach ($machineGroup['values'] as $value) {
                DailyTargetValue::updateOrCreate(
                    [
                        'production_machine_group_id' => $pmgId,
                        'date' => $date,
                        'field_name' => $value['field_name'],
                    ],
                    [
                        'target_value' => $value['target_value'] ?? null,
                        'actual_value' => $value['actual_value'] ?? null,
                        'keterangan' => $value['keterangan'] ?? null,
                    ]
                );
            }
        }

        return redirect()
            ->route('data-management.targets.index', ['production_id' => $validated['production_id'], 'date' => $date])
            ->with('success', 'Daily targets saved successfully');
    }

    /**
     * Update daily target values for a specific machine group
     */
    public function update(Request $request, $productionMachineGroupId)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'values' => 'required|array',
            'values.*.field_name' => 'required|string',
            'values.*.target_value' => 'nullable|integer|min:0',
            'values.*.actual_value' => 'nullable|integer|min:0',
            'values.*.keterangan' => 'nullable|string|max:500',
        ]);

        $date = Carbon::parse($validated['date'], 'Asia/Jakarta')->toDateString();

        foreach ($validated['values'] as $value) {
            DailyTargetValue::updateOrCreate(
                [
                    'production_machine_group_id' => $productionMachineGroupId,
                    'date' => $date,
                    'field_name' => $value['field_name'],
                ],
                [
                    'target_value' => $value['target_value'] ?? null,
                    'actual_value' => $value['actual_value'] ?? null,
                    'keterangan' => $value['keterangan'] ?? null,
                ]
            );
        }

        $pmg = ProductionMachineGroup::findOrFail($productionMachineGroupId);

        return redirect()
            ->route('data-management.targets.index', ['production_id' => $pmg->production_id, 'date' => $date])
            ->with('success', 'Daily targets updated successfully');
    }
}

