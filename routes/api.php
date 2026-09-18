<?php

use App\Http\Controllers\Api\Admin\OrganizationSyncController;
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
