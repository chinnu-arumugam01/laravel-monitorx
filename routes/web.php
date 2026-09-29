<?php

use App\Http\Controllers\MonitoringController;
use App\Http\Middleware\ProtectMonitorX;
use Illuminate\Support\Facades\Route;

Route::get('/', [MonitoringController::class, 'dashboard'])
    ->middleware(ProtectMonitorX::class)
    ->name('home');

Route::middleware(ProtectMonitorX::class)
    ->prefix(config('monitorx.path', 'monitorx'))
    ->name('monitorx.')
    ->group(function () {
        Route::get('/', [MonitoringController::class, 'dashboard'])->name('dashboard');
        Route::get('/search', [MonitoringController::class, 'search'])->name('search');
        Route::get('/settings', [MonitoringController::class, 'settings'])->name('settings');

        // Saved Filters
        Route::get('/saved-filters', [MonitoringController::class, 'savedFiltersIndex'])->name('saved-filters.index');
        Route::post('/saved-filters', [MonitoringController::class, 'savedFiltersStore'])->name('saved-filters.store');
        Route::delete('/saved-filters/{filter}', [MonitoringController::class, 'savedFiltersDestroy'])->name('saved-filters.destroy');

        // Module routes
        Route::get('/{module}/export', [MonitoringController::class, 'export'])->name('export');
        Route::get('/{module}/{record}', [MonitoringController::class, 'show'])->whereNumber('record')->name('show');
        Route::get('/{module}', [MonitoringController::class, 'index'])->name('index');
    });
