<?php

namespace App\Http\Controllers;

use App\Models\MachineGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MachineGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = (int) request()->input('per_page', 10);
        $q = trim((string) request()->input('q', ''));
        $sort = request()->input('sort', 'name');
        $direction = strtolower(request()->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $cursor = request()->input('cursor');

        $allowed = ['name', 'created_at'];
        if (! in_array($sort, $allowed, true)) $sort = 'name';

        $query = MachineGroup::query()
            ->select(['machine_group_id', 'name', 'description', 'created_at'])
            ->with(['productionMachineGroups.production'])
            ->orderBy($sort, $direction)
            ->orderBy('machine_group_id', 'asc');

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%");
        }

        $p = $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

        $data = collect($p->items())->map(function($mg) {
            $total = 0;
            $active = 0;
            foreach ($mg->productionMachineGroups ?? [] as $pmg) {
                $count = (int) ($pmg->machine_count ?? 0);
                $total += $count;
                if ($pmg->production && ($pmg->production->status ?? '') === 'active') {
                    $active += $count;
                }
            }

            return [
                'machine_group_id' => $mg->machine_group_id,
                'name' => $mg->name,
                'description' => $mg->description,
                'created_at' => $mg->created_at->toDateString(),
                'total_machines' => $total,
                'active_machines' => $active,
                'inactive_machines' => max(0, $total - $active),
            ];
        })->all();

        return Inertia::render('data-management/Machine', [
            'machineGroups' => [
                'data' => $data,
                'next_cursor' => $p->nextCursor()?->encode() ?? null,
                'prev_cursor' => $p->previousCursor()?->encode() ?? null,
            ],
            'meta' => [
                'sort' => $sort,
                'direction' => $direction,
                'q' => $q,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(MachineGroup $machine_group)
    {
        $allocations = $machine_group->productionMachineGroups()->with('production')->get()->map(function ($pmg) {
            return [
                'production_id' => $pmg->production_id,
                'production_name' => $pmg->production?->name ?? null,
                'machine_count' => (int) ($pmg->machine_count ?? 0),
            ];
        });

        $total = $allocations->sum('machine_count');

        return Inertia::render('data-management/MachineShow', [
            'machineGroup' => [
                'machine_group_id' => $machine_group->machine_group_id,
                'name' => $machine_group->name,
                'description' => $machine_group->description,
                'total_machines' => $total,
                'allocations' => $allocations,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MachineGroup $machineGroup)
    {
        return Inertia::render('data-management/MachineEdit', [
            'machineGroup' => [
                'machine_group_id' => $machineGroup->machine_group_id,
                'name' => $machineGroup->name,
                'description' => $machineGroup->description,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MachineGroup $machineGroup)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $machineGroup->update($data);

        return redirect()->route('data-management.machine.index')->with('success', 'Machine group updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MachineGroup $machineGroup)
    {
        $machineGroup->delete();

        return redirect()->route('data-management.machine.index')->with('success', 'Machine group deleted.');
    }
}
