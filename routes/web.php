<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\MachineGroupController;
use App\Http\Controllers\DailyTargetController;
use App\Http\Controllers\HourlyLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\ProductionDefaultController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// API helpers (used by frontend when Inertia props are missing)
Route::get('/api/dashboard/aggregates', [DashboardController::class, 'apiAggregates'])->middleware(['auth', 'verified']);

Route::prefix('data-management')->middleware(['auth', 'verified'])->name('data-management.')->group(function () {
    Route::get('production', [ProductionController::class, 'index'])->name('production');
    Route::get('production/create', [ProductionController::class, 'create'])->name('production.create');
    Route::post('production', [ProductionController::class, 'store'])->name('production.store');
    Route::get('production/{production}', [ProductionController::class, 'show'])->name('production.show');
    Route::get('production/{production}/edit', [ProductionController::class, 'edit'])->name('production.edit');
    Route::put('production/{production}', [ProductionController::class, 'update'])->name('production.update');
    Route::delete('production/{production}', [ProductionController::class, 'destroy'])->name('production.destroy');

        Route::get('machine', [MachineGroupController::class, 'index'])->name('machine.index');
        Route::get('machine/create', [MachineGroupController::class, 'create'])->name('machine.create');
        Route::post('machine', [MachineGroupController::class, 'store'])->name('machine.store');
        Route::get('machine/{machine_group}', [MachineGroupController::class, 'show'])->name('machine.show');
        Route::get('machine/{machine_group}/edit', [MachineGroupController::class, 'edit'])->name('machine.edit');
        Route::put('machine/{machine_group}', [MachineGroupController::class, 'update'])->name('machine.update');
        Route::delete('machine/{machine_group}', [MachineGroupController::class, 'destroy'])->name('machine.destroy');

    // Targets management (per production, per machine group, per date)
    Route::get('target', [TargetController::class, 'index'])->name('target');
    Route::get('targets', [TargetController::class, 'index'])->name('targets.index');
    Route::get('targets/create', [TargetController::class, 'create'])->name('targets.create');
    Route::post('targets', [TargetController::class, 'store'])->name('targets.store');
    Route::get('targets/{productionMachineGroupId}/edit', [TargetController::class, 'edit'])->name('targets.edit');
    Route::put('targets/{productionMachineGroupId}', [TargetController::class, 'update'])->name('targets.update');

    // Production defaults management
    Route::get('productions/defaults', [ProductionDefaultController::class, 'index'])->name('productions.defaults.index');
    Route::get('productions/{productionMachineGroupId}/defaults/edit', [ProductionDefaultController::class, 'edit'])->name('productions.defaults.edit');
    Route::put('productions/{productionMachineGroupId}/defaults', [ProductionDefaultController::class, 'update'])->name('productions.defaults.update');

    Route::get('hourly-logs', [HourlyLogController::class, 'index'])->name('hourly-logs');
    Route::get('aggregates/machine-groups', [App\Http\Controllers\AggregationController::class, 'machineGroups'])->name('aggregates.machine-groups');
    Route::get('aggregates/productions', [App\Http\Controllers\AggregationController::class, 'productions'])->name('aggregates.productions');
    Route::get('logs/group', [App\Http\Controllers\AggregationController::class, 'groupLogs'])->name('logs.group');
    Route::get('logs/production', [App\Http\Controllers\AggregationController::class, 'productionLogs'])->name('logs.production');
});

require __DIR__.'/settings.php';
