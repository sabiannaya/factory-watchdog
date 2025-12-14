<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\MachineGroup;
use App\Models\DailyTarget;
use App\Models\HourlyLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        // Quick stats
        $totalProductions = Production::count();
        $activeProductions = Production::where('status', 'active')->count();
        $totalMachineGroups = MachineGroup::count();
        
        // Last 7 days trend (anchor in Asia/Jakarta)
        $sevenDaysAgo = Carbon::now('Asia/Jakarta')->subDays(7)->toDateString();
        $dailyTrends = DailyTarget::where('date', '>=', $sevenDaysAgo)
            ->orderBy('date', 'asc')
            ->get(['date', 'target_value', 'actual_value'])
            ->map(fn($d) => [
                'date' => $d->date->format('m/d'),
                'target' => (int) $d->target_value,
                'actual' => (int) $d->actual_value,
            ])
            ->toArray();

        // Today's performance
        $today = now()->startOfDay();
        $todayTarget = DailyTarget::whereDate('date', $today)->first();
        $todayActual = $todayTarget?->actual_value ?? 0;
        $todayTargetValue = $todayTarget?->target_value ?? 0;
        $todayPerformance = $todayTargetValue > 0 
            ? round(($todayActual / $todayTargetValue) * 100, 1) 
            : 0;

        // Recent hourly logs (last 10)
        $recentLogs = HourlyLog::with('productionMachineGroup.production', 'productionMachineGroup.machineGroup')
            ->orderBy('recorded_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($log) => [
                'production' => $log->productionMachineGroup->production->production_name ?? '-',
                'machine_group' => $log->productionMachineGroup->machineGroup->name ?? '-',
                'machine_index' => $log->machine_index,
                'recorded_at' => $log->recorded_at->format('H:i'),
                'output' => $log->output_value,
                'target' => $log->target_value,
            ])
            ->toArray();

        // Group output distribution for last 24 hours (anchor in Asia/Jakarta -> convert to UTC)
        $since24 = Carbon::now('Asia/Jakarta')->subDay()->setTimezone('UTC');
        $groupDistribution = DB::table('hourly_logs as hl')
            ->leftJoin('production_machine_groups as pmg', 'hl.production_machine_group_id', '=', 'pmg.production_machine_group_id')
            ->leftJoin('machine_groups as mg', 'pmg.machine_group_id', '=', 'mg.machine_group_id')
            ->where('hl.recorded_at', '>=', $since24)
            ->groupBy('mg.machine_group_id', 'mg.name')
            ->selectRaw('mg.name as machine_group_name, COALESCE(SUM(hl.output_value),0) as total_output')
            ->orderByDesc('total_output')
            ->get()
            ->map(fn($r) => ['machine_group' => $r->machine_group_name, 'total_output' => (float) $r->total_output])
            ->toArray();

        // Production totals for last 7 days (anchor in Asia/Jakarta -> convert to UTC)
        $since7 = Carbon::now('Asia/Jakarta')->subDays(7)->setTimezone('UTC');
        $productionWeekly = DB::table('hourly_logs as hl')
            ->leftJoin('production_machine_groups as pmg', 'hl.production_machine_group_id', '=', 'pmg.production_machine_group_id')
            ->leftJoin('productions as p', 'pmg.production_id', '=', 'p.production_id')
            ->where('hl.recorded_at', '>=', $since7)
            ->groupBy('p.production_id', 'p.production_name')
            ->selectRaw('p.production_name, COALESCE(SUM(hl.output_value),0) as total_output')
            ->orderByDesc('total_output')
            ->get()
            ->map(fn($r) => ['production' => $r->production_name, 'total_output' => (float) $r->total_output])
            ->toArray();

        Log::debug('Dashboard returning aggregates', ['groupDistribution' => count($groupDistribution), 'productionWeekly' => count($productionWeekly)]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_productions' => $totalProductions,
                'active_productions' => $activeProductions,
                'total_machine_groups' => $totalMachineGroups,
                'today_performance' => $todayPerformance,
                'today_actual' => $todayActual,
                'today_target' => $todayTargetValue,
            ],
            'dailyTrends' => $dailyTrends,
            'recentLogs' => $recentLogs,
            'groupDistribution' => $groupDistribution,
            'productionWeekly' => $productionWeekly,
        ]);
    }

    /**
     * API endpoint to return aggregates when Inertia props are not available on client.
     */
    public function apiAggregates()
    {
        // reuse the same queries used in index()
        $since24 = Carbon::now('Asia/Jakarta')->subDay()->setTimezone('UTC');
        $groupDistribution = DB::table('hourly_logs as hl')
            ->leftJoin('production_machine_groups as pmg', 'hl.production_machine_group_id', '=', 'pmg.production_machine_group_id')
            ->leftJoin('machine_groups as mg', 'pmg.machine_group_id', '=', 'mg.machine_group_id')
            ->where('hl.recorded_at', '>=', $since24)
            ->groupBy('mg.machine_group_id', 'mg.name')
            ->selectRaw('mg.name as machine_group_name, COALESCE(SUM(hl.output_value),0) as total_output')
            ->orderByDesc('total_output')
            ->get()
            ->map(fn($r) => ['machine_group' => $r->machine_group_name, 'total_output' => (float) $r->total_output])
            ->toArray();

        $since7 = Carbon::now('Asia/Jakarta')->subDays(7)->setTimezone('UTC');
        $productionWeekly = DB::table('hourly_logs as hl')
            ->leftJoin('production_machine_groups as pmg', 'hl.production_machine_group_id', '=', 'pmg.production_machine_group_id')
            ->leftJoin('productions as p', 'pmg.production_id', '=', 'p.production_id')
            ->where('hl.recorded_at', '>=', $since7)
            ->groupBy('p.production_id', 'p.production_name')
            ->selectRaw('p.production_name, COALESCE(SUM(hl.output_value),0) as total_output')
            ->orderByDesc('total_output')
            ->get()
            ->map(fn($r) => ['production' => $r->production_name, 'total_output' => (float) $r->total_output])
            ->toArray();

        return response()->json([
            'groupDistribution' => $groupDistribution,
            'productionWeekly' => $productionWeekly,
        ]);
    }
}
