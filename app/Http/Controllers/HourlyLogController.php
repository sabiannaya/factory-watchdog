<?php

namespace App\Http\Controllers;

use App\Models\HourlyLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HourlyLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $q = trim((string) $request->input('q', ''));
        $sort = $request->input('sort', 'recorded_at');
        $direction = strtolower($request->input('direction', 'desc')) === 'desc' ? 'desc' : 'asc';
        $cursor = $request->input('cursor');
        $productionId = $request->input('production_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $allowed = ['recorded_at'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'recorded_at';
        }

        $query = HourlyLog::query()
            ->with('productionMachineGroup.production', 'productionMachineGroup.machineGroup')
            ->orderBy($sort, $direction)
            ->orderBy('hourly_log_id', 'asc');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->orWhereHas('productionMachineGroup.production', function ($pq) use ($q) {
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

        if ($dateFrom) {
            $query->where('recorded_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('recorded_at', '<=', $dateTo);
        }

        $paginator = $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

        $data = collect($paginator->items())->map(function ($log) {
            return [
                'hourly_log_id' => $log->hourly_log_id,
                'production_name' => $log->productionMachineGroup->production->production_name ?? '-',
                'machine_group' => $log->productionMachineGroup->machineGroup->name ?? '-',
                'recorded_at' => $log->recorded_at->format('Y-m-d H:i'),
                'output_normal' => $log->output_qty_normal,
                'target_normal' => $log->target_qty_normal,
                'output_reject' => $log->output_qty_reject,
                'target_reject' => $log->target_qty_reject,
                'variance_normal' => (int) (($log->output_qty_normal ?? 0) - ($log->target_qty_normal ?? 0)),
            ];
        })->all();

        return Inertia::render('logs/Index', [
            'hourlyLogs' => [
                'data' => $data,
                'next_cursor' => $paginator->nextCursor()?->encode() ?? null,
                'prev_cursor' => $paginator->previousCursor()?->encode() ?? null,
            ],
            'meta' => [
                'sort' => $sort,
                'direction' => $direction,
                'q' => $q,
                'per_page' => $perPage,
                'production_id' => $productionId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }
}
