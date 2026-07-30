# Configuration

The package has a single config file, `config/gingerminds-multisite.php`, holding one `resources` array — the class bindings for its two resources, `site` and `language` — plus a `translation` array controlling the "front translations from Google Drive" feature.

```php
'resources' => [
    'language' => [
        'model'      => Language::class,
        'controller' => LanguageController::class,
        'repository' => LanguageRepository::class,
        'request'    => LanguageRequest::class,
        'provider'   => LanguageProvider::class,
    ],

    'site' => [
        'model'           => Site::class,
        'controller'      => SiteController::class,
        'repository'      => SiteRepository::class,
        'request'         => SiteRequest::class,
        'provider'        => SiteProvider::class,
        'state_processor' => SiteStateProcessor::class,
    ],
],
```

These entries are read by `Gingerminds\LaravelMultisite\Resolver\ResourceResolver` (`model()`, `controller()`, `repository()`, `request()`, `provider()`, `stateProcessor()`), the same pattern used throughout `gingerminds-laravel-core`. `Language` has no `state_processor` entry, since it has no mutating API endpoints (see [API](./API.md)).

Publish it with `php artisan vendor:publish --tag=gingerminds-multisite-config` (see [Installation](./Installation.md#4-optional-publish-the-config)), then override just the keys you need — the package's defaults are merged in for the rest.

## `translation`

```php
'translation' => [
    'enabled'            => env('GINGERMINDS_MULTISITE_TRANSLATION_ENABLED', false),
    'cache_ttl'          => env('GINGERMINDS_MULTISITE_TRANSLATION_CACHE_TTL', 300),
    'google_log_channel' => env('GINGERMINDS_MULTISITE_TRANSLATION_GOOGLE_LOG_CHANNEL', 'google'),
    'google_log_level'   => env('GINGERMINDS_MULTISITE_TRANSLATION_GOOGLE_LOG_LEVEL', 'debug'),
],
```

- `enabled` / `cache_ttl` control the "front translations from a per-site Google Drive xlsx" feature (see `TranslationService`).
- `google_log_channel` is the log channel Google API client errors (Drive auth/quota/network failures, ...) are written to, instead of the app's default channel. If your `config/logging.php` already defines a channel with this name, it's used as-is; otherwise `LaravelMultisiteServiceProvider` registers a daily-file default for it automatically (`storage/logs/{channel}.log`, 14 days retention, level from `google_log_level`) — nothing to add to `config/logging.php` unless you want a different driver (e.g. Slack).
