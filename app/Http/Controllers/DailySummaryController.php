<?php

namespace App\Http\Controllers;

use App\Exports\DailySummaryExport;
use App\Models\DailyTargetValue;
use App\Models\HourlyLog;
use App\Models\Production;
use App\Models\ProductionMachineGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class DailySummaryController extends Controller
{
    /**
     * Display daily summary report.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Normalize incoming date
        $rawDate = $request->input('date');

        if (! $request->has('date') || empty($rawDate) || $rawDate === 'undefined' || $rawDate === 'null') {
            $qs = $request->query();
            $qs['date'] = now('Asia/Jakarta')->toDateString();

            return redirect()->route('summary.daily', $qs);
        }

        if ($rawDate) {
            if (str_contains($rawDate, '/')) {
                try {
                    $parts = explode('/', $rawDate);
                    if (count($parts) === 3) {
                        $date = Carbon::createFromFormat('d/m/Y', $rawDate, 'Asia/Jakarta')->toDateString();
                    } else {
                        $date = Carbon::parse($rawDate, 'Asia/Jakarta')->toDateString();
                    }
                } catch (\Exception $e) {
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

        $productionId = $request->input('production_id');
        if ($productionId === 'undefined' || $productionId === 'null' || trim((string) $productionId) === '') {
            $productionId = null;
        }

        // Build date range in Asia/Jakarta then convert to UTC for DB comparisons
        $startOfDay = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Jakarta')->startOfDay()->setTimezone('UTC');
        $endOfDay = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Jakarta')->endOfDay()->setTimezone('UTC');

        // Get productions for filter
        $productionsQuery = Production::where('status', 'active')
            ->orderBy('production_name');

        if ($user->isStaff()) {
            $productionsQuery->whereIn('production_id', $user->accessibleProductionIds());
        }

        $productions = $productionsQuery->get()->map(function ($prod) {
            return [
                'production_id' => $prod->production_id,
                'production_name' => $prod->production_name,
            ];
        });

        // Get summary data
        $summaryQuery = ProductionMachineGroup::query()
            ->with(['production', 'machineGroup'])
            ->whereHas('production', function ($q) {
                $q->where('status', 'active');
            });

        if ($productionId) {
            $summaryQuery->where('production_id', $productionId);
        }

        if ($user->isStaff()) {
            $summaryQuery->whereIn('production_id', $user->accessibleProductionIds());
        }

        $summaryData = $summaryQuery->get()->map(function ($pmg) use ($date, $startOfDay, $endOfDay) {
            // Get daily target values for this machine group (match by date)
            $targetValues = DailyTargetValue::where('production_machine_group_id', $pmg->production_machine_group_id)
                ->where('date', $date)
                ->get()
                ->keyBy('field_name');

            // Aggregate hourly logs for this date
            $hourlyLogs = HourlyLog::where('production_machine_group_id', $pmg->production_machine_group_id)
                ->whereBetween('recorded_at', [$startOfDay, $endOfDay])
                ->get();

            // Calculate actual outputs
            $actualQtyNormal = $hourlyLogs->sum('output_qty_normal') ?? 0;
            $actualQtyReject = $hourlyLogs->sum('output_qty_reject') ?? 0;
            $actualTotal = $actualQtyNormal + $actualQtyReject;

            // Get target values with fallback to machine group's default_targets
            $defaults = is_array($pmg->default_targets) ? $pmg->default_targets : [];
            $targetQtyNormal = $targetValues->get('qty_normal')?->target_value
                ?? ($defaults['qty_normal'] ?? ($defaults['qty'] ?? 0));
            $targetQtyReject = $targetValues->get('qty_reject')?->target_value
                ?? ($defaults['qty_reject'] ?? 0);
            $targetTotal = $targetQtyNormal + $targetQtyReject;

            // Calculate achievement
            $achievementPercentage = $targetTotal > 0
                ? round(($actualTotal / $targetTotal) * 100, 1)
                : 0;

            // Contextual variance: (Qty Normal - Target Normal) + (Target Reject - Qty Reject)
            $variance = ($actualQtyNormal - $targetQtyNormal) + ($targetQtyReject - $actualQtyReject);

            return [
                'production_machine_group_id' => $pmg->production_machine_group_id,
                'production_name' => $pmg->production->production_name ?? '-',
                'machine_group_name' => $pmg->machineGroup->name ?? '-',
                'machine_count' => $pmg->machine_count ?? 1,
                'target_qty_normal' => $targetQtyNormal,
                'target_qty_reject' => $targetQtyReject,
                'target_total' => $targetTotal,
                'actual_qty_normal' => $actualQtyNormal,
                'actual_qty_reject' => $actualQtyReject,
                'actual_total' => $actualTotal,
                'variance' => $variance,
                'achievement_percentage' => $achievementPercentage,
                'status' => $variance >= 0 ? 'achieved' : 'below',
            ];
        })->values();

        return Inertia::render('summary/DailySummary', [
            'date' => $date,
            'productions' => $productions,
            'selectedProductionId' => $productionId ? (int) $productionId : null,
            'summaryData' => $summaryData,
        ]);
    }

    /**
     * Export daily summary to Excel
     */
    public function export(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rawDate = $request->input('date', now('Asia/Jakarta')->toDateString());

        if ($rawDate) {
            try {
                $date = Carbon::parse($rawDate, 'Asia/Jakarta')->toDateString();
            } catch (\Exception $e) {
                $date = now('Asia/Jakarta')->toDateString();
            }
        } else {
            $date = now('Asia/Jakarta')->toDateString();
        }

        $productionId = $request->input('production_id');
        if ($productionId === 'undefined' || $productionId === 'null' || trim((string) $productionId) === '') {
            $productionId = null;
        }

        $startOfDay = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Jakarta')->startOfDay()->setTimezone('UTC');
        $endOfDay = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Jakarta')->endOfDay()->setTimezone('UTC');

        $summaryQuery = ProductionMachineGroup::query()
            ->with(['production', 'machineGroup'])
            ->whereHas('production', function ($q) {
                $q->where('status', 'active');
            });

        if ($productionId) {
            $summaryQuery->where('production_id', $productionId);
        }

        if ($user->isStaff()) {
            $summaryQuery->whereIn('production_id', $user->accessibleProductionIds());
        }

        $summaryData = $summaryQuery->get()->map(function ($pmg) use ($date, $startOfDay, $endOfDay) {
            $targetValues = DailyTargetValue::where('production_machine_group_id', $pmg->production_machine_group_id)
                ->where('date', $date)
                ->get()
                ->keyBy('field_name');

            $hourlyLogs = HourlyLog::where('production_machine_group_id', $pmg->production_machine_group_id)
                ->whereBetween('recorded_at', [$startOfDay, $endOfDay])
                ->get();

            $actualQtyNormal = $hourlyLogs->sum('output_qty_normal') ?? 0;
            $actualQtyReject = $hourlyLogs->sum('output_qty_reject') ?? 0;
            $actualTotal = $actualQtyNormal + $actualQtyReject;

            $defaults = is_array($pmg->default_targets) ? $pmg->default_targets : [];
            $targetQtyNormal = $targetValues->get('qty_normal')?->target_value
                ?? ($defaults['qty_normal'] ?? ($defaults['qty'] ?? 0));
            $targetQtyReject = $targetValues->get('qty_reject')?->target_value
                ?? ($defaults['qty_reject'] ?? 0);
            $targetTotal = $targetQtyNormal + $targetQtyReject;

            $achievementPercentage = $targetTotal > 0
                ? round(($actualTotal / $targetTotal) * 100, 1)
                : 0;

            $variance = ($actualQtyNormal - $targetQtyNormal) + ($targetQtyReject - $actualQtyReject);

            return [
                'production_name' => $pmg->production->production_name ?? '-',
                'machine_group_name' => $pmg->machineGroup->name ?? '-',
                'machine_count' => $pmg->machine_count ?? 1,
                'target_qty_normal' => $targetQtyNormal,
                'target_qty_reject' => $targetQtyReject,
                'target_total' => $targetTotal,
                'actual_qty_normal' => $actualQtyNormal,
                'actual_qty_reject' => $actualQtyReject,
                'actual_total' => $actualTotal,
                'variance' => $variance,
                'achievement_percentage' => $achievementPercentage,
                'status' => $variance >= 0 ? 'achieved' : 'below',
            ];
        });

        $filename = 'daily-summary-'.$date.'.xlsx';

        return Excel::download(new DailySummaryExport($summaryData, 'Daily Summary'), $filename);
    }
}
