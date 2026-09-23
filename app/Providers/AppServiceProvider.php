<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\ServiceType;
use App\Models\Specialty;
use App\Models\User;
use App\Support\ApplyIntegrationSettings;
use App\Support\Branding;
use App\Support\Settings;
use App\Support\TrackingTags;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
        $this->app->singleton(Branding::class);
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

        if (! $this->app->runningUnitTests()) {
            try {
                app(ApplyIntegrationSettings::class)(app(Settings::class));
            } catch (\Throwable) {
                // Settings table may not exist during first migrate.
            }
        }

        View::composer('components.layouts.public', function ($view): void {
            $featured = Specialty::query()->active()->featured()->ordered()->limit(10)->get();
            $settings = app(Settings::class);
            $branding = app(Branding::class);

            $view->with([
                'navServices' => ServiceType::query()->active()->ordered()->get(),
                'navSpecialties' => $featured->isNotEmpty()
                    ? $featured
                    : Specialty::query()->active()->ordered()->limit(10)->get(),
                'appIosUrl' => $settings->get('seo.app_ios_url'),
                'appAndroidUrl' => $settings->get('seo.app_android_url'),
                'branding' => $branding,
            ]);
        });

        View::composer('components.layouts.base', function ($view): void {
            $view->with([
                'branding' => app(Branding::class),
                'tracking' => app(TrackingTags::class),
            ]);
        });

        View::composer(['components.layouts.admin', 'components.layouts.clinic', 'components.layouts.auth'], function ($view): void {
            $view->with('branding', app(Branding::class));
        });
    }
}
