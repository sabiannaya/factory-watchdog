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
    public function index()
    {
        // Backend-ready: pass actual data here when available.
        return Inertia::render('data-management/Target', [
            'dailyTargets' => [], // replace with DailyTarget::query()->get() or resource
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DailyTarget $dailyTarget)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DailyTarget $dailyTarget)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DailyTarget $dailyTarget)
    {
        //
    }
}
