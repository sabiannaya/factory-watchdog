<?php

namespace App\Http\Controllers;

use App\Models\DailyTargetValue;
use App\Models\HourlyLog;
use App\Models\Production;
use App\Models\ProductionMachineGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HourlyInputController extends Controller
{
    /**
     * Display a listing of hourly inputs.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $q = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'recorded_at');
        $direction = strtolower($request->input('direction', 'desc')) === 'desc' ? 'desc' : 'asc';
        $cursor = $request->input('cursor');
        $productionId = $request->input('production_id');
        $date = $request->input('date', now('Asia/Jakarta')->toDateString());

        $allowed = ['recorded_at', 'output_value'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'recorded_at';
        }

        $query = HourlyLog::query()
            ->with('productionMachineGroup.production', 'productionMachineGroup.machineGroup')
            ->whereDate('recorded_at', $date)
            ->orderBy($sort, $direction)
            ->orderBy('hourly_log_id', 'asc');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                // No machine-level logging; search only by production and machine group names
                $sub
                    ->orWhereHas('productionMachineGroup.production', function ($pq) use ($q) {
                        $pq->where('production_name', 'like', "%{$q}%");
                    })
                    ->orWhereHas('productionMachineGroup.machineGroup', function ($mq) use ($q) {
                        $mq->where('name', 'like', "%{$q}%");
                    });
            });
        }

        if ($productionId) {
            $query->whereHas('productionMachineGroup', function ($pmq) use ($productionId) {
                $pmq->where('production_id', $productionId);
            });
        }

        $paginator = $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

        $data = collect($paginator->items())->map(function ($log) {
            $pmg = $log->productionMachineGroup;
            $date = $log->recorded_at->toDateString();

            return [
                'hourly_log_id' => $log->hourly_log_id,
                'production_name' => $log->productionMachineGroup->production->production_name ?? '-',
                'machine_group' => $log->productionMachineGroup->machineGroup->name ?? '-',
                'recorded_at' => $log->recorded_at->format('Y-m-d H:i'),
                'hour' => $log->recorded_at->format('H:00'),
                'output_qty_normal' => $log->output_qty_normal,
                'output_qty_reject' => $log->output_qty_reject,
                'output_grades' => $log->output_grades,
                'output_grade' => $log->output_grade,
                'output_ukuran' => $log->output_ukuran,
                'target_qty_normal' => $log->target_qty_normal,
                'target_qty_reject' => $log->target_qty_reject,
                'target_grades' => $log->target_grades,
                'target_grade' => $log->target_grade,
                'target_ukuran' => $log->target_ukuran,
                'keterangan' => $log->keterangan,
                'total_output' => $log->total_output,
                'total_target' => $log->total_target,
            ];
        })->all();

        $productions = Production::where('status', 'active')
            ->orderBy('production_name')
            ->get(['production_id', 'production_name']);

        return Inertia::render('input/Index', [
            'hourlyInputs' => [
                'data' => $data,
                'next_cursor' => $paginator->nextCursor()?->encode() ?? null,
                'prev_cursor' => $paginator->previousCursor()?->encode() ?? null,
            ],
            'productions' => $productions,
            'meta' => [
                'sort' => $sort,
                'direction' => $direction,
                'q' => $q,
                'per_page' => $perPage,
                'production_id' => $productionId,
                'date' => $date,
            ],
        ]);
    }

    /**
     * Show the form for creating a new hourly input.
     */
    public function create(Request $request)
    {
        $date = $request->input('date', now('Asia/Jakarta')->toDateString());
        $productionId = $request->input('production_id');
        $hour = $request->input('hour', now('Asia/Jakarta')->format('H'));

        $productions = Production::where('status', 'active')
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
                            'name' => $pmg->machineGroup->name,
                            'machine_count' => $pmg->machine_count ?? 1,
                            'default_targets' => $pmg->default_targets ?? [],
                            'fields' => $pmg->machineGroup->getInputFields(),
                            'input_config' => $pmg->machineGroup->input_config,
                        ];
                    })->all(),
                ];
            });

        return Inertia::render('input/Create', [
            'productions' => $productions,
            'selectedProductionId' => $productionId ? (int) $productionId : null,
            'selectedDate' => $date,
            'selectedHour' => $hour,
        ]);
    }

    /**
     * Store a newly created hourly input.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'production_machine_group_id' => 'required|integer|exists:production_machine_groups,production_machine_group_id',
            'date' => 'required|date',
            'hour' => 'required|integer|min:0|max:23',
            'output_qty_normal' => 'nullable|integer|min:0',
            'output_qty_reject' => 'nullable|integer|min:0',
            'output_grades' => 'nullable|array',
            'output_grades.*' => 'nullable|integer|min:0',
            'output_grade' => 'nullable|string|max:50',
            'output_ukuran' => 'nullable|string|max:100',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $pmg = ProductionMachineGroup::findOrFail($validated['production_machine_group_id']);

        // Create recorded_at timestamp
        $recordedAt = Carbon::parse($validated['date'], 'Asia/Jakarta')
            ->setHour((int) $validated['hour'])
            ->setMinute(0)
            ->setSecond(0);

        // Check for duplicate entry (same machine group, same hour)
        $existingEntry = HourlyLog::where('production_machine_group_id', $validated['production_machine_group_id'])
            ->where('recorded_at', $recordedAt)
            ->first();

        if ($existingEntry) {
            return back()->withErrors([
                'duplicate' => "An entry already exists for this machine group at {$recordedAt->format('Y-m-d H:00')}. Please edit the existing entry instead.",
            ])->withInput();
        }

        // Get target values for each field
        $targetQtyNormal = $this->getTargetValue($pmg, $validated['date'], 'qty_normal');
        $targetQtyReject = $this->getTargetValue($pmg, $validated['date'], 'qty_reject');

        HourlyLog::create([
            'production_machine_group_id' => $validated['production_machine_group_id'],
            'recorded_at' => $recordedAt,
            'output_qty_normal' => $validated['output_qty_normal'] ?? null,
            'output_qty_reject' => $validated['output_qty_reject'] ?? null,
            'output_grades' => $validated['output_grades'] ?? null,
            'output_grade' => $validated['output_grade'] ?? null,
            'output_ukuran' => $validated['output_ukuran'] ?? null,
            'target_qty_normal' => $targetQtyNormal,
            'target_qty_reject' => $targetQtyReject,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return redirect()
            ->route('input.index', ['date' => $validated['date'], 'production_id' => $pmg->production_id])
            ->with('success', 'Hourly input recorded successfully.');
    }

    /**
     * Display the specified hourly input.
     */
    public function show(HourlyLog $hourlyLog)
    {
        $hourlyLog->load('productionMachineGroup.production', 'productionMachineGroup.machineGroup', 'creator', 'modifier');

        return Inertia::render('input/Show', [
            'hourlyInput' => [
                'hourly_log_id' => $hourlyLog->hourly_log_id,
                'production_name' => $hourlyLog->productionMachineGroup->production->production_name ?? '-',
                'machine_group' => $hourlyLog->productionMachineGroup->machineGroup->name ?? '-',
                'recorded_at' => $hourlyLog->recorded_at->format('Y-m-d H:i'),
                'date' => $hourlyLog->recorded_at->toDateString(),
                'hour' => $hourlyLog->recorded_at->format('H'),
                'output_qty_normal' => $hourlyLog->output_qty_normal,
                'output_qty_reject' => $hourlyLog->output_qty_reject,
                'output_grades' => $hourlyLog->output_grades,
                'output_grade' => $hourlyLog->output_grade,
                'output_ukuran' => $hourlyLog->output_ukuran,
                'target_qty_normal' => $hourlyLog->target_qty_normal,
                'target_qty_reject' => $hourlyLog->target_qty_reject,
                'target_grades' => $hourlyLog->target_grades,
                'target_grade' => $hourlyLog->target_grade,
                'target_ukuran' => $hourlyLog->target_ukuran,
                'keterangan' => $hourlyLog->keterangan,
                'total_output' => $hourlyLog->total_output,
                'total_target' => $hourlyLog->total_target,
                'created_by' => $hourlyLog->creator?->name,
                'modified_by' => $hourlyLog->modifier?->name,
                'created_at' => $hourlyLog->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $hourlyLog->updated_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified hourly input.
     */
    public function edit(HourlyLog $hourlyLog)
    {
        $hourlyLog->load('productionMachineGroup.production', 'productionMachineGroup.machineGroup');

        $inputConfig = $hourlyLog->productionMachineGroup->machineGroup->input_config;
        $fields = $hourlyLog->productionMachineGroup->machineGroup->getInputFields();

        return Inertia::render('input/Edit', [
            'hourlyInput' => [
                'hourly_log_id' => $hourlyLog->hourly_log_id,
                'production_machine_group_id' => $hourlyLog->production_machine_group_id,
                'production_name' => $hourlyLog->productionMachineGroup->production->production_name ?? '-',
                'machine_group' => $hourlyLog->productionMachineGroup->machineGroup->name ?? '-',
                'date' => $hourlyLog->recorded_at->toDateString(),
                'hour' => (int) $hourlyLog->recorded_at->format('H'),
                'output_qty_normal' => $hourlyLog->output_qty_normal,
                'output_qty_reject' => $hourlyLog->output_qty_reject,
                'output_grades' => $hourlyLog->output_grades,
                'output_grade' => $hourlyLog->output_grade,
                'output_ukuran' => $hourlyLog->output_ukuran,
                'target_qty_normal' => $hourlyLog->target_qty_normal,
                'target_qty_reject' => $hourlyLog->target_qty_reject,
                'target_grades' => $hourlyLog->target_grades,
                'target_grade' => $hourlyLog->target_grade,
                'target_ukuran' => $hourlyLog->target_ukuran,
                'keterangan' => $hourlyLog->keterangan,
            ],
            'inputConfig' => $inputConfig,
            'fields' => $fields,
        ]);
    }

    /**
     * Update the specified hourly input.
     */
    public function update(Request $request, HourlyLog $hourlyLog)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'hour' => 'required|integer|min:0|max:23',
            'output_qty_normal' => 'nullable|integer|min:0',
            'output_qty_reject' => 'nullable|integer|min:0',
            'output_grades' => 'nullable|array',
            'output_grades.*' => 'nullable|integer|min:0',
            'output_grade' => 'nullable|string|max:50',
            'output_ukuran' => 'nullable|string|max:100',
            'keterangan' => 'nullable|string|max:500',
        ]);

        // Update recorded_at timestamp
        $recordedAt = Carbon::parse($validated['date'], 'Asia/Jakarta')
            ->setHour((int) $validated['hour'])
            ->setMinute(0)
            ->setSecond(0);

        // Get updated target values
        $targetQtyNormal = $this->getTargetValue($hourlyLog->productionMachineGroup, $validated['date'], 'qty_normal');
        $targetQtyReject = $this->getTargetValue($hourlyLog->productionMachineGroup, $validated['date'], 'qty_reject');

        $hourlyLog->update([
            'recorded_at' => $recordedAt,
            'output_qty_normal' => $validated['output_qty_normal'] ?? null,
            'output_qty_reject' => $validated['output_qty_reject'] ?? null,
            'output_grades' => $validated['output_grades'] ?? null,
            'output_grade' => $validated['output_grade'] ?? null,
            'output_ukuran' => $validated['output_ukuran'] ?? null,
            'target_qty_normal' => $targetQtyNormal,
            'target_qty_reject' => $targetQtyReject,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return redirect()
            ->route('input.index', ['date' => $validated['date']])
            ->with('success', 'Hourly input updated successfully.');
    }

    /**
     * Remove the specified hourly input.
     */
    public function destroy(HourlyLog $hourlyLog)
    {
        $date = $hourlyLog->recorded_at->toDateString();
        $hourlyLog->delete();

        return redirect()
            ->route('input.index', ['date' => $date])
            ->with('success', 'Hourly input deleted successfully.');
    }

    /**
     * Get target value for a production machine group on a specific date.
     */
    protected function getTargetValue(ProductionMachineGroup $pmg, string $date, string $fieldName = 'qty'): int
    {
        // First try to get from daily target values
        $dailyTarget = DailyTargetValue::where([
            ['production_machine_group_id', '=', $pmg->production_machine_group_id],
            ['date', '=', $date],
            ['field_name', '=', $fieldName],
        ])->first();

        if ($dailyTarget && $dailyTarget->target_value !== null) {
            // Return hourly target (daily target / 8 hours shift)
            return (int) ceil($dailyTarget->target_value / 8);
        }

        // Fall back to default targets
        $defaultTargets = $pmg->default_targets ?? [];
        if (isset($defaultTargets[$fieldName])) {
            return (int) ceil($defaultTargets[$fieldName] / 8);
        }

        return 0;
    }

    /**
     * Check if a duplicate entry exists.
     */
    public function checkDuplicate(Request $request)
    {
        $validated = $request->validate([
            'production_machine_group_id' => 'required|integer',
            'date' => 'required|date',
            'hour' => 'required|integer|min:0|max:23',
        ]);

        $recordedAt = Carbon::parse($validated['date'], 'Asia/Jakarta')
            ->setHour((int) $validated['hour'])
            ->setMinute(0)
            ->setSecond(0);

        $existingEntry = HourlyLog::where('production_machine_group_id', $validated['production_machine_group_id'])
            ->where('recorded_at', $recordedAt)
            ->first();

        return response()->json([
            'exists' => (bool) $existingEntry,
            'entry' => $existingEntry ? [
                'hourly_log_id' => $existingEntry->hourly_log_id,
                'recorded_at' => $existingEntry->recorded_at->format('Y-m-d H:i'),
            ] : null,
        ]);
    }
}
