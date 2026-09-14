<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // The bundled Tailwind paginator is LTR-only and hardcodes English labels.
        Paginator::defaultView('vendor.pagination.hakeem');

        // Every Permission case becomes a Gate ability; platform admins pass everything.
        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, fn (User $user) => $user->hasPermission($permission));
        }

        Gate::before(fn (User $user) => $user->isPlatformAdmin() ? true : null);
    }
}
