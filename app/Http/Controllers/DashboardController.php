<?php

namespace App\Http\Controllers;

use App\Models\HourlyLog;
use App\Models\MachineGroup;
use App\Models\Production;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isSuper()) {
            return redirect('/input');
        }
        $isStaff = $user->isStaff();
        $accessibleProductionIds = $isStaff ? $user->accessibleProductionIds() : [];

        // Quick stats (filtered for staff users)
        if ($isStaff) {
            $totalProductions = count($accessibleProductionIds);
            $activeProductions = Production::where('status', 'active')
                ->whereIn('production_id', $accessibleProductionIds)
                ->count();
            // Machine groups accessible to staff via their productions
            $totalMachineGroups = DB::table('production_machine_groups')
                ->whereIn('production_id', $accessibleProductionIds)
                ->distinct('machine_group_id')
                ->count('machine_group_id');
        } else {
            $totalProductions = Production::count();
            $activeProductions = Production::where('status', 'active')->count();
            $totalMachineGroups = MachineGroup::count();
        }

        // Last 7 days trend (anchor in Asia/Jakarta) — use only qty_normal
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = Carbon::now('Asia/Jakarta')->subDays($i)->toDateString();
        }

        $since7 = Carbon::now('Asia/Jakarta')->subDays(7)->setTimezone('UTC');
        $dailyRows = DB::table('hourly_logs as hl')
            ->where('hl.recorded_at', '>=', $since7)
            ->selectRaw("DATE(CONVERT_TZ(hl.recorded_at, '+00:00', '+07:00')) as date, COALESCE(SUM(hl.output_qty_normal), 0) as actual")
            ->groupBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();

        $dailyTrends = array_map(function ($d) use ($dailyRows) {
            $row = $dailyRows[$d] ?? null;

            return [
                'date' => Carbon::createFromFormat('Y-m-d', $d, 'Asia/Jakarta')->format('m/d'),
                'actual' => (int) ($row->actual ?? 0),
            ];
        }, $dates);

        // Today's performance (use qty_normal aggregated)
        $todayStart = Carbon::now('Asia/Jakarta')->startOfDay()->setTimezone('UTC');
        $todayRow = DB::table('hourly_logs as hl')
            ->selectRaw('COALESCE(SUM(hl.output_qty_normal), 0) as actual')
            ->where('hl.recorded_at', '>=', $todayStart)
            ->first();

        $todayActual = $todayRow->actual ?? 0;

        // Yesterday's performance (use qty_normal aggregated)
        $yesterdayStart = Carbon::now('Asia/Jakarta')->startOfDay()->subDay()->setTimezone('UTC');
        $yesterdayEnd = Carbon::now('Asia/Jakarta')->startOfDay()->setTimezone('UTC');
        $yesterdayRow = DB::table('hourly_logs as hl')
            ->selectRaw('COALESCE(SUM(hl.output_qty_normal), 0) as actual')
            ->where('hl.recorded_at', '>=', $yesterdayStart)
            ->where('hl.recorded_at', '<', $yesterdayEnd)
            ->first();

        $yesterdayActual = $yesterdayRow->actual ?? 0;

        // Recent hourly logs (last 10) - filtered for staff
        $recentLogsQuery = HourlyLog::with('productionMachineGroup.production', 'productionMachineGroup.machineGroup')
            ->orderBy('recorded_at', 'desc')
            ->limit(10);

        if ($isStaff) {
            $recentLogsQuery->whereHas('productionMachineGroup', function ($pmq) use ($accessibleProductionIds) {
                $pmq->whereIn('production_id', $accessibleProductionIds);
            });
        }

        $recentLogs = $recentLogsQuery->get()
            ->map(fn ($log) => [
                'production' => $log->productionMachineGroup->production->production_name ?? '-',
                'machine_group' => $log->productionMachineGroup->machineGroup->name ?? '-',
                'recorded_at' => $log->recorded_at->format('Y-m-d H:00'),
                'output_normal' => (int) ($log->output_qty_normal ?? 0),
                'output_reject' => (int) ($log->output_qty_reject ?? 0),
            ])
            ->toArray();

        // Group output distribution for last 24 hours (anchor in Asia/Jakarta -> convert to UTC)
        $since24 = Carbon::now('Asia/Jakarta')->subDay()->setTimezone('UTC');

        $groupBase = DB::table('hourly_logs as hl')
            ->leftJoin('production_machine_groups as pmg', 'hl.production_machine_group_id', '=', 'pmg.production_machine_group_id')
            ->leftJoin('machine_groups as mg', 'pmg.machine_group_id', '=', 'mg.machine_group_id')
            ->where('hl.recorded_at', '>=', $since24);

        if ($isStaff) {
            $groupBase->whereIn('pmg.production_id', $accessibleProductionIds);
        }

        $groupDistributionNormal = (clone $groupBase)
            ->groupBy('mg.machine_group_id', 'mg.name')
            ->selectRaw('mg.name as machine_group_name, COALESCE(SUM(hl.output_qty_normal), 0) as total_output')
            ->orderByDesc('total_output')
            ->get()
            ->map(fn ($r) => ['machine_group' => $r->machine_group_name, 'total_output' => (float) $r->total_output])
            ->toArray();

        $groupDistributionReject = (clone $groupBase)
            ->groupBy('mg.machine_group_id', 'mg.name')
            ->selectRaw('mg.name as machine_group_name, COALESCE(SUM(hl.output_qty_reject), 0) as total_output')
            ->orderByDesc('total_output')
            ->get()
            ->map(fn ($r) => ['machine_group' => $r->machine_group_name, 'total_output' => (float) $r->total_output])
            ->toArray();

        // Production totals for last 7 days (anchor in Asia/Jakarta -> convert to UTC)
        $since7 = Carbon::now('Asia/Jakarta')->subDays(7)->setTimezone('UTC');
        $productionWeeklyQuery = DB::table('hourly_logs as hl')
            ->leftJoin('production_machine_groups as pmg', 'hl.production_machine_group_id', '=', 'pmg.production_machine_group_id')
            ->leftJoin('productions as p', 'pmg.production_id', '=', 'p.production_id')
            ->where('hl.recorded_at', '>=', $since7);

        if ($isStaff) {
            $productionWeeklyQuery->whereIn('pmg.production_id', $accessibleProductionIds);
        }

        $productionWeekly = $productionWeeklyQuery
            ->groupBy('p.production_id', 'p.production_name')
            ->selectRaw('p.production_name, COALESCE(SUM(hl.output_qty_normal) + SUM(hl.output_qty_reject), 0) as total_output')
            ->orderByDesc('total_output')
            ->get()
            ->map(fn ($r) => ['production' => $r->production_name, 'total_output' => (float) $r->total_output])
            ->toArray();

        Log::debug('Dashboard returning aggregates', ['groupDistributionNormal' => count($groupDistributionNormal), 'groupDistributionReject' => count($groupDistributionReject), 'productionWeekly' => count($productionWeekly)]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_productions' => $totalProductions,
                'active_productions' => $activeProductions,
                'total_machine_groups' => $totalMachineGroups,
                'today_actual' => $todayActual,
                'yesterday_actual' => $yesterdayActual,
            ],
            'dailyTrends' => $dailyTrends,
            'recentLogs' => $recentLogs,
            'groupDistributionNormal' => $groupDistributionNormal,
            'groupDistributionReject' => $groupDistributionReject,
            'productionWeekly' => $productionWeekly,
        ]);
    }

    /**
     * API endpoint to return aggregates when Inertia props are not available on client.
     */
    public function apiAggregates()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isSuper()) {
            abort(403);
        }
        $isStaff = $user->isStaff();
        $accessibleProductionIds = $isStaff ? $user->accessibleProductionIds() : [];

        // reuse the same queries used in index()
        $since24 = Carbon::now('Asia/Jakarta')->subDay()->setTimezone('UTC');
        $groupDistributionQuery = DB::table('hourly_logs as hl')
            ->leftJoin('production_machine_groups as pmg', 'hl.production_machine_group_id', '=', 'pmg.production_machine_group_id')
            ->leftJoin('machine_groups as mg', 'pmg.machine_group_id', '=', 'mg.machine_group_id')
            ->where('hl.recorded_at', '>=', $since24);

        if ($isStaff) {
            $groupDistributionQuery->whereIn('pmg.production_id', $accessibleProductionIds);
        }

        $groupDistributionNormal = (clone $groupDistributionQuery)
            ->groupBy('mg.machine_group_id', 'mg.name')
            ->selectRaw('mg.name as machine_group_name, COALESCE(SUM(hl.output_qty_normal), 0) as total_output')
            ->orderByDesc('total_output')
            ->get()
            ->map(fn ($r) => ['machine_group' => $r->machine_group_name, 'total_output' => (float) $r->total_output])
            ->toArray();

        $groupDistributionReject = (clone $groupDistributionQuery)
            ->groupBy('mg.machine_group_id', 'mg.name')
            ->selectRaw('mg.name as machine_group_name, COALESCE(SUM(hl.output_qty_reject), 0) as total_output')
            ->orderByDesc('total_output')
            ->get()
            ->map(fn ($r) => ['machine_group' => $r->machine_group_name, 'total_output' => (float) $r->total_output])
            ->toArray();

        $since7 = Carbon::now('Asia/Jakarta')->subDays(7)->setTimezone('UTC');
        $productionWeeklyQuery = DB::table('hourly_logs as hl')
            ->leftJoin('production_machine_groups as pmg', 'hl.production_machine_group_id', '=', 'pmg.production_machine_group_id')
            ->leftJoin('productions as p', 'pmg.production_id', '=', 'p.production_id')
            ->where('hl.recorded_at', '>=', $since7);

        if ($isStaff) {
            $productionWeeklyQuery->whereIn('pmg.production_id', $accessibleProductionIds);
        }

        $productionWeekly = $productionWeeklyQuery
            ->groupBy('p.production_id', 'p.production_name')
            ->selectRaw('p.production_name, COALESCE(SUM(hl.output_qty_normal) + SUM(hl.output_qty_reject), 0) as total_output')
            ->orderByDesc('total_output')
            ->get()
            ->map(fn ($r) => ['production' => $r->production_name, 'total_output' => (float) $r->total_output])
            ->toArray();

        return response()->json([
            'groupDistributionNormal' => $groupDistributionNormal,
            'groupDistributionReject' => $groupDistributionReject,
            'productionWeekly' => $productionWeekly,
        ]);
    }
}
