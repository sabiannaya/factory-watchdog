<?php

namespace App\Http\Controllers;

use App\Models\Production;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Using dummy data for front-end development. Uncomment DB code
        // below when ready to fetch real data from the database.

        $sampleData = [
            ['production_id' => 1, 'production_name' => 'Widget A', 'status' => 'active', 'created_at' => '2025-12-01'],
            ['production_id' => 2, 'production_name' => 'Widget B', 'status' => 'active', 'created_at' => '2025-12-05'],
            ['production_id' => 3, 'production_name' => 'Gadget X', 'status' => 'inactive', 'created_at' => '2025-12-03'],
            ['production_id' => 4, 'production_name' => 'Gadget Y', 'status' => 'active', 'created_at' => '2025-12-08'],
            ['production_id' => 5, 'production_name' => 'Tool Z', 'status' => 'inactive', 'created_at' => '2025-12-02'],
        ];

        return Inertia::render('data-management/Production', [
            'productions' => $sampleData,
        ]);

        // Real DB query (example):
        // $productions = Production::query()->get(['production_id', 'production_name', 'status', 'created_at']);
        // return Inertia::render('data-management/Production', ['productions' => $productions]);
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
    public function show(Production $production)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Production $production)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Production $production)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Production $production)
    {
        //
    }
}
