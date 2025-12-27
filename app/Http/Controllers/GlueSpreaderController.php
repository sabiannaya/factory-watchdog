<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlueSpreaderRequest;
use App\Models\GlueSpreader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GlueSpreaderController extends Controller
{
    /**
     * Display a listing of the glue spreaders.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $q = trim((string) $request->query('q', ''));

        $query = GlueSpreader::query()->orderBy('created_at', 'desc');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('model', 'like', "%{$q}%")
                    ->orWhere('viscosity', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%");
            });
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $items = collect($paginator->items())->map(function ($g) {
            return [
                'id' => $g->glue_spreader_id,
                'name' => $g->name,
                'model' => $g->model,
                'glue_kg' => (float) $g->glue_kg,
                'hardener_kg' => (float) $g->hardener_kg,
                'powder_kg' => (float) $g->powder_kg,
                'colorant_kg' => (float) $g->colorant_kg,
                'anti_termite_kg' => (float) $g->anti_termite_kg,
                'viscosity' => $g->viscosity,
                'washes_per_day' => (int) $g->washes_per_day,
                'glue_loss_kg' => (float) $g->glue_loss_kg,
                'notes' => $g->notes,
                'is_active' => $g->is_active,
                'created_at' => $g->created_at?->toDateTimeString(),
                'created_by' => $g->creator?->name,
                'updated_at' => $g->updated_at?->toDateTimeString(),
                'modified_by' => $g->modifier?->name,
                'deleted_at' => $g->deleted_at?->toDateTimeString(),
            ];
        })->all();

        return Inertia::render('data-management/GlueSpreaders/Index', [
            'glueSpreaders' => [
                'data' => $items,
                'links' => [
                    'next' => $paginator->nextPageUrl(),
                    'prev' => $paginator->previousPageUrl(),
                ],
            ],
            'meta' => [
                'per_page' => $perPage,
                'q' => $q,
            ],
        ]);
    }

    /**
     * Show the form for creating a new glue spreader.
     */
    public function create()
    {
        $this->authorize('create', GlueSpreader::class);

        return Inertia::render('data-management/GlueSpreaders/Create');
    }

    /**
     * Store a newly created glue spreader in storage.
     */
    public function store(GlueSpreaderRequest $request)
    {
        $this->authorize('create', GlueSpreader::class);

        GlueSpreader::create($request->validated());

        return redirect()->route('data-management.glue-spreaders.index')->with('success', 'Glue Spreader created.');
    }

    /**
     * Display the specified glue spreader.
     */
    public function show(GlueSpreader $glue_spreader)
    {
        $this->authorize('view', $glue_spreader);

        return Inertia::render('data-management/GlueSpreaders/Show', [
            'glueSpreader' => [
                'id' => $glue_spreader->glue_spreader_id,
                'name' => $glue_spreader->name,
                'model' => $glue_spreader->model,
                'glue_kg' => (float) $glue_spreader->glue_kg,
                'hardener_kg' => (float) $glue_spreader->hardener_kg,
                'powder_kg' => (float) $glue_spreader->powder_kg,
                'colorant_kg' => (float) $glue_spreader->colorant_kg,
                'anti_termite_kg' => (float) $glue_spreader->anti_termite_kg,
                'viscosity' => $glue_spreader->viscosity,
                'washes_per_day' => (int) $glue_spreader->washes_per_day,
                'glue_loss_kg' => (float) $glue_spreader->glue_loss_kg,
                'notes' => $glue_spreader->notes,
                'is_active' => $glue_spreader->is_active,
                'created_by' => $glue_spreader->creator?->name,
                'modified_by' => $glue_spreader->modifier?->name,
                'deleted_at' => $glue_spreader->deleted_at?->toDateTimeString(),
                'deleted_by' => $glue_spreader->deleter?->name,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified glue spreader.
     */
    public function edit(GlueSpreader $glue_spreader)
    {
        $this->authorize('update', $glue_spreader);

        return Inertia::render('data-management/GlueSpreaders/Edit', [
            'glueSpreader' => [
                'id' => $glue_spreader->glue_spreader_id,
                'name' => $glue_spreader->name,
                'model' => $glue_spreader->model,
                'glue_kg' => (float) $glue_spreader->glue_kg,
                'hardener_kg' => (float) $glue_spreader->hardener_kg,
                'powder_kg' => (float) $glue_spreader->powder_kg,
                'colorant_kg' => (float) $glue_spreader->colorant_kg,
                'anti_termite_kg' => (float) $glue_spreader->anti_termite_kg,
                'viscosity' => $glue_spreader->viscosity,
                'washes_per_day' => (int) $glue_spreader->washes_per_day,
                'glue_loss_kg' => (float) $glue_spreader->glue_loss_kg,
                'notes' => $glue_spreader->notes,
                'is_active' => $glue_spreader->is_active,
            ],
        ]);
    }

    /**
     * Update the specified glue spreader in storage.
     */
    public function update(GlueSpreaderRequest $request, GlueSpreader $glue_spreader)
    {
        $this->authorize('update', $glue_spreader);

        $glue_spreader->update($request->validated());

        return redirect()->route('data-management.glue-spreaders.index')->with('success', 'Glue Spreader updated.');
    }

    /**
     * Soft delete the specified glue spreader from storage.
     */
    public function destroy(GlueSpreader $glue_spreader)
    {
        $this->authorize('delete', $glue_spreader);

        $glue_spreader->deleted_by = Auth::id();
        $glue_spreader->save();
        $glue_spreader->delete();

        return redirect()->route('data-management.glue-spreaders.index')->with('success', 'Glue Spreader deleted.');
    }

    /**
     * Permanently delete the specified glue spreader from storage.
     * Only Super can force delete.
     */
    public function forceDelete(GlueSpreader $glue_spreader)
    {
        $this->authorize('forceDelete', $glue_spreader);

        $glue_spreader->forceDelete();

        return redirect()->route('data-management.glue-spreaders.index')->with('success', 'Glue Spreader permanently deleted.');
    }
}
