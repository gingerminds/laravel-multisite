<?php

use Gingerminds\LaravelMultisite\ApiProvider\Language\LanguageProvider;
use Gingerminds\LaravelMultisite\Http\Requests\Site\SiteRequest;
use Gingerminds\LaravelMultisite\Models\Language\Language;
use Gingerminds\LaravelMultisite\Models\Site\Site;
use Gingerminds\LaravelMultisite\Http\Controllers\Language\LanguageController;
use Gingerminds\LaravelMultisite\Http\Controllers\Site\SiteController;
use Gingerminds\LaravelMultisite\Repositories\Language\LanguageRepository;
use Gingerminds\LaravelMultisite\Repositories\Site\SiteRepository;
use Gingerminds\LaravelMultisite\Http\Requests\Language\LanguageRequest;
use Gingerminds\LaravelMultisite\ApiProvider\Site\SiteProvider;
use Gingerminds\LaravelMultisite\StateProcessor\Site\SiteStateProcessor;

return [
    'translation' => [
        // Master switch for the whole "front translations from a Google
        // Drive xlsx" feature (routes, provider, admin fields).
        'enabled' => env('GINGERMINDS_MULTISITE_TRANSLATION_ENABLED', false),

        // How long the parsed translations (locale => [key => value]) are
        // kept in cache per site, in seconds. This only protects against
        // hammering the Google Drive API on high traffic — translations are
        // still fetched "live", just not re-downloaded on every request.
        'cache_ttl' => env('GINGERMINDS_MULTISITE_TRANSLATION_CACHE_TTL', 300),
    ],

    'resources' => [
        'language' => [
            'model' => Language::class,
            'controller' => LanguageController::class,
            'repository' => LanguageRepository::class,
            'request' => LanguageRequest::class,
            'provider' => LanguageProvider::class
        ],

        'site' => [
            'model' => Site::class,
            'controller' => SiteController::class,
            'repository' => SiteRepository::class,
            'request' => SiteRequest::class,
            'provider' => SiteProvider::class,
            'state_processor' => SiteStateProcessor::class
        ],
    ],
];
