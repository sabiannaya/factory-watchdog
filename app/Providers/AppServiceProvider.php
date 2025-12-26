<?php

namespace App\Providers;

use App\Models\MachineGroup;
use App\Models\Production;
use App\Policies\MachineGroupPolicy;
use App\Policies\ProductionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(Production::class, ProductionPolicy::class);
        Gate::policy(MachineGroup::class, MachineGroupPolicy::class);
    }
}
