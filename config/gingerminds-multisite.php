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

        // Name of the log channel used for Google API client errors (Drive
        // auth/quota/network failures, ...), so they can be routed to their
        // own log file instead of drowning in the app's default channel. If
        // the host app already defines a channel with this name in
        // config/logging.php, that definition is used as-is; otherwise the
        // package registers a sensible daily-file default for it (see
        // LaravelMultisiteServiceProvider::configureGoogleLogChannel()).
        'google_log_channel' => env('GINGERMINDS_MULTISITE_TRANSLATION_GOOGLE_LOG_CHANNEL', 'google'),

        // Minimum level written to that channel when the package registers
        // its own default driver for it (see google_log_channel above).
        'google_log_level' => env('GINGERMINDS_MULTISITE_TRANSLATION_GOOGLE_LOG_LEVEL', 'debug'),
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
