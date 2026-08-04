<?php

namespace Gingerminds\LaravelMultisite\Providers;

use Gingerminds\LaravelMultisite\Models\Language\Language;
use Gingerminds\LaravelMultisite\Models\Site\Site;
use Gingerminds\LaravelMultisite\Policies\Language\LanguagePolicy;
use Gingerminds\LaravelMultisite\Policies\Site\SitePolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class LaravelMultisiteAuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Site::class     => SitePolicy::class,
        Language::class => LanguagePolicy::class,
    ];

    public function register(): void
    {
        // No bindings to register; policies are wired in boot() via registerPolicies().
    }

    public function boot(): void
    {
        app(PermissionRegistrar::class)
            ->registerPermissions(app(Gate::class));

        $this->registerPolicies();
    }
}
