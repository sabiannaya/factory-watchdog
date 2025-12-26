<?php

namespace App\Http\Controllers;

use App\Exports\HourlyInputExport;
use App\Models\DailyTargetValue;
use App\Models\HourlyLog;
use App\Models\Production;
use App\Models\ProductionMachineGroup;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class HourlyInputController extends Controller
{
    /**
     * Display a listing of hourly inputs.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $perPage = (int) $request->input('per_page', 20);
        $q = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'recorded_at');
        $direction = strtolower($request->input('direction', 'desc')) === 'desc' ? 'desc' : 'asc';
        $productionId = $request->input('production_id');

        // Normalize incoming date formats. Accept yyyy-mm-dd or dd/mm/yyyy from client.
        $rawDate = $request->input('date');

        // If the incoming request did not include a date parameter, redirect
        // to the same route but include the normalized date so the URL reflects
        // the active filter on initial load.
        if (! $request->has('date') || empty($rawDate) || $rawDate === 'undefined' || $rawDate === 'null') {
            $qs = $request->query();
            $qs['date'] = now('Asia/Jakarta')->toDateString();
            if ($request->filled('production_id')) {
                $qs['production_id'] = $request->input('production_id');
            }

            return redirect()->route('input.index', $qs);
        }

        if ($rawDate) {
            // If client sent dd/mm/yyyy convert to yyyy-mm-dd
            if (str_contains($rawDate, '/')) {
                try {
                    $parts = explode('/', $rawDate);
                    if (count($parts) === 3) {
                        // assume dd/mm/yyyy
                        $date = Carbon::createFromFormat('d/m/Y', $rawDate, 'Asia/Jakarta')->toDateString();
                    } else {
                        $date = Carbon::parse($rawDate, 'Asia/Jakarta')->toDateString();
                    }
                } catch (\Exception $e) {
                    // fallback to parse
                    $date = Carbon::parse($rawDate, 'Asia/Jakarta')->toDateString();
                }
            } else {
                try {
                    $date = Carbon::parse($rawDate, 'Asia/Jakarta')->toDateString();
                } catch (\Exception $e) {
                    $date = now('Asia/Jakarta')->toDateString();
                }
            }
        } else {
            $date = now('Asia/Jakarta')->toDateString();
        }

        // Build date range for the requested date in Asia/Jakarta
        // Laravel will automatically handle UTC conversion for DB queries
        $startOfDay = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Jakarta')->startOfDay();
        $endOfDay = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Jakarta')->endOfDay();

        // Always order by recorded_at descending to keep the listing predictable
        $query = HourlyLog::query()
            ->with('productionMachineGroup.production', 'productionMachineGroup.machineGroup')
            ->whereBetween('recorded_at', [$startOfDay, $endOfDay])
            ->orderBy('recorded_at', 'desc')
            ->orderBy('hourly_log_id', 'desc');

        // Staff users can only see inputs for their assigned productions
        if ($user->isStaff()) {
            $accessibleProductionIds = $user->accessibleProductionIds();
            $query->whereHas('productionMachineGroup', function ($pmq) use ($accessibleProductionIds) {
                $pmq->whereIn('production_id', $accessibleProductionIds);
            });
        }

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
            // Verify staff user has access to this production
            if ($user->isStaff() && ! $user->canAccessProduction($productionId)) {
                abort(403, 'You do not have access to this production.');
            }

            $query->whereHas('productionMachineGroup', function ($pmq) use ($productionId) {
                $pmq->where('production_id', $productionId);
            });
        }

        // Use offset pagination (traditional page-based) to simplify paging behavior.
        $paginator = $query->paginate($perPage);

        $data = collect($paginator->items())->map(function ($log) {
            $pmg = $log->productionMachineGroup;
            $date = $log->recorded_at->toDateString();

            return [
                'hourly_log_id' => $log->hourly_log_id,
                'production_name' => $log->productionMachineGroup->production->production_name ?? '-',
                'machine_group' => $log->productionMachineGroup->machineGroup->name ?? '-',
                'recorded_at' => $log->recorded_at->format('Y-m-d H:00'),
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

        // Pagination meta (page-based)
        $pagination = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
        ];

        // Staff sees only their assigned productions, Super sees all active
        $productionsQuery = Production::where('status', 'active')->orderBy('production_name');
        if ($user->isStaff()) {
            $productionsQuery->whereIn('production_id', $user->accessibleProductionIds());
        }
        $productions = $productionsQuery->get(['production_id', 'production_name']);

        return Inertia::render('input/Index', [
            'hourlyInputs' => [
                'data' => $data,
                'pagination' => $pagination,
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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Normalize incoming date; guard against client sending the literal string 'undefined' or 'null'
        $rawDate = $request->input('date');
        if ($rawDate && $rawDate !== 'undefined' && $rawDate !== 'null' && trim($rawDate) !== '') {
            if (str_contains($rawDate, '/')) {
                try {
                    $parts = explode('/', $rawDate);
                    if (count($parts) === 3) {
                        $date = Carbon::createFromFormat('d/m/Y', $rawDate, 'Asia/Jakarta')->toDateString();
                    } else {
                        $date = Carbon::parse($rawDate, 'Asia/Jakarta')->toDateString();
                    }
                } catch (\Exception $e) {
                    $date = now('Asia/Jakarta')->toDateString();
                }
            } else {
                try {
                    $date = Carbon::parse($rawDate, 'Asia/Jakarta')->toDateString();
                } catch (\Exception $e) {
                    $date = now('Asia/Jakarta')->toDateString();
                }
            }
        } else {
            $date = now('Asia/Jakarta')->toDateString();
        }

        $productionId = $request->input('production_id');
        // Handle potential 'undefined' or 'null' strings for production_id
        if ($productionId === 'undefined' || $productionId === 'null' || trim((string) $productionId) === '') {
            $productionId = null;
        }

        $hour = $request->input('hour', now('Asia/Jakarta')->format('H'));

        $productionsQuery = Production::where('status', 'active')
            ->with(['productionMachineGroups.machineGroup'])
            ->orderBy('production_name');

        // Staff users can only create inputs for their assigned productions
        if ($user->isStaff()) {
            $productionsQuery->whereIn('production_id', $user->accessibleProductionIds());
        }

        $productions = $productionsQuery->get()
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
        /** @var \App\Models\User $user */
        $user = Auth::user();

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

        // Verify staff user has access to this production
        if ($user->isStaff() && ! $user->canAccessProduction($pmg->production_id)) {
            abort(403, 'You do not have access to this production.');
        }

        // Create recorded_at timestamp in Asia/Jakarta (Laravel will store as UTC)
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

        try {
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
        } catch (QueryException $e) {
            // Handle duplicate entry (unique constraint violation)
            // SQLSTATE[23000] is common for integrity constraint violation, code 1062 for MySQL duplicate
            $sqlState = $e->getCode();
            $message = $e->getMessage();
            if ($sqlState === '23000' || str_contains($message, '1062')) {
                return back()->withErrors([
                    'duplicate' => "An entry already exists for this machine group at {$recordedAt->format('Y-m-d H:00')}. Please edit the existing entry instead.",
                ])->withInput();
            }

            // Log unexpected DB errors and rethrow
            Log::error('Failed to create HourlyLog', ['error' => $message, 'exception' => $e]);
            throw $e;
        }

        return redirect()
            ->route('input.index', ['date' => $validated['date'], 'production_id' => $pmg->production_id])
            ->with('success', 'Hourly input recorded successfully.');
    }

    /**
     * Display the specified hourly input.
     */
    public function show(HourlyLog $hourlyLog)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $hourlyLog->load('productionMachineGroup.production', 'productionMachineGroup.machineGroup', 'creator', 'modifier');

        // Verify staff user has access to this production
        if ($user->isStaff() && ! $user->canAccessProduction($hourlyLog->productionMachineGroup->production_id)) {
            abort(403, 'You do not have access to this production.');
        }

        return Inertia::render('input/Show', [
            'hourlyInput' => [
                'hourly_log_id' => $hourlyLog->hourly_log_id,
                'production_name' => $hourlyLog->productionMachineGroup->production->production_name ?? '-',
                'machine_group' => $hourlyLog->productionMachineGroup->machineGroup->name ?? '-',
                'recorded_at' => $hourlyLog->recorded_at->format('Y-m-d H:00'),
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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $hourlyLog->load('productionMachineGroup.production', 'productionMachineGroup.machineGroup');

        // Verify staff user has access to this production
        if ($user->isStaff() && ! $user->canAccessProduction($hourlyLog->productionMachineGroup->production_id)) {
            abort(403, 'You do not have access to this production.');
        }

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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Verify staff user has access to this production
        if ($user->isStaff() && ! $user->canAccessProduction($hourlyLog->productionMachineGroup->production_id)) {
            abort(403, 'You do not have access to this production.');
        }

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

        // Update recorded_at timestamp in Asia/Jakarta (Laravel handles UTC conversion)
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
    public function destroy(Request $request, HourlyLog $hourlyLog)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Only Super users can delete
        if (! $user->canDelete()) {
            abort(403, 'You do not have permission to delete hourly inputs.');
        }

        $hourlyLog->delete();

        // Preserve all query parameters from the request
        $params = [];
        if ($request->has('date')) {
            $params['date'] = $request->input('date');
        }
        if ($request->has('production_id')) {
            $params['production_id'] = $request->input('production_id');
        }
        if ($request->has('q')) {
            $params['q'] = $request->input('q');
        }
        if ($request->has('page')) {
            $params['page'] = $request->input('page');
        }
        if ($request->has('per_page')) {
            $params['per_page'] = $request->input('per_page');
        }

        return redirect()
            ->route('input.index', $params)
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
                'recorded_at' => $existingEntry->recorded_at->format('Y-m-d H:00'),
            ] : null,
        ]);
    }

    /**
     * Export hourly inputs to Excel
     */
    public function export(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $q = trim((string) $request->input('q', ''));
        $productionId = $request->input('production_id');
        $rawDate = $request->input('date', now('Asia/Jakarta')->toDateString());

        // Normalize incoming date
        try {
            if (str_contains($rawDate, '/')) {
                $date = Carbon::createFromFormat('d/m/Y', $rawDate, 'Asia/Jakarta')->toDateString();
            } else {
                $date = Carbon::parse($rawDate, 'Asia/Jakarta')->toDateString();
            }
        } catch (\Exception $e) {
            $date = now('Asia/Jakarta')->toDateString();
        }

        // Build date range (Laravel handles timezone conversion)
        $startOfDay = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Jakarta')->startOfDay();
        $endOfDay = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Jakarta')->endOfDay();

        $query = HourlyLog::query()
            ->with('productionMachineGroup.production', 'productionMachineGroup.machineGroup')
            ->whereBetween('recorded_at', [$startOfDay, $endOfDay])
            ->orderBy('recorded_at', 'desc');

        // Staff users can only export inputs for their assigned productions
        if ($user->isStaff()) {
            $accessibleProductionIds = $user->accessibleProductionIds();
            $query->whereHas('productionMachineGroup', function ($pmq) use ($accessibleProductionIds) {
                $pmq->whereIn('production_id', $accessibleProductionIds);
            });
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
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
            // Verify staff user has access to this production
            if ($user->isStaff() && ! $user->canAccessProduction($productionId)) {
                abort(403, 'You do not have access to this production.');
            }

            $query->whereHas('productionMachineGroup', function ($pmq) use ($productionId) {
                $pmq->where('production_id', $productionId);
            });
        }

        $data = collect($query->get())->map(function ($log) {
            return [
                'hour' => $log->recorded_at->format('H:00'),
                'production_name' => $log->productionMachineGroup->production->production_name ?? '-',
                'machine_group' => $log->productionMachineGroup->machineGroup->name ?? '-',
                'output_qty_normal' => $log->output_qty_normal,
                'output_qty_reject' => $log->output_qty_reject,
                'target_qty_normal' => $log->target_qty_normal,
                'target_qty_reject' => $log->target_qty_reject,
                'total_output' => $log->total_output,
                'total_target' => $log->total_target,
            ];
        });

        $filename = 'hourly-input-'.$date.'.xlsx';

        return Excel::download(new HourlyInputExport($data, 'Hourly Input '.$date), $filename);
    }

    /**
     * Bulk delete hourly logs.
     */
    public function bulkDelete(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:hourly_logs,hourly_log_id'],
        ]);

        $logs = HourlyLog::whereIn('hourly_log_id', $validated['ids'])->get();

        // Verify user has access to all logs
        foreach ($logs as $log) {
            $productionId = $log->productionMachineGroup->production_id;
            if ($user->isStaff() && ! $user->canAccessProduction($productionId)) {
                abort(403, 'You do not have access to delete one or more of these records.');
            }
        }

        HourlyLog::whereIn('hourly_log_id', $validated['ids'])->delete();

        // Preserve all query parameters from the request
        $params = [];
        if ($request->has('date')) {
            $params['date'] = $request->input('date');
        }
        if ($request->has('production_id')) {
            $params['production_id'] = $request->input('production_id');
        }
        if ($request->has('q')) {
            $params['q'] = $request->input('q');
        }
        if ($request->has('page')) {
            $params['page'] = $request->input('page');
        }
        if ($request->has('per_page')) {
            $params['per_page'] = $request->input('per_page');
        }

        return redirect()
            ->route('input.index', $params)
            ->with('success', count($validated['ids']).' hourly inputs deleted successfully.');
    }
}
