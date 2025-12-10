<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\MachineGroupController;
use App\Http\Controllers\DailyTargetController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::prefix('data-management')->middleware(['auth', 'verified'])->name('data-management.')->group(function () {
    Route::get('production', [ProductionController::class, 'index'])->name('production');

    Route::get('machine', [MachineGroupController::class, 'index'])->name('machine');

    Route::get('target', [DailyTargetController::class, 'index'])->name('target');
});

require __DIR__.'/settings.php';
