# Site & Language Context

Two services answer "what site/language is this request for?": `SiteContext` and `LanguageContext`. Both are bound as `scoped` (resolved once per request, memoized) — there is **no middleware to add to your application**; just inject them wherever you need the current site or language.

```php
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Gingerminds\LaravelMultisite\Services\Context\LanguageContext;

public function __construct(
    private readonly SiteContext $siteContext,
    private readonly LanguageContext $languageContext,
) {}

public function handle()
{
    $site = $this->siteContext->site(); // ?Site

    $current  = $this->languageContext->current();  // ?Language
    $fallback = $this->languageContext->fallback(); // ?Language
    $hasAny   = $this->languageContext->has();       // bool
}
```

Or resolve them directly from the container: `app(SiteContext::class)->site()`.

## `SiteContext`

- `site(): ?Site` — the current site, memoized for the lifetime of the request.

Resolution is delegated to `SiteContextResolver`, in order:

1. The `admin_site_id` session value, if present.
2. The `X-Site-Id` request header.
3. A `Site` whose `url` matches the current request host (`LIKE %host%`).
4. The first `Site` in the table, as a last resort.

## `LanguageContext`

- `current(): ?Language` — the best-matching language for this request.
- `fallback(): ?Language` — the site's default language (used when nothing better matches).
- `has(): bool` — whether either `current()` or `fallback()` resolved to something.

Resolution is delegated to `LanguageContextResolver`, and depends on `SiteContext` first resolving a site (no site → both `current()` and `fallback()` return `null`):

1. The fallback is the site's language flagged `is_default` in the `site_language` pivot (or, if none is flagged, the site's first attached language).
2. The `Accept-Language` header is parsed into an ordered list of locale codes; the first one matching one of the site's attached languages (by `iso`) becomes `current()`.
3. If no header, or nothing matches, `current()` falls back to the same value as `fallback()`.

## Where this is used

`TranslatableModelTrait` and `LanguageContextedModelTrait` (see [Traits](./Traits.md)) both consult `LanguageContext` internally to decide which translation/language-scoped rows to return, with the `Accept-Language` header as a safe fallback when no context is bound (e.g. in a console command or queued job).

## Swagger / OpenAPI documentation

`LaravelMultisiteServiceProvider::boot()` registers `SiteContextedModelTrait` → `X-Site-Id` and `LanguageContextedModelTrait`/`TranslatableModelTrait` → `Accept-Language` on `Gingerminds\LaravelCore\ApiPlatform\ApiHeaderParameterRegistry`. Any resource — in this package, another package, or a project — that uses one of these traits automatically gets the corresponding header documented on every one of its OpenAPI operations, with no attribute to add on the model itself. See [laravel-core's API docs](../../gingerminds-laravel-core/docs/API.md#documenting-context-headers-x-site-id-accept-language-) for how the mechanism works.
