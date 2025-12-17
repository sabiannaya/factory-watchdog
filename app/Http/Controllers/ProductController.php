<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $q = trim((string) $request->query('q', ''));

        $query = Product::query()->orderBy('created_at', 'desc');

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $products = collect($paginator->items())->map(function ($p) {
            return [
                'id' => $p->product_id,
                'name' => $p->name,
                'thickness' => $p->thickness,
                'ply' => $p->ply,
                'glue_type' => $p->glue_type,
                'qty' => (int) $p->qty,
                'notes' => $p->notes,
                'created_at' => $p->created_at?->toDateString(),
            ];
        })->all();

        return Inertia::render('data-management/Products/Index', [
            'products' => [
                'data' => $products,
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
     * Store a newly created product in storage.
     */
    public function store(ProductRequest $request)
    {
        $product = Product::create($request->validated());

        return redirect()->route('data-management.products.index')->with('success', 'Product created.');
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        return Inertia::render('data-management/Products/Create');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        return Inertia::render('data-management/Products/Show', [
            'product' => [
                'id' => $product->product_id,
                'name' => $product->name,
                'thickness' => $product->thickness,
                'ply' => $product->ply,
                'glue_type' => $product->glue_type,
                'qty' => (int) $product->qty,
                'notes' => $product->notes,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        return Inertia::render('data-management/Products/Edit', [
            'product' => [
                'id' => $product->product_id,
                'name' => $product->name,
                'thickness' => $product->thickness,
                'ply' => $product->ply,
                'glue_type' => $product->glue_type,
                'qty' => (int) $product->qty,
                'notes' => $product->notes,
            ],
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(ProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return redirect()->route('data-management.products.index')->with('success', 'Product updated.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('data-management.products.index')->with('success', 'Product deleted.');
    }
}
