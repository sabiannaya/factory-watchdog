<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMachineGroupRequest;
use App\Http\Requests\UpdateMachineGroupRequest;
use App\Models\MachineGroup;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MachineGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Staff users can only see machine groups assigned to their productions
        $isStaff = $user->isStaff();
        $accessibleMachineGroupIds = [];
        if ($isStaff) {
            $accessibleMachineGroupIds = \App\Models\ProductionMachineGroup::query()
                ->whereIn('production_id', $user->accessibleProductionIds())
                ->pluck('machine_group_id')
                ->unique()
                ->toArray();
        }

        $perPage = (int) request()->input('per_page', 10);
        $q = trim((string) request()->input('q', ''));
        $sort = request()->input('sort', 'name');
        $direction = strtolower(request()->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $cursor = request()->input('cursor');

        $allowed = ['name', 'created_at'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'name';
        }

        $query = MachineGroup::query()
            ->select(['machine_group_id', 'name', 'description', 'input_config', 'created_at'])
            ->with(['productionMachineGroups.production'])
            ->orderBy($sort, $direction)
            ->orderBy('machine_group_id', 'asc');

        // Filter for staff users
        if ($isStaff) {
            $query->whereIn('machine_group_id', $accessibleMachineGroupIds);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $p = $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

        $data = collect($p->items())->map(function ($mg) {
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
                'input_config' => $mg->input_config,
                'created_at' => $mg->created_at->toDateString(),
                'total_machines' => $total,
                'active_machines' => $active,
                'inactive_machines' => max(0, $total - $active),
            ];
        })->all();

        return Inertia::render('data-management/Machines/Index', [
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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Staff users can only view machine groups assigned to their productions
        if ($user->isStaff() && ! $user->canAccessMachineGroup($machine_group->machine_group_id)) {
            abort(403, 'You do not have access to this machine group.');
        }

        $allocations = $machine_group->productionMachineGroups()->with('production')->get()->map(function ($pmg) {
            return [
                'production_id' => $pmg->production_id,
                'production_name' => $pmg->production?->production_name ?? null,
                'machine_count' => (int) ($pmg->machine_count ?? 0),
            ];
        });

        $total = $allocations->sum('machine_count');

        return Inertia::render('data-management/Machines/Show', [
            'machineGroup' => [
                'machine_group_id' => $machine_group->machine_group_id,
                'name' => $machine_group->name,
                'description' => $machine_group->description,
                'input_config' => $machine_group->input_config,
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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Only Super users can create machine groups
        if (! $user->isSuper()) {
            abort(403, 'Only Super users can create machine groups.');
        }

        return Inertia::render('data-management/Machines/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMachineGroupRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Only Super users can create machine groups
        if (! $user->isSuper()) {
            abort(403, 'Only Super users can create machine groups.');
        }

        $data = $request->validated();

        // Ensure input_config has at least 'fields' if provided
        if (isset($data['input_config']) && empty($data['input_config']['fields'])) {
            $data['input_config'] = null;
        }

        MachineGroup::create($data);

        return redirect()->route('data-management.machine.index')->with('success', 'Machine group created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MachineGroup $machineGroup)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Only Super users can edit machine groups
        if (! $user->isSuper()) {
            abort(403, 'Only Super users can edit machine groups.');
        }

        return Inertia::render('data-management/Machines/Edit', [
            'machineGroup' => [
                'machine_group_id' => $machineGroup->machine_group_id,
                'name' => $machineGroup->name,
                'description' => $machineGroup->description,
                'input_config' => $machineGroup->input_config ?? ['type' => 'qty_only', 'fields' => ['qty']],
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMachineGroupRequest $request, MachineGroup $machineGroup)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Only Super users can update machine groups
        if (! $user->isSuper()) {
            abort(403, 'Only Super users can update machine groups.');
        }

        $data = $request->validated();

        // Ensure input_config has at least 'fields' if provided
        if (isset($data['input_config']) && empty($data['input_config']['fields'])) {
            $data['input_config'] = null;
        }

        $machineGroup->update($data);

        return redirect()->route('data-management.machine.index')->with('success', 'Machine group updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MachineGroup $machineGroup)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Only Super users can delete machine groups
        if (! $user->canDelete()) {
            abort(403, 'Only Super users can delete machine groups.');
        }

        $machineGroup->delete();

        return redirect()->route('data-management.machine.index')->with('success', 'Machine group deleted.');
    }
}
