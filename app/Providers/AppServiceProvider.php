<?php

namespace App\Providers;

use App\Models\NkzOccupation;
use App\Models\Person;
use App\Observers\PersonEngagementObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Person::observe(PersonEngagementObserver::class);

        View::composer([
            'organization.structure.modals',
            'organization.structure.index',
        ], function ($view) {
            $view->with('nkzRad1gOptions', NkzOccupation::rad1gOptions());
        });

        RateLimiter::for('admin-sync', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
