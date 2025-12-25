<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\ProductionMachineGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductionDefaultController extends Controller
{
    /**
     * Show production defaults view
     */
    public function index()
    {
        $productions = Production::where('status', 'active')
            ->with(['productionMachineGroups' => function ($query) {
                $query->with('machineGroup');
            }])
            ->orderBy('production_name')
            ->get()
            ->map(function ($production) {
                return [
                    'production_id' => $production->production_id,
                    'production_name' => $production->production_name,
                    'machine_groups' => $production->productionMachineGroups->map(function ($pmg) {
                        return [
                            'production_machine_group_id' => $pmg->production_machine_group_id,
                            'name' => $pmg->machineGroup->name,
                            'machine_count' => $pmg->machine_count,
                            'fields' => $pmg->machineGroup->getInputFields(),
                            'default_targets' => $pmg->default_targets ?? [],
                        ];
                    }),
                ];
            });

        return Inertia::render('Productions/Defaults', [
            'productions' => $productions,
        ]);
    }

    /**
     * Show edit form for production defaults
     */
    public function edit($productionMachineGroupId)
    {
        $pmg = ProductionMachineGroup::with(['machineGroup', 'production'])
            ->findOrFail($productionMachineGroupId);

        return Inertia::render('Productions/EditDefaults', [
            'productionMachineGroup' => $pmg,
            'fields' => $pmg->machineGroup->getInputFields(),
            'defaultTargets' => $pmg->default_targets ?? [],
        ]);
    }

    /**
     * Update production defaults
     */
    public function update(Request $request, $productionMachineGroupId)
    {
        $validated = $request->validate([
            'machine_count' => 'required|integer|min:1',
            'default_targets' => 'required|array',
            'default_targets.*' => 'nullable|integer|min:0',
        ]);

        $pmg = ProductionMachineGroup::findOrFail($productionMachineGroupId);

        $pmg->update([
            'machine_count' => $validated['machine_count'],
            'default_targets' => $validated['default_targets'],
        ]);

        return redirect()->route('productions.defaults.index')
            ->with('success', 'Production defaults updated successfully');
    }
}
