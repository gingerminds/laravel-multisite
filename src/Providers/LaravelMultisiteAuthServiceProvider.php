<?php

namespace Gingerminds\LaravelMultisite\Providers;

use Gingerminds\LaravelCore\Resolver\ResourceResolver;
use Gingerminds\LaravelMultisite\Models\Language\Language;
use Gingerminds\LaravelMultisite\Models\Site\Site;
use Gingerminds\LaravelMultisite\Policies\Language\LanguagePolicy;
use Gingerminds\LaravelMultisite\Policies\Site\SitePolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        // Defensive: pins the same 'user' morph alias as gingerminds-core so
        // model_has_roles/model_has_permissions stay consistent regardless of
        // provider boot order.
        Relation::morphMap([
            'user' => ResourceResolver::model('user'),
        ]);

        app(PermissionRegistrar::class)
            ->registerPermissions(app(Gate::class));

        $this->registerPolicies();
    }
}
