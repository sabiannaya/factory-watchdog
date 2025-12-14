<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\MachineGroup;
use App\Models\ProductionMachineGroup;
use App\Models\DailyTarget;
use App\Models\ProductionMachineGroupTarget;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Use cursor pagination for efficiency on large datasets.
        $perPage = (int) request()->input('per_page', 10);
        $q = trim((string) request()->input('q', ''));
        $sort = request()->input('sort', 'production_name');
        $direction = strtolower(request()->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $cursor = request()->input('cursor');

        // Whitelist sortable columns to avoid SQL injection
        $allowedSorts = ['production_name', 'status', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'production_name';
        }

        $query = Production::query()
            ->select(['production_id', 'production_name', 'status', 'created_at'])
            ->withCount('productionMachineGroups')
            ->orderBy($sort, $direction)
            ->orderBy('production_id', 'asc');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('production_name', 'like', "%{$q}%")
                    ->orWhere('status', 'like', "%{$q}%");
            });
        }
        
        // server-side status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $paginator = $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

        // Map items to simple arrays for the frontend
        $data = collect($paginator->items())->map(function ($p) {
            return [
                'production_id' => $p->production_id,
                'production_name' => $p->production_name,
                'status' => $p->status,
                'created_at' => $p->created_at->toDateString(),
                'process_count' => $p->production_machine_groups_count ?? 0,
            ];
        })->all();

        $productions = [
            'data' => $data,
            'next_cursor' => $paginator->nextCursor()?->encode() ?? null,
            'prev_cursor' => $paginator->previousCursor()?->encode() ?? null,
        ];

        return Inertia::render('data-management/Production', [
            'productions' => $productions,
            'meta' => [
                'sort' => $sort,
                'direction' => $direction,
                'q' => $q,
                'per_page' => $perPage,
                'status' => $request->input('status'),
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
     * Display the specified resource.
     */
    public function show(Production $production)
    {
        // Load related counts and attached machine groups so frontend can show summary and defaults
        $production->loadCount('productionMachineGroups');

        $attached = $production->productionMachineGroups()->with('machineGroup')->get()->map(function ($pmg) {
            return [
                'production_machine_group_id' => $pmg->production_machine_group_id,
                'machine_group_id' => $pmg->machine_group_id,
                'machine_count' => $pmg->machine_count,
                'default_target' => $pmg->default_target ?? null,
                'pmg_name' => $pmg->name,
                'group_name' => $pmg->machineGroup?->name ?? null,
                'group_description' => $pmg->machineGroup?->description ?? null,
            ];
        })->all();

        return Inertia::render('data-management/ProductionShow', [
            'production' => [
                'production_id' => $production->production_id,
                'production_name' => $production->production_name,
                'status' => $production->status,
                'process_count' => $production->production_machine_groups_count ?? 0,
                'machine_groups' => $attached,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Production $production)
    {
        // Load all machine groups and the production's attached groups
        $machineGroups = MachineGroup::query()->select(['machine_group_id', 'name', 'description'])->get()->map(function ($m) {
            return [
                'machine_group_id' => $m->machine_group_id,
                'name' => $m->name,
                'description' => $m->description,
            ];
        })->all();

        $attached = $production->productionMachineGroups()->get()->map(function ($pmg) {
            return [
                'production_machine_group_id' => $pmg->production_machine_group_id,
                'machine_group_id' => $pmg->machine_group_id,
                'machine_count' => $pmg->machine_count,
                'default_target' => $pmg->default_target ?? null,
                'name' => $pmg->name,
            ];
        })->keyBy('machine_group_id')->all();

        return Inertia::render('data-management/ProductionEdit', [
            'production' => [
                'production_id' => $production->production_id,
                'production_name' => $production->production_name,
                'status' => $production->status,
            ],
            'machine_groups' => $machineGroups,
            'attached_groups' => $attached,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Production $production)
    {
        $data = $request->validate([
            'production_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'machine_groups' => ['nullable', 'array'],
            'machine_groups.*.machine_group_id' => ['required_with:machine_groups', 'integer', 'exists:machine_groups,machine_group_id'],
            'machine_groups.*.machine_count' => ['nullable', 'integer', 'min:1'],
            'machine_groups.*.default_target' => ['nullable', 'integer', 'min:0'],
            'target_date' => ['nullable', 'date'],
            'machine_groups.*.target_value' => ['nullable', 'integer', 'min:0'],
        ]);

        $production->update($data);

        // Handle attached machine groups if provided
        $groups = $request->input('machine_groups'); // expected as array of objects
        if (is_array($groups)) {
            // Build a list of incoming machine_group_ids
            $incomingIds = [];
            foreach ($groups as $g) {
                $mgId = isset($g['machine_group_id']) ? (int) $g['machine_group_id'] : null;
                if (! $mgId) {
                    continue;
                }

                $incomingIds[] = $mgId;

                $machineCount = isset($g['machine_count']) ? (int) $g['machine_count'] : 1;
                // prefer per-date target_value if provided; keep it also as pmg.default_target for convenience
                $defaultTarget = isset($g['target_value']) && $g['target_value'] !== '' ? (int) $g['target_value'] : (isset($g['default_target']) && $g['default_target'] !== '' ? (int) $g['default_target'] : null);

                $existing = ProductionMachineGroup::where('production_id', $production->production_id)
                    ->where('machine_group_id', $mgId)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'machine_count' => $machineCount,
                        'default_target' => $defaultTarget,
                    ]);
                } else {
                    ProductionMachineGroup::create([
                        'production_id' => $production->production_id,
                        'machine_group_id' => $mgId,
                        'name' => null,
                        'machine_count' => $machineCount,
                        'default_target' => $defaultTarget,
                    ]);
                }
            }

            // Remove any production_machine_groups not in incoming list
            ProductionMachineGroup::where('production_id', $production->production_id)
                ->whereNotIn('machine_group_id', $incomingIds)
                ->delete();
        }

        // If a target_date and per-group target values are provided, create daily targets and link them
        $targetDate = $request->input('target_date');
        if ($targetDate && is_array($groups)) {
            foreach ($groups as $g) {
                $mgId = isset($g['machine_group_id']) ? (int) $g['machine_group_id'] : null;
                $targetValue = isset($g['target_value']) && $g['target_value'] !== '' ? (int) $g['target_value'] : null;
                if (! $mgId || $targetValue === null) {
                    continue;
                }

                // find the production_machine_group record
                $pmg = ProductionMachineGroup::where('production_id', $production->production_id)
                    ->where('machine_group_id', $mgId)
                    ->first();

                if (! $pmg) {
                    continue;
                }

                // remove existing pmgt entries for this pmg that point to the same date
                $existing = ProductionMachineGroupTarget::where('production_machine_group_id', $pmg->production_machine_group_id)
                    ->whereHas('dailyTarget', function ($q) use ($targetDate) {
                        $q->whereDate('date', $targetDate);
                    })->get();

                foreach ($existing as $e) {
                    $e->delete();
                }

                // create a daily target for this pmg/date
                $daily = DailyTarget::create([
                    'date' => $targetDate,
                    'target_value' => $targetValue,
                    'actual_value' => 0,
                ]);

                ProductionMachineGroupTarget::create([
                    'production_machine_group_id' => $pmg->production_machine_group_id,
                    'daily_target_id' => $daily->daily_target_id,
                ]);
            }
        }

        return redirect()->route('data-management.production')->with('success', 'Production updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Production $production)
    {
        $production->delete();

        return redirect()->route('data-management.production')->with('success', 'Production deleted.');
    }
}
