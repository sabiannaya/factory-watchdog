<?php

namespace App\Http\Controllers;

use App\Models\DailyTarget;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DailyTargetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int) request()->input('per_page', 10);
        $q = trim((string) request()->input('q', ''));
        $sort = request()->input('sort', 'date');
        $direction = strtolower(request()->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $cursor = request()->input('cursor');

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $allowed = ['date', 'target_value', 'actual_value'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'date';
        }

        $query = DailyTarget::query()
            ->select(['daily_target_id', 'date', 'target_value', 'actual_value', 'notes', 'created_at'])
            ->orderBy($sort, $direction)
            ->orderBy('daily_target_id', 'asc');

        if ($q !== '') {
            $query->where('notes', 'like', "%{$q}%");
        }

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $p = $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

        $data = collect($p->items())->map(fn ($d) => [
            'daily_target_id' => $d->daily_target_id,
            'date' => $d->date->toDateString(),
            'target_value' => $d->target_value,
            'actual_value' => $d->actual_value,
            'notes' => $d->notes,
        ])->all();

        return Inertia::render('data-management/Target', [
            'dailyTargets' => [
                'data' => $data,
                'next_cursor' => $p->nextCursor()?->encode() ?? null,
                'prev_cursor' => $p->previousCursor()?->encode() ?? null,
            ],
            'meta' => [
                'sort' => $sort,
                'direction' => $direction,
                'q' => $q,
                'per_page' => $perPage,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
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
    public function show(DailyTarget $dailyTarget)
    {
        return Inertia::render('data-management/TargetShow', [
            'dailyTarget' => [
                'daily_target_id' => $dailyTarget->daily_target_id,
                'date' => $dailyTarget->date->toDateString(),
                'target_value' => $dailyTarget->target_value,
                'actual_value' => $dailyTarget->actual_value,
                'notes' => $dailyTarget->notes,
                'created_at' => $dailyTarget->created_at->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DailyTarget $dailyTarget)
    {
        return Inertia::render('data-management/TargetEdit', [
            'dailyTarget' => [
                'daily_target_id' => $dailyTarget->daily_target_id,
                'date' => $dailyTarget->date->toDateString(),
                'target_value' => $dailyTarget->target_value,
                'actual_value' => $dailyTarget->actual_value,
                'notes' => $dailyTarget->notes,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DailyTarget $dailyTarget)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'target_value' => ['required', 'integer', 'min:0'],
            'actual_value' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $dailyTarget->update($data);

        return redirect()->route('data-management.target')->with('success', 'Daily target updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DailyTarget $dailyTarget)
    {
        $dailyTarget->delete();

        return redirect()->route('data-management.target')->with('success', 'Daily target deleted.');
    }
}
