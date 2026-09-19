<?php

use App\Http\Controllers\Api\Admin\OrganizationSyncController;
use App\Http\Controllers\ChatClockController;
use App\Http\Controllers\ClockController;
use App\Http\Controllers\TerminalClockController;
use App\Http\Middleware\VerifyAdminSyncApiKey;
use Illuminate\Support\Facades\Route;

Route::middleware([VerifyAdminSyncApiKey::class, 'throttle:admin-sync'])
    ->prefix('admin')
    ->name('api.admin.')
    ->group(function () {
        Route::get('organizations', [OrganizationSyncController::class, 'index'])
            ->name('organizations.index');

        Route::patch('organizations/{organization}', [OrganizationSyncController::class, 'update'])
            ->name('organizations.update');
    });

Route::prefix('{slug}')
    ->where(['slug' => '[a-z0-9\-]+'])
    ->middleware('throttle:60,1')
    ->group(function () {
        Route::post('clock/punches', [ClockController::class, 'apiStore'])
            ->middleware('clock.actor')
            ->name('api.clock.punches');
        Route::post('chat/punches', [ChatClockController::class, 'store'])
            ->middleware('clock.actor')
            ->name('api.chat.punches');
        Route::post('terminal/{token}/punches', [TerminalClockController::class, 'store'])
            ->middleware('terminal')
            ->name('api.terminal.punches');
    });

Route::middleware([VerifyAdminSyncApiKey::class, 'throttle:admin-sync'])
    ->prefix('admin')
    ->name('api.admin.')
    ->group(function () {
        Route::get('organizations', [OrganizationSyncController::class, 'index'])
            ->name('organizations.index');

        Route::patch('organizations/{organization}', [OrganizationSyncController::class, 'update'])
            ->name('organizations.update');
    });
