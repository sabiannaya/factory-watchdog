<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HourlyInputController;
use App\Http\Controllers\HourlyLogController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MachineGroupController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProductionDefaultController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Language switching
Route::post('/language/switch', [LanguageController::class, 'switch'])->name('language.switch');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// API helpers (used by frontend when Inertia props are missing)
Route::get('/api/dashboard/aggregates', [DashboardController::class, 'apiAggregates'])->middleware(['auth', 'verified']);

// Admin routes (Super only)
Route::prefix('admin')->middleware(['auth', 'verified', 'super'])->name('admin.')->group(function () {
    // User Management
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

// Data Management routes
Route::prefix('data-management')->middleware(['auth', 'verified'])->name('data-management.')->group(function () {
    // Productions
    Route::get('production', [ProductionController::class, 'index'])->name('production');
    Route::get('production/create', [ProductionController::class, 'create'])->name('production.create');
    Route::post('production', [ProductionController::class, 'store'])->name('production.store');
    Route::get('production/{production}', [ProductionController::class, 'show'])->name('production.show');
    Route::get('production/{production}/edit', [ProductionController::class, 'edit'])->name('production.edit');
    Route::put('production/{production}', [ProductionController::class, 'update'])->name('production.update');
    Route::delete('production/{production}', [ProductionController::class, 'destroy'])->name('production.destroy');

    // Machines
    Route::get('machine', [MachineGroupController::class, 'index'])->name('machine.index');
    Route::get('machine/create', [MachineGroupController::class, 'create'])->name('machine.create');
    Route::post('machine', [MachineGroupController::class, 'store'])->name('machine.store');
    Route::get('machine/{machine_group}', [MachineGroupController::class, 'show'])->name('machine.show');
    Route::get('machine/{machine_group}/edit', [MachineGroupController::class, 'edit'])->name('machine.edit');
    Route::put('machine/{machine_group}', [MachineGroupController::class, 'update'])->name('machine.update');
    Route::delete('machine/{machine_group}', [MachineGroupController::class, 'destroy'])->name('machine.destroy');

    // Targets management (per production, per machine group, per date)
    Route::get('targets', [TargetController::class, 'index'])->name('targets.index');
    Route::get('targets/create', [TargetController::class, 'create'])->name('targets.create');
    Route::post('targets', [TargetController::class, 'store'])->name('targets.store');
    Route::get('targets/{productionMachineGroupId}/edit', [TargetController::class, 'edit'])->name('targets.edit');
    Route::put('targets/{productionMachineGroupId}', [TargetController::class, 'update'])->name('targets.update');

    // Production defaults management
    Route::get('productions/defaults', [ProductionDefaultController::class, 'index'])->name('productions.defaults.index');
    Route::get('productions/{productionMachineGroupId}/defaults/edit', [ProductionDefaultController::class, 'edit'])->name('productions.defaults.edit');
    Route::put('productions/{productionMachineGroupId}/defaults', [ProductionDefaultController::class, 'update'])->name('productions.defaults.update');

    // Product settings
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Glue Spreader settings
    Route::get('glue-spreaders', [App\Http\Controllers\GlueSpreaderController::class, 'index'])->name('glue-spreaders.index');
    Route::get('glue-spreaders/create', [App\Http\Controllers\GlueSpreaderController::class, 'create'])->name('glue-spreaders.create');
    Route::post('glue-spreaders', [App\Http\Controllers\GlueSpreaderController::class, 'store'])->name('glue-spreaders.store');
    Route::get('glue-spreaders/{glue_spreader}', [App\Http\Controllers\GlueSpreaderController::class, 'show'])->name('glue-spreaders.show');
    Route::get('glue-spreaders/{glue_spreader}/edit', [App\Http\Controllers\GlueSpreaderController::class, 'edit'])->name('glue-spreaders.edit');
    Route::put('glue-spreaders/{glue_spreader}', [App\Http\Controllers\GlueSpreaderController::class, 'update'])->name('glue-spreaders.update');
    Route::delete('glue-spreaders/{glue_spreader}', [App\Http\Controllers\GlueSpreaderController::class, 'destroy'])->name('glue-spreaders.destroy');
    Route::delete('glue-spreaders/{glue_spreader}/force', [App\Http\Controllers\GlueSpreaderController::class, 'forceDelete'])->name('glue-spreaders.force-delete');

    // Warehouse records (similar permission model to Glue Spreader)
    Route::get('warehouses', [App\Http\Controllers\WarehouseController::class, 'index'])->name('warehouses.index');
    Route::get('warehouses/create', [App\Http\Controllers\WarehouseController::class, 'create'])->name('warehouses.create');
    Route::post('warehouses', [App\Http\Controllers\WarehouseController::class, 'store'])->name('warehouses.store');
    Route::get('warehouses/{warehouse}', [App\Http\Controllers\WarehouseController::class, 'show'])->name('warehouses.show');
    Route::get('warehouses/{warehouse}/edit', [App\Http\Controllers\WarehouseController::class, 'edit'])->name('warehouses.edit');
    Route::put('warehouses/{warehouse}', [App\Http\Controllers\WarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('warehouses/{warehouse}', [App\Http\Controllers\WarehouseController::class, 'destroy'])->name('warehouses.destroy');
    Route::delete('warehouses/{warehouse}/force', [App\Http\Controllers\WarehouseController::class, 'forceDelete'])->name('warehouses.force-delete');
});

// Input routes (hourly production input)
Route::prefix('input')->middleware(['auth', 'verified'])->name('input.')->group(function () {
    Route::get('/', [HourlyInputController::class, 'index'])->name('index');
    Route::get('/create', [HourlyInputController::class, 'create'])->name('create');
    Route::post('/', [HourlyInputController::class, 'store'])->name('store');
    Route::post('/check-duplicate', [HourlyInputController::class, 'checkDuplicate'])->name('check-duplicate');
    Route::post('/bulk-delete', [HourlyInputController::class, 'bulkDelete'])->name('bulk-delete');
    Route::get('/export', [HourlyInputController::class, 'export'])->name('export');
    Route::get('/import/template', [HourlyInputController::class, 'downloadTemplate'])->name('import.template');
    Route::post('/import/preview', [HourlyInputController::class, 'previewImport'])->name('import.preview');
    Route::post('/import/execute', [HourlyInputController::class, 'executeImport'])->name('import.execute');
    Route::get('/{hourlyLog}', [HourlyInputController::class, 'show'])->name('show');
    Route::get('/{hourlyLog}/edit', [HourlyInputController::class, 'edit'])->name('edit');
    Route::put('/{hourlyLog}', [HourlyInputController::class, 'update'])->name('update');
    Route::delete('/{hourlyLog}', [HourlyInputController::class, 'destroy'])->name('destroy');
});

// Logs routes (viewing hourly logs and aggregated logs)
Route::prefix('logs')->middleware(['auth', 'verified'])->name('logs.')->group(function () {
    Route::get('/', [HourlyLogController::class, 'index'])->name('index');
    Route::get('/group', [App\Http\Controllers\AggregationController::class, 'groupLogs'])->name('group');
    Route::get('/group/export', [App\Http\Controllers\AggregationController::class, 'exportGroupLogs'])->name('group.export');
    Route::get('/production', [App\Http\Controllers\AggregationController::class, 'productionLogs'])->name('production');
    Route::get('/production/export', [App\Http\Controllers\AggregationController::class, 'exportProductionLogs'])->name('production.export');
});

// Summary routes (aggregate views)
Route::prefix('summary')->middleware(['auth', 'verified'])->name('summary.')->group(function () {
    Route::get('/daily', [App\Http\Controllers\DailySummaryController::class, 'index'])->name('daily');
    Route::get('/daily/export', [App\Http\Controllers\DailySummaryController::class, 'export'])->name('daily.export');
    Route::get('/machine-groups', [App\Http\Controllers\AggregationController::class, 'machineGroups'])->name('machine-groups');
    Route::get('/machine-groups/export', [App\Http\Controllers\AggregationController::class, 'exportMachineGroups'])->name('machine-groups.export');
    Route::get('/productions', [App\Http\Controllers\AggregationController::class, 'productions'])->name('productions');
    Route::get('/productions/export', [App\Http\Controllers\AggregationController::class, 'exportProductions'])->name('productions.export');
});

require __DIR__.'/settings.php';
