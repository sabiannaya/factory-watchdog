<?php

namespace App\Http\Controllers;

use App\Exports\GroupLogsExport;
use App\Exports\MachineGroupsExport;
use App\Exports\ProductionLogsExport;
use App\Exports\ProductionsExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AggregationController extends Controller
{
    /**
     * Convert a string to sentence case (first letter uppercase, rest lowercase).
     */
    protected function sentenceCase(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $lower = mb_strtolower($value);

        return mb_strtoupper(mb_substr($lower, 0, 1)).mb_substr($lower, 1);
    }

    /**
     * Aggregated view: outputs per production_machine_group (group-level)
     */
    public function machineGroups(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $q = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'total_output');
        $direction = strtolower($request->input('direction', 'desc')) === 'desc' ? 'desc' : 'asc';

        $query = DB::table('production_machine_groups as pmg')
            ->select([
                'pmg.production_machine_group_id',
                'p.production_id',
                'p.production_name',
                'mg.machine_group_id',
                'mg.name as machine_group_name',
                'pmg.machine_count',
                DB::raw('COALESCE(SUM(hl.output_qty_normal), 0) as total_output_qty_normal'),
                DB::raw('COALESCE(SUM(hl.output_qty_reject), 0) as total_output_qty_reject'),
                DB::raw('COALESCE(SUM(hl.output_qty_normal), 0) + COALESCE(SUM(hl.output_qty_reject), 0) as total_output'),
            ])
            ->leftJoin('productions as p', 'pmg.production_id', '=', 'p.production_id')
            ->leftJoin('machine_groups as mg', 'pmg.machine_group_id', '=', 'mg.machine_group_id')
            ->leftJoin('hourly_logs as hl', function ($join) use ($dateFrom, $dateTo) {
                $join->on('pmg.production_machine_group_id', '=', 'hl.production_machine_group_id');
                if ($dateFrom) {
                    $join->where('hl.recorded_at', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $join->where('hl.recorded_at', '<=', $dateTo);
                }
            })
            ->groupBy(['pmg.production_machine_group_id', 'p.production_id', 'p.production_name', 'mg.machine_group_id', 'mg.name', 'pmg.machine_count']);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('p.production_name', 'like', "%{$q}%")
                    ->orWhere('mg.name', 'like', "%{$q}%");
            });
        }

        // Whitelist sort columns
        $allowed = ['total_output', 'machine_count', 'production_name', 'machine_group_name'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'total_output';
        }

        // Apply sort
        if (in_array($sort, ['total_output', 'machine_count'])) {
            $query->orderBy(DB::raw($sort), $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $p = $query->paginate($perPage);

        // Prepare date range for targets
        $fromDate = $dateFrom ? Carbon::parse($dateFrom)->toDateString() : Carbon::now('Asia/Jakarta')->toDateString();
        $toDate = $dateTo ? Carbon::parse($dateTo)->toDateString() : Carbon::now('Asia/Jakarta')->toDateString();
        $days = Carbon::parse($fromDate)->diffInDays(Carbon::parse($toDate)) + 1;

        $items = $p->items();
        $pmgIds = array_map(fn ($i) => $i->production_machine_group_id, $items);

        // Fetch default_targets for these pmgs
        $defaults = DB::table('production_machine_groups')
            ->whereIn('production_machine_group_id', $pmgIds)
            ->pluck('default_targets', 'production_machine_group_id')
            ->toArray();

        $data = collect($items)->map(function ($r) use ($fromDate, $toDate, $days, $defaults) {
            $totalOutput = (int) $r->total_output_qty_normal + (int) $r->total_output_qty_reject;

            // Sum daily target values for this PMG across the date range
            $targetNormalSum = (int) DB::table('daily_target_values')
                ->where('production_machine_group_id', $r->production_machine_group_id)
                ->whereBetween('date', [$fromDate, $toDate])
                ->where('field_name', 'qty_normal')
                ->sum('target_value');

            $targetRejectSum = (int) DB::table('daily_target_values')
                ->where('production_machine_group_id', $r->production_machine_group_id)
                ->whereBetween('date', [$fromDate, $toDate])
                ->where('field_name', 'qty_reject')
                ->sum('target_value');

            // Fallback to defaults if daily overrides missing
            if ($targetNormalSum === 0) {
                $d = $defaults[$r->production_machine_group_id] ?? null;
                $arr = is_string($d) ? json_decode($d, true) : ($d ?? []);
                $perDay = $arr['qty_normal'] ?? $arr['qty'] ?? 0;
                $targetNormalSum = (int) $perDay * $days;
            }
            if ($targetRejectSum === 0) {
                $d = $defaults[$r->production_machine_group_id] ?? null;
                $arr = is_string($d) ? json_decode($d, true) : ($d ?? []);
                $perDay = $arr['qty_reject'] ?? 0;
                $targetRejectSum = (int) $perDay * $days;
            }

            $totalTarget = $targetNormalSum + $targetRejectSum;

            // Contextual variance: (Qty Normal - Target Normal) + (Target Reject - Qty Reject)
            $variance = ((int) $r->total_output_qty_normal - $targetNormalSum) + ($targetRejectSum - (int) $r->total_output_qty_reject);

            return [
                'production_machine_group_id' => $r->production_machine_group_id,
                'production_id' => $r->production_id,
                'production_name' => $this->sentenceCase($r->production_name),
                'machine_group_id' => $r->machine_group_id,
                'machine_group_name' => $this->sentenceCase($r->machine_group_name),
                'machine_count' => (int) $r->machine_count,
                'total_output_qty_normal' => (int) $r->total_output_qty_normal,
                'total_output_qty_reject' => (int) $r->total_output_qty_reject,
                'total_output' => $totalOutput,
                'total_target_qty_normal' => $targetNormalSum,
                'total_target_qty_reject' => $targetRejectSum,
                'total_target' => $totalTarget,
                'variance' => $variance,
            ];
        })->all();

        return Inertia::render('summary/MachineGroups', [
            'aggregates' => [
                'data' => $data,
                'next_page' => $p->nextPageUrl(),
                'prev_page' => $p->previousPageUrl(),
            ],
            'meta' => [
                'q' => $q,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Aggregated view: outputs per production (production-level)
     */
    public function productions(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $q = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'total_output');
        $direction = strtolower($request->input('direction', 'desc')) === 'desc' ? 'desc' : 'asc';

        $query = DB::table('productions as p')
            ->select([
                'p.production_id',
                'p.production_name',
                DB::raw('COALESCE(SUM(hl.output_qty_normal), 0) as total_output_qty_normal'),
                DB::raw('COALESCE(SUM(hl.output_qty_reject), 0) as total_output_qty_reject'),
                DB::raw('COALESCE(SUM(hl.output_qty_normal), 0) + COALESCE(SUM(hl.output_qty_reject), 0) as total_output'),
                DB::raw('COUNT(DISTINCT pmg.production_machine_group_id) as group_count'),
            ])
            ->leftJoin('production_machine_groups as pmg', 'p.production_id', '=', 'pmg.production_id')
            ->leftJoin('hourly_logs as hl', function ($join) use ($dateFrom, $dateTo) {
                $join->on('pmg.production_machine_group_id', '=', 'hl.production_machine_group_id');
                if ($dateFrom) {
                    $join->where('hl.recorded_at', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $join->where('hl.recorded_at', '<=', $dateTo);
                }
            })
            ->groupBy(['p.production_id', 'p.production_name']);

        if ($q !== '') {
            $query->where('p.production_name', 'like', "%{$q}%");
        }

        $allowed = ['total_output', 'group_count', 'production_name'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'total_output';
        }

        if (in_array($sort, ['total_output', 'group_count'])) {
            $query->orderBy(DB::raw($sort), $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $p = $query->paginate($perPage);

        // Prepare date range and day count
        $fromDate = $dateFrom ? Carbon::parse($dateFrom)->toDateString() : Carbon::now('Asia/Jakarta')->toDateString();
        $toDate = $dateTo ? Carbon::parse($dateTo)->toDateString() : Carbon::now('Asia/Jakarta')->toDateString();
        $days = Carbon::parse($fromDate)->diffInDays(Carbon::parse($toDate)) + 1;

        $items = $p->items();
        $productionIds = array_map(fn ($i) => $i->production_id, $items);

        // Fetch summed daily targets grouped by production
        if (! empty($productionIds)) {
            $targetRows = DB::table('production_machine_groups as pmg')
                ->leftJoin('daily_target_values as dtv', function ($join) use ($fromDate, $toDate) {
                    $join->on('pmg.production_machine_group_id', '=', 'dtv.production_machine_group_id')
                        ->whereBetween('dtv.date', [$fromDate, $toDate]);
                })
                ->whereIn('pmg.production_id', $productionIds)
                    ->selectRaw('pmg.production_id, SUM(CASE WHEN dtv.field_name = \'qty_normal\' THEN dtv.target_value ELSE 0 END) as total_target_qty_normal, SUM(CASE WHEN dtv.field_name = \'qty_reject\' THEN dtv.target_value ELSE 0 END) as total_target_qty_reject')
                ->groupBy('pmg.production_id')
                ->get()
                ->keyBy('production_id')
                ->toArray();
        } else {
            $targetRows = [];
        }

        // Fetch defaults per production (sum defaults across pmgs)
        $defaultsPerProduction = DB::table('production_machine_groups')
            ->whereIn('production_id', $productionIds)
            ->get(['production_id', 'default_targets'])
            ->groupBy('production_id')
            ->mapWithKeys(function ($group, $prodId) {
                $sumNormal = 0;
                $sumReject = 0;
                foreach ($group as $row) {
                    $d = is_string($row->default_targets) ? json_decode($row->default_targets, true) : ($row->default_targets ?? []);
                    $sumNormal += (int) ($d['qty_normal'] ?? $d['qty'] ?? 0);
                    $sumReject += (int) ($d['qty_reject'] ?? 0);
                }

                return [$prodId => ['per_day_normal' => $sumNormal, 'per_day_reject' => $sumReject]];
            })->toArray();

        $data = collect($items)->map(function ($r) use ($targetRows, $defaultsPerProduction, $days) {
            $totalOutput = (int) $r->total_output_qty_normal + (int) $r->total_output_qty_reject;

            $prodId = $r->production_id;
            $targetNormal = isset($targetRows[$prodId]) ? (int) $targetRows[$prodId]->total_target_qty_normal : 0;
            $targetReject = isset($targetRows[$prodId]) ? (int) $targetRows[$prodId]->total_target_qty_reject : 0;

            if ($targetNormal === 0) {
                $perDay = $defaultsPerProduction[$prodId]['per_day_normal'] ?? 0;
                $targetNormal = (int) $perDay * $days;
            }
            if ($targetReject === 0) {
                $perDay = $defaultsPerProduction[$prodId]['per_day_reject'] ?? 0;
                $targetReject = (int) $perDay * $days;
            }

            $totalTarget = $targetNormal + $targetReject;

            $variance = ((int) $r->total_output_qty_normal - $targetNormal) + ($targetReject - (int) $r->total_output_qty_reject);

            return [
                'production_id' => $r->production_id,
                'production_name' => $this->sentenceCase($r->production_name),
                'group_count' => (int) $r->group_count,
                'total_output_qty_normal' => (int) $r->total_output_qty_normal,
                'total_output_qty_reject' => (int) $r->total_output_qty_reject,
                'total_output' => $totalOutput,
                'total_target_qty_normal' => $targetNormal,
                'total_target_qty_reject' => $targetReject,
                'total_target' => $totalTarget,
                'variance' => $variance,
            ];
        })->all();

        return Inertia::render('summary/Productions', [
            'aggregates' => [
                'data' => $data,
                'next_page' => $p->nextPageUrl(),
                'prev_page' => $p->previousPageUrl(),
            ],
            'meta' => [
                'q' => $q,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Hourly group-level logs (sum per production_machine_group per hour)
     */
    public function groupLogs(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $q = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'recorded_hour');
        $direction = strtolower($request->input('direction', 'desc')) === 'desc' ? 'desc' : 'asc';

        $query = DB::table('hourly_logs as hl')
            ->select([
                DB::raw("DATE_FORMAT(hl.recorded_at, '%Y-%m-%d %H:00:00') as recorded_hour"),
                'pmg.production_machine_group_id',
                'p.production_id',
                'p.production_name',
                'mg.machine_group_id',
                'mg.name as machine_group_name',
                DB::raw('SUM(hl.output_qty_normal) as total_output_qty_normal'),
                DB::raw('SUM(hl.output_qty_reject) as total_output_qty_reject'),
                DB::raw('(SUM(hl.output_qty_normal) + SUM(hl.output_qty_reject)) as total_output'),
            ])
            ->leftJoin('production_machine_groups as pmg', 'hl.production_machine_group_id', '=', 'pmg.production_machine_group_id')
            ->leftJoin('productions as p', 'pmg.production_id', '=', 'p.production_id')
            ->leftJoin('machine_groups as mg', 'pmg.machine_group_id', '=', 'mg.machine_group_id')
            ->groupBy(['recorded_hour', 'pmg.production_machine_group_id', 'p.production_id', 'p.production_name', 'mg.machine_group_id', 'mg.name']);

        if ($dateFrom) {
            $query->where('hl.recorded_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('hl.recorded_at', '<=', $dateTo);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('p.production_name', 'like', "%{$q}%")
                    ->orWhere('mg.name', 'like', "%{$q}%");
            });
        }

        $allowed = ['recorded_hour', 'total_output', 'production_name', 'machine_group_name'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'recorded_hour';
        }

        if (in_array($sort, ['total_output'])) {
            $query->orderBy(DB::raw($sort), $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $p = $query->paginate($perPage);

        $data = collect($p->items())->map(function ($r) {
            $totalOutput = (int) $r->total_output_qty_normal + (int) $r->total_output_qty_reject;

            return [
                'recorded_hour' => $r->recorded_hour,
                'production_machine_group_id' => $r->production_machine_group_id,
                'production_id' => $r->production_id,
                'production_name' => $this->sentenceCase($r->production_name),
                'machine_group_id' => $r->machine_group_id,
                'machine_group_name' => $this->sentenceCase($r->machine_group_name),
                'total_output_qty_normal' => (int) $r->total_output_qty_normal,
                'total_output_qty_reject' => (int) $r->total_output_qty_reject,
                'total_output' => $totalOutput,
            ];
        })->all();

        return Inertia::render('logs/GroupLogs', [
            'logs' => [
                'data' => $data,
                'next_page' => $p->nextPageUrl(),
                'prev_page' => $p->previousPageUrl(),
            ],
            'meta' => [
                'q' => $q,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Hourly production-level logs (sum per production per hour)
     */
    public function productionLogs(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $q = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'recorded_hour');
        $direction = strtolower($request->input('direction', 'desc')) === 'desc' ? 'desc' : 'asc';

        $query = DB::table('hourly_logs as hl')
            ->select([
                DB::raw("DATE_FORMAT(hl.recorded_at, '%Y-%m-%d %H:00:00') as recorded_hour"),
                'p.production_id',
                'p.production_name',
                DB::raw('SUM(hl.output_qty_normal) as total_output_qty_normal'),
                DB::raw('SUM(hl.output_qty_reject) as total_output_qty_reject'),
                DB::raw('(SUM(hl.output_qty_normal) + SUM(hl.output_qty_reject)) as total_output'),
            ])
            ->leftJoin('production_machine_groups as pmg', 'hl.production_machine_group_id', '=', 'pmg.production_machine_group_id')
            ->leftJoin('productions as p', 'pmg.production_id', '=', 'p.production_id')
            ->groupBy(['recorded_hour', 'p.production_id', 'p.production_name']);

        if ($dateFrom) {
            $query->where('hl.recorded_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('hl.recorded_at', '<=', $dateTo);
        }

        if ($q !== '') {
            $query->where('p.production_name', 'like', "%{$q}%");
        }

        $allowed = ['recorded_hour', 'total_output', 'production_name'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'recorded_hour';
        }

        if (in_array($sort, ['total_output'])) {
            $query->orderBy(DB::raw($sort), $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $p = $query->paginate($perPage);

        $data = collect($p->items())->map(function ($r) {
            $totalOutput = (int) $r->total_output_qty_normal + (int) $r->total_output_qty_reject;

            return [
                'recorded_hour' => $r->recorded_hour,
                'production_id' => $r->production_id,
                'production_name' => $r->production_name,
                'total_output_qty_normal' => (int) $r->total_output_qty_normal,
                'total_output_qty_reject' => (int) $r->total_output_qty_reject,
                'total_output' => $totalOutput,
            ];
        })->all();

        return Inertia::render('logs/ProductionLogs', [
            'logs' => [
                'data' => $data,
                'next_page' => $p->nextPageUrl(),
                'prev_page' => $p->previousPageUrl(),
            ],
            'meta' => [
                'q' => $q,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Export machine groups summary to Excel
     */
    public function exportMachineGroups(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $dateFrom = $request->input('date_from', now('Asia/Jakarta')->toDateString());
        $dateTo = $request->input('date_to', now('Asia/Jakarta')->toDateString());

        $sql = "
            SELECT
                pmg.production_machine_group_id,
                pmg.production_id,
                p.production_name,
                pmg.machine_group_id,
                mg.name AS machine_group_name,
                COUNT(DISTINCT pmg.production_machine_group_id) AS machine_count,
                COALESCE(SUM(hl.output_qty_normal), 0) AS total_output_qty_normal,
                COALESCE(SUM(hl.output_qty_reject), 0) AS total_output_qty_reject,
                COALESCE(SUM(CASE WHEN dtv.field_name = 'qty_normal' THEN dtv.target_value ELSE 0 END), 0) AS total_target_qty_normal,
                COALESCE(SUM(CASE WHEN dtv.field_name = 'qty_reject' THEN dtv.target_value ELSE 0 END), 0) AS total_target_qty_reject
            FROM production_machine_groups pmg
            LEFT JOIN machine_groups mg ON mg.machine_group_id = pmg.machine_group_id
            LEFT JOIN productions p ON p.production_id = pmg.production_id
            LEFT JOIN hourly_logs hl
                ON hl.production_machine_group_id = pmg.production_machine_group_id
                AND DATE(hl.recorded_at) BETWEEN ? AND ?
            LEFT JOIN daily_target_values dtv
                ON dtv.production_machine_group_id = pmg.production_machine_group_id
                AND dtv.date BETWEEN ? AND ?
        ";

        $params = [$dateFrom, $dateTo, $dateFrom, $dateTo];

        if ($q !== '') {
            $sql .= ' WHERE (p.production_name LIKE ? OR mg.name LIKE ?)';
            $likeVal = '%'.$q.'%';
            $params[] = $likeVal;
            $params[] = $likeVal;
        }

        $sql .= ' GROUP BY pmg.production_id, p.production_name, pmg.machine_group_id, mg.name
                  ORDER BY p.production_name, mg.name';

        $rows = DB::select($sql, $params);

        // compute days
        $fromDate = $dateFrom ? Carbon::parse($dateFrom)->toDateString() : Carbon::now('Asia/Jakarta')->toDateString();
        $toDate = $dateTo ? Carbon::parse($dateTo)->toDateString() : Carbon::now('Asia/Jakarta')->toDateString();
        $days = Carbon::parse($fromDate)->diffInDays(Carbon::parse($toDate)) + 1;

        // fetch defaults map for fallback
        $pmgIds = array_map(fn ($r) => $r->machine_group_id, $rows);
        $defaults = DB::table('production_machine_groups')
            ->whereIn('production_machine_group_id', array_map(function ($r) {
                return $r->production_machine_group_id;
            }, $rows))
            ->pluck('default_targets', 'production_machine_group_id')
            ->toArray();

        $data = collect($rows)->map(function ($r) use ($days, $defaults) {
            $totalOutput = (int) $r->total_output_qty_normal + (int) $r->total_output_qty_reject;

            $targetNormal = (int) $r->total_target_qty_normal;
            $targetReject = (int) $r->total_target_qty_reject;

            if ($targetNormal === 0 || $targetReject === 0) {
                $d = $defaults[$r->production_machine_group_id] ?? null;
                $arr = is_string($d) ? json_decode($d, true) : ($d ?? []);
                if ($targetNormal === 0) {
                    $perDay = $arr['qty_normal'] ?? $arr['qty'] ?? 0;
                    $targetNormal = (int) $perDay * $days;
                }
                if ($targetReject === 0) {
                    $perDay = $arr['qty_reject'] ?? 0;
                    $targetReject = (int) $perDay * $days;
                }
            }

            $totalTarget = $targetNormal + $targetReject;
            $variance = ($totalOutput - $totalTarget);

            return [
                'production_name' => $this->sentenceCase($r->production_name),
                'machine_group_name' => $this->sentenceCase($r->machine_group_name),
                'machine_count' => (int) $r->machine_count,
                'total_output' => $totalOutput,
                'total_target' => $totalTarget,
                'total_target_qty_normal' => $targetNormal,
                'total_target_qty_reject' => $targetReject,
                'variance' => $variance,
            ];
        });

        $filename = 'machine-groups-'.$dateFrom.'-to-'.$dateTo.'.xlsx';

        return Excel::download(new MachineGroupsExport($data, 'Machine Groups Summary'), $filename);
    }

    /**
     * Export productions summary to Excel
     */
    public function exportProductions(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $dateFrom = $request->input('date_from', now('Asia/Jakarta')->toDateString());
        $dateTo = $request->input('date_to', now('Asia/Jakarta')->toDateString());

        $sql = "
            SELECT
                pmg.production_id,
                p.production_name,
                COUNT(DISTINCT pmg.machine_group_id) AS group_count,
                COALESCE(SUM(hl.output_qty_normal), 0) AS total_output_qty_normal,
                COALESCE(SUM(hl.output_qty_reject), 0) AS total_output_qty_reject,
                COALESCE(SUM(CASE WHEN dtv.field_name = 'qty_normal' THEN dtv.target_value ELSE 0 END), 0) AS total_target_qty_normal,
                COALESCE(SUM(CASE WHEN dtv.field_name = 'qty_reject' THEN dtv.target_value ELSE 0 END), 0) AS total_target_qty_reject
            FROM production_machine_groups pmg
            LEFT JOIN productions p ON p.production_id = pmg.production_id
            LEFT JOIN hourly_logs hl
                ON hl.production_machine_group_id = pmg.production_machine_group_id
                AND DATE(hl.recorded_at) BETWEEN ? AND ?
            LEFT JOIN daily_target_values dtv
                ON dtv.production_machine_group_id = pmg.production_machine_group_id
                AND dtv.date BETWEEN ? AND ?
        ";

        $params = [$dateFrom, $dateTo, $dateFrom, $dateTo];

        if ($q !== '') {
            $sql .= ' WHERE p.production_name LIKE ?';
            $params[] = '%'.$q.'%';
        }

        $sql .= ' GROUP BY pmg.production_id, p.production_name
                  ORDER BY p.production_name';

        $rows = DB::select($sql, $params);

        $fromDate = $dateFrom ? Carbon::parse($dateFrom)->toDateString() : Carbon::now('Asia/Jakarta')->toDateString();
        $toDate = $dateTo ? Carbon::parse($dateTo)->toDateString() : Carbon::now('Asia/Jakarta')->toDateString();
        $days = Carbon::parse($fromDate)->diffInDays(Carbon::parse($toDate)) + 1;

        // fetch defaults per production
        $prodDefaults = DB::table('production_machine_groups')
            ->whereIn('production_id', array_map(fn ($r) => $r->production_id, $rows))
            ->get(['production_id', 'default_targets'])
            ->groupBy('production_id')
            ->mapWithKeys(function ($group, $prodId) {
                $sumNormal = 0;
                $sumReject = 0;
                foreach ($group as $row) {
                    $d = is_string($row->default_targets) ? json_decode($row->default_targets, true) : ($row->default_targets ?? []);
                    $sumNormal += (int) ($d['qty_normal'] ?? $d['qty'] ?? 0);
                    $sumReject += (int) ($d['qty_reject'] ?? 0);
                }

                return [$prodId => ['per_day_normal' => $sumNormal, 'per_day_reject' => $sumReject]];
            })->toArray();

        $data = collect($rows)->map(function ($r) use ($days, $prodDefaults) {
            $totalOutput = (int) $r->total_output_qty_normal + (int) $r->total_output_qty_reject;

            $targetNormal = (int) $r->total_target_qty_normal;
            $targetReject = (int) $r->total_target_qty_reject;

            if ($targetNormal === 0) {
                $perDay = $prodDefaults[$r->production_id]['per_day_normal'] ?? 0;
                $targetNormal = (int) $perDay * $days;
            }
            if ($targetReject === 0) {
                $perDay = $prodDefaults[$r->production_id]['per_day_reject'] ?? 0;
                $targetReject = (int) $perDay * $days;
            }

            $totalTarget = $targetNormal + $targetReject;
            $variance = ($totalOutput - $totalTarget);

            return [
                'production_name' => $this->sentenceCase($r->production_name),
                'group_count' => (int) $r->group_count,
                'total_output' => $totalOutput,
                'total_target' => $totalTarget,
                'total_target_qty_normal' => $targetNormal,
                'total_target_qty_reject' => $targetReject,
                'variance' => $variance,
            ];
        });

        $filename = 'productions-'.$dateFrom.'-to-'.$dateTo.'.xlsx';

        return Excel::download(new ProductionsExport($data, 'Productions Summary'), $filename);
    }

    /**
     * Export group logs to Excel
     */
    public function exportGroupLogs(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $dateFrom = $request->input('date_from', now('Asia/Jakarta')->toDateString());
        $dateTo = $request->input('date_to', now('Asia/Jakarta')->toDateString());

        $sql = "
            SELECT
                DATE_FORMAT(hl.recorded_at, '%Y-%m-%d %H:00:00') AS hour_recorded,
                pmg.production_id,
                p.production_name,
                pmg.machine_group_id,
                mg.name AS machine_group_name,
                COALESCE(SUM(hl.output_qty_normal), 0) AS total_output_qty_normal,
                COALESCE(SUM(hl.output_qty_reject), 0) AS total_output_qty_reject
            FROM hourly_logs hl
            INNER JOIN production_machine_groups pmg
                ON pmg.production_machine_group_id = hl.production_machine_group_id
            LEFT JOIN productions p ON p.production_id = pmg.production_id
            LEFT JOIN machine_groups mg ON mg.machine_group_id = pmg.machine_group_id
            WHERE DATE(hl.recorded_at) BETWEEN ? AND ?
        ";

        $params = [$dateFrom, $dateTo];

        if ($q !== '') {
            $sql .= ' AND (p.production_name LIKE ? OR mg.name LIKE ?)';
            $likeVal = '%'.$q.'%';
            $params[] = $likeVal;
            $params[] = $likeVal;
        }

        $sql .= ' GROUP BY hour_recorded, pmg.production_id, p.production_name,
                          pmg.machine_group_id, mg.name
                  ORDER BY hour_recorded DESC';

        $rows = DB::select($sql, $params);

        $data = collect($rows)->map(function ($r) {
            $totalOutput = (int) $r->total_output_qty_normal + (int) $r->total_output_qty_reject;

            return [
                'hour' => \Carbon\Carbon::parse($r->hour_recorded)->format('Y-m-d H:00'),
                'production_name' => $this->sentenceCase($r->production_name),
                'machine_group_name' => $this->sentenceCase($r->machine_group_name),
                'total_output' => $totalOutput,
            ];
        });

        $filename = 'group-logs-'.$dateFrom.'-to-'.$dateTo.'.xlsx';

        return Excel::download(new GroupLogsExport($data, 'Group Logs by Hour'), $filename);
    }

    /**
     * Export production logs to Excel
     */
    public function exportProductionLogs(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $dateFrom = $request->input('date_from', now('Asia/Jakarta')->toDateString());
        $dateTo = $request->input('date_to', now('Asia/Jakarta')->toDateString());

        $sql = "
            SELECT
                DATE_FORMAT(hl.recorded_at, '%Y-%m-%d %H:00:00') AS hour_recorded,
                pmg.production_id,
                p.production_name,
                COALESCE(SUM(hl.output_qty_normal), 0) AS total_output_qty_normal,
                COALESCE(SUM(hl.output_qty_reject), 0) AS total_output_qty_reject
            FROM hourly_logs hl
            INNER JOIN production_machine_groups pmg
                ON pmg.production_machine_group_id = hl.production_machine_group_id
            LEFT JOIN productions p ON p.production_id = pmg.production_id
            WHERE DATE(hl.recorded_at) BETWEEN ? AND ?
        ";

        $params = [$dateFrom, $dateTo];

        if ($q !== '') {
            $sql .= ' AND p.production_name LIKE ?';
            $params[] = '%'.$q.'%';
        }

        $sql .= ' GROUP BY hour_recorded, pmg.production_id, p.production_name
                  ORDER BY hour_recorded DESC';

        $rows = DB::select($sql, $params);

        $data = collect($rows)->map(function ($r) {
            return [
                'hour' => \Carbon\Carbon::parse($r->hour_recorded)->format('Y-m-d H:00'),
                'production_name' => $this->sentenceCase($r->production_name),
                'total_output' => (int) $r->total_output_qty_normal + (int) $r->total_output_qty_reject,
            ];
        });

        $filename = 'production-logs-'.$dateFrom.'-to-'.$dateTo.'.xlsx';

        return Excel::download(new ProductionLogsExport($data, 'Production Logs by Hour'), $filename);
    }
}
