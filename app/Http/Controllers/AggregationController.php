<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AggregationController extends Controller
{
    /**
     * Convert a string to sentence case (first letter uppercase, rest lowercase).
     */
    protected function sentenceCase(?string $value): ?string
    {
        if ($value === null) return null;
        $lower = mb_strtolower($value);
        return mb_strtoupper(mb_substr($lower, 0, 1)) . mb_substr($lower, 1);
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
                DB::raw('COALESCE(SUM(hl.output_value), 0) as total_output'),
                DB::raw('COALESCE(SUM(hl.target_value), 0) as total_target'),
                DB::raw('(COALESCE(SUM(hl.output_value), 0) - COALESCE(SUM(hl.target_value), 0)) as variance'),
            ])
            ->leftJoin('productions as p', 'pmg.production_id', '=', 'p.production_id')
            ->leftJoin('machine_groups as mg', 'pmg.machine_group_id', '=', 'mg.machine_group_id')
            ->leftJoin('hourly_logs as hl', function ($join) use ($dateFrom, $dateTo) {
                $join->on('pmg.production_machine_group_id', '=', 'hl.production_machine_group_id');
                if ($dateFrom) $join->where('hl.recorded_at', '>=', $dateFrom);
                if ($dateTo) $join->where('hl.recorded_at', '<=', $dateTo);
            })
            ->groupBy(['pmg.production_machine_group_id', 'p.production_id', 'p.production_name', 'mg.machine_group_id', 'mg.name', 'pmg.machine_count']);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('p.production_name', 'like', "%{$q}%")
                    ->orWhere('mg.name', 'like', "%{$q}%");
            });
        }

        // Whitelist sort columns (allow sorting by the computed variance)
        $allowed = ['total_output', 'total_target', 'variance', 'machine_count', 'production_name', 'machine_group_name'];
        if (! in_array($sort, $allowed, true)) $sort = 'total_output';

        // apply sort (handle computed columns separately)
        if (in_array($sort, ['total_output','total_target','machine_count'])) {
            $query->orderBy(DB::raw($sort), $direction);
        } elseif ($sort === 'variance') {
            $query->orderBy(DB::raw('(COALESCE(SUM(hl.output_value), 0) - COALESCE(SUM(hl.target_value), 0))'), $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $p = $query->paginate($perPage);

        $data = collect($p->items())->map(function ($r) {
            return [
                'production_machine_group_id' => $r->production_machine_group_id,
                'production_id' => $r->production_id,
                'production_name' => $this->sentenceCase($r->production_name),
                'machine_group_id' => $r->machine_group_id,
                'machine_group_name' => $this->sentenceCase($r->machine_group_name),
                'machine_count' => (int) $r->machine_count,
                'total_output' => (float) $r->total_output,
                'total_target' => (float) $r->total_target,
                'variance' => (float) ($r->total_output - $r->total_target),
            ];
        })->all();

        return Inertia::render('data-management/MachineGroupAggregate', [
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
                DB::raw('COALESCE(SUM(hl.output_value), 0) as total_output'),
                DB::raw('COALESCE(SUM(hl.target_value), 0) as total_target'),
                DB::raw('(COALESCE(SUM(hl.output_value), 0) - COALESCE(SUM(hl.target_value), 0)) as variance'),
                DB::raw('COUNT(DISTINCT pmg.production_machine_group_id) as group_count'),
            ])
            ->leftJoin('production_machine_groups as pmg', 'p.production_id', '=', 'pmg.production_id')
            ->leftJoin('hourly_logs as hl', function ($join) use ($dateFrom, $dateTo) {
                $join->on('pmg.production_machine_group_id', '=', 'hl.production_machine_group_id');
                if ($dateFrom) $join->where('hl.recorded_at', '>=', $dateFrom);
                if ($dateTo) $join->where('hl.recorded_at', '<=', $dateTo);
            })
            ->groupBy(['p.production_id', 'p.production_name']);

        if ($q !== '') {
            $query->where('p.production_name', 'like', "%{$q}%");
        }

        $allowed = ['total_output', 'total_target', 'variance', 'group_count', 'production_name'];
        if (! in_array($sort, $allowed, true)) $sort = 'total_output';

        if (in_array($sort, ['total_output','total_target','group_count'])) {
            $query->orderBy(DB::raw($sort), $direction);
        } elseif ($sort === 'variance') {
            $query->orderBy(DB::raw('(COALESCE(SUM(hl.output_value), 0) - COALESCE(SUM(hl.target_value), 0))'), $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $p = $query->paginate($perPage);

        $data = collect($p->items())->map(function ($r) {
            return [
                'production_id' => $r->production_id,
                'production_name' => $this->sentenceCase($r->production_name),
                'group_count' => (int) $r->group_count,
                'total_output' => (float) $r->total_output,
                'total_target' => (float) $r->total_target,
                'variance' => (float) ($r->total_output - $r->total_target),
            ];
        })->all();

        return Inertia::render('data-management/ProductionAggregate', [
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
                DB::raw('SUM(hl.output_value) as total_output'),
                DB::raw('SUM(hl.target_value) as total_target'),
                DB::raw('(SUM(hl.output_value) - SUM(hl.target_value)) as variance'),
            ])
            ->leftJoin('production_machine_groups as pmg', 'hl.production_machine_group_id', '=', 'pmg.production_machine_group_id')
            ->leftJoin('productions as p', 'pmg.production_id', '=', 'p.production_id')
            ->leftJoin('machine_groups as mg', 'pmg.machine_group_id', '=', 'mg.machine_group_id')
            ->groupBy(['recorded_hour','pmg.production_machine_group_id','p.production_id','p.production_name','mg.machine_group_id','mg.name']);

        if ($dateFrom) $query->where('hl.recorded_at', '>=', $dateFrom);
        if ($dateTo) $query->where('hl.recorded_at', '<=', $dateTo);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('p.production_name', 'like', "%{$q}%")
                    ->orWhere('mg.name', 'like', "%{$q}%");
            });
        }

        $allowed = ['recorded_hour','total_output','total_target','variance','production_name','machine_group_name'];
        if (! in_array($sort, $allowed, true)) $sort = 'recorded_hour';

        if (in_array($sort, ['total_output','total_target'])) {
            $query->orderBy(DB::raw($sort), $direction);
        } elseif ($sort === 'variance') {
            $query->orderBy(DB::raw('(SUM(hl.output_value) - SUM(hl.target_value))'), $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $p = $query->paginate($perPage);

        $data = collect($p->items())->map(function ($r) {
            return [
                'recorded_hour' => $r->recorded_hour,
                'production_machine_group_id' => $r->production_machine_group_id,
                'production_id' => $r->production_id,
                'production_name' => $this->sentenceCase($r->production_name),
                'machine_group_id' => $r->machine_group_id,
                'machine_group_name' => $this->sentenceCase($r->machine_group_name),
                'total_output' => (float) $r->total_output,
                'total_target' => (float) $r->total_target,
                'variance' => (float) ($r->total_output - $r->total_target),
            ];
        })->all();

        return Inertia::render('data-management/GroupLogs', [
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
                DB::raw('SUM(hl.output_value) as total_output'),
                DB::raw('SUM(hl.target_value) as total_target'),
                DB::raw('(SUM(hl.output_value) - SUM(hl.target_value)) as variance'),
            ])
            ->leftJoin('production_machine_groups as pmg', 'hl.production_machine_group_id', '=', 'pmg.production_machine_group_id')
            ->leftJoin('productions as p', 'pmg.production_id', '=', 'p.production_id')
            ->groupBy(['recorded_hour','p.production_id','p.production_name']);

        if ($dateFrom) $query->where('hl.recorded_at', '>=', $dateFrom);
        if ($dateTo) $query->where('hl.recorded_at', '<=', $dateTo);

        if ($q !== '') {
            $query->where('p.production_name', 'like', "%{$q}%");
        }

        $allowed = ['recorded_hour','total_output','total_target','variance','production_name'];
        if (! in_array($sort, $allowed, true)) $sort = 'recorded_hour';

        if (in_array($sort, ['total_output','total_target'])) {
            $query->orderBy(DB::raw($sort), $direction);
        } elseif ($sort === 'variance') {
            $query->orderBy(DB::raw('(SUM(hl.output_value) - SUM(hl.target_value))'), $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $p = $query->paginate($perPage);

        $data = collect($p->items())->map(function ($r) {
            return [
                'recorded_hour' => $r->recorded_hour,
                'production_id' => $r->production_id,
                'production_name' => $r->production_name,
                'total_output' => (float) $r->total_output,
                'total_target' => (float) $r->total_target,
                'variance' => (float) ($r->total_output - $r->total_target),
            ];
        })->all();

        return Inertia::render('data-management/ProductionLogs', [
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
}
