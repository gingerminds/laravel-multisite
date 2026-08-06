# Laravel Multisite

Multi-site and multi-language support for Laravel projects built on `gingerminds/laravel-core`. It provides:

- `Site` and `Language` models, with an admin CRUD for managing both.
- `SiteContext` / `LanguageContext` services that resolve "the current site" / "the current language" for a request.
- Traits your own models can use to scope queries by site, scope by attached language, or attach a translation model.
- A generic tabbed Blade component for editing per-language fields.
- API Platform endpoints for `Site` (read-only) and `Language` (nested only).

## Requirements

- PHP ^8.4
- `gingerminds/laravel-core` ^4.3

## Quick start

```bash
composer require gingerminds/laravel-multisite
php artisan migrate
```

Then register the package's models with API Platform (see [Installation](docs/Installation.md#2-register-the-packages-models-with-api-platform)).

## Documentation

- [Installation](docs/Installation.md)
- [Configuration](docs/Configuration.md)
- [Context](docs/Context.md) — `SiteContext`, `LanguageContext`, and how "current" is resolved
- [Traits](docs/Traits.md) — `TranslatableModelTrait`, `TranslationModelTrait`, `LanguageContextedModelTrait`, `SiteContextedModelTrait`, `BuildsTranslationAttributesTrait`
- [Components](docs/Components.md) — the translations form component
- [API](docs/API.md) — admin routes and API Platform endpoints
