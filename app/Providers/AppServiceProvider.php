<?php

namespace App\Providers;

use App\Http\Middleware\EnsureHasHousehold;
use App\Support\CurrentHousehold;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Eén huidig huishouden per request (scoped: wordt per request/Livewire-call opnieuw bepaald)
        $this->app->scoped(CurrentHousehold::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ook bij Livewire-updates (/livewire/update) het huishouden controleren
        Livewire::addPersistentMiddleware([
            EnsureHasHousehold::class,
        ]);
    }
}
