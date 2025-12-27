<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $q = trim((string) $request->query('q', ''));

        $query = Warehouse::query()->orderBy('created_at', 'desc');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('source', 'like', "%{$q}%")
                    ->orWhere('packing', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%");
            });
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $items = collect($paginator->items())->map(function ($w) {
            return [
                'id' => $w->warehouse_id,
                'source' => $w->source,
                'quantity' => (int) $w->quantity,
                'packing' => $w->packing,
                'notes' => $w->notes,
                'is_active' => $w->is_active,
                'created_at' => $w->created_at?->toDateTimeString(),
                'created_by' => $w->creator?->name,
                'updated_at' => $w->updated_at?->toDateTimeString(),
                'modified_by' => $w->modifier?->name,
                'deleted_at' => $w->deleted_at?->toDateTimeString(),
            ];
        })->all();

        return Inertia::render('data-management/Warehouses/Index', [
            'warehouses' => [
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

    public function create()
    {
        $this->authorize('create', Warehouse::class);

        return Inertia::render('data-management/Warehouses/Create');
    }

    public function store(WarehouseRequest $request)
    {
        $this->authorize('create', Warehouse::class);

        Warehouse::create($request->validated());

        return redirect()->route('data-management.warehouses.index')->with('success', 'Warehouse record created.');
    }

    public function show(Warehouse $warehouse)
    {
        $this->authorize('view', $warehouse);

        return Inertia::render('data-management/Warehouses/Show', [
            'warehouse' => [
                'id' => $warehouse->warehouse_id,
                'source' => $warehouse->source,
                'quantity' => (int) $warehouse->quantity,
                'packing' => $warehouse->packing,
                'notes' => $warehouse->notes,
                'is_active' => $warehouse->is_active,
                'created_by' => $warehouse->creator?->name,
                'modified_by' => $warehouse->modifier?->name,
                'deleted_at' => $warehouse->deleted_at?->toDateTimeString(),
                'deleted_by' => $warehouse->deleter?->name,
            ],
        ]);
    }

    public function edit(Warehouse $warehouse)
    {
        $this->authorize('update', $warehouse);

        return Inertia::render('data-management/Warehouses/Edit', [
            'warehouse' => [
                'id' => $warehouse->warehouse_id,
                'source' => $warehouse->source,
                'quantity' => (int) $warehouse->quantity,
                'packing' => $warehouse->packing,
                'notes' => $warehouse->notes,
                'is_active' => $warehouse->is_active,
            ],
        ]);
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse)
    {
        $this->authorize('update', $warehouse);

        $warehouse->update($request->validated());

        return redirect()->route('data-management.warehouses.index')->with('success', 'Warehouse record updated.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $this->authorize('delete', $warehouse);

        $warehouse->deleted_by = Auth::id();
        $warehouse->save();
        $warehouse->delete();

        return redirect()->route('data-management.warehouses.index')->with('success', 'Warehouse record deleted.');
    }

    public function forceDelete(Warehouse $warehouse)
    {
        $this->authorize('forceDelete', $warehouse);

        $warehouse->forceDelete();

        return redirect()->route('data-management.warehouses.index')->with('success', 'Warehouse record permanently deleted.');
    }
}
