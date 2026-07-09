# Traits

Four model traits and one `FormRequest` trait, applied to your own classes, add site-scoping, language-scoping, and translation support without you having to write the boilerplate yourself.

## Model traits

Applied to your own Eloquent models.

### `TranslatableModelTrait`

For a model that has a separate "translation" model carrying its language-dependent fields (name, description, etc.) — the classic one-model-per-language-row pattern.

#### What it expects

- A sibling model holding the translatable fields, with a `language_id` column and (typically) the `TranslationModelTrait` below.
- A `protected string $translationModel = XxxTranslation::class;` property on the model **is required in practice** — the trait's default guess (`static::class . 'Translation'`) only works if your translation model lives in the exact same namespace, right next to the base model, which is rarely the case once models are organized into subfolders.

#### What it adds

- `translations(): HasMany` — every translation row.
- `currentTranslation(): HasOne` — the best-matching translation for [the current language context](./Context.md), with a fallback chain (context language → context fallback language → `Accept-Language` header), ordered so the best match is returned first.
- `translation(?int|Language $language = null, bool $fallback = true): ?Model` — get a specific translation by language (or the current one if `null`), optionally falling back to the context's fallback language if the requested one doesn't exist.
- `getTranslatedAttribute(): ?Model` — shortcut for `translation()`.
- `syncTranslations(array $translations)` — upserts translations from an array keyed by `language_id` (matches the shape produced by the [translations form component](./Components.md)).
- A global scope that eager-loads `translations` and `currentTranslation` automatically, avoiding N+1 queries when you access `$model->currentTranslation` in a list view.

#### Real usage

```php
// app/Models/Product/Product.php
use Gingerminds\LaravelMultisite\Models\Trait\TranslatableModelTrait;

class Product extends Model implements ResourceModelInterface, /* ... */
{
    use TranslatableModelTrait;

    protected string $translationModel = ProductTranslation::class;

    public function getNameAttribute(): ?string
    {
        /** @var ProductTranslation|null $translation */
        $translation = $this->currentTranslation;

        return $translation?->name;
    }
}
```

```php
// Persisting translations from a form submission, keyed by language id:
$product->syncTranslations($request->input('translations', []));
```

### `TranslationModelTrait`

For the translation-side model itself (the one referenced by `$translationModel` above).

#### What it expects

A `language_id` column (fillable) pointing at the `languages` table.

#### What it adds

- `language(): BelongsTo` — the `Language` this row is for.
- `isFor(Language|int $language): bool` — convenience check against a language or a language id.

#### Real usage

```php
// app/Models/Product/ProductTranslation.php
use Gingerminds\LaravelMultisite\Models\Trait\TranslationModelTrait;

class ProductTranslation extends Model implements ResourceModelInterface
{
    use TranslationModelTrait;

    public function getFillable(): array
    {
        return ['name', 'hook', 'booklet_id', 'language_id'];
    }
}
```

### `LanguageContextedModelTrait`

For a model that isn't itself translated, but should only appear when it's attached to the current language — e.g. a media item flagged as relevant to specific languages.

#### What it expects

A `languages(): BelongsToMany` relation on the model, pointing at `Language` through your own pivot table.

#### What it adds

A global scope restricting the query to rows `whereHas('languages', ...)` matching the [current language context](./Context.md#languagecontext) (falling back to the `Accept-Language` header if no context is bound).

#### Real usage

```php
// app/Models/Media/Media.php
use Gingerminds\LaravelMultisite\Models\Trait\LanguageContextedModelTrait;

class Media extends BaseMedia implements FilterableModelInterface
{
    use LanguageContextedModelTrait;

    /**
     * @return BelongsToMany<Language, $this>
     */
    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'language_media', 'media_id', 'language_id');
    }
}
```

### `SiteContextedModelTrait`

For a model that belongs to a single site (via a `site_id` column) and should be scoped to [the current site](./Context.md#sitecontext) automatically, with rows that have no `site_id` at all treated as global/shared.

#### What it expects

A `site_id` column on the model's table.

#### What it adds

- A global scope: `site_id = current site OR site_id IS NULL`.
- Auto-fills `site_id` on `creating`, from the current site context, if not already set.
- `site(): BelongsTo` and a `scopeForSite($query, $siteId = null)` local scope for explicit filtering.

> **Known issue:** as of the current version, `scopeForSite()` and the auto-fill-on-create logic call `SiteContext::id()` / `SiteContext::has()`, but `SiteContext` only exposes `site(): ?Site` — those two call sites will throw `Error: Call to undefined method`. This trait is not used anywhere in the `yanmar-extranet` codebase today, which is likely why this hasn't surfaced yet. If you plan to use this trait, this needs a fix in the package first (either add `id()`/`has()` to `SiteContext`, or change the trait to use `site()?->id` / `site() !== null` like the rest of the trait already does).

## Form request traits

Applied to your own `FormRequest` classes — not models.

### `BuildsTranslationAttributesTrait`

For any `FormRequest` validating a model that uses `TranslatableModelTrait` above. Every such request submits the same `translations.{languageId}.{field}` shape, and `attributes()` needs the same per-language label override so validation errors read naturally (e.g. "Title (en)") instead of the raw dotted key (`translations.3.title`) — this factors that loop out so it isn't copy-pasted into every request.

#### What it expects

- Used inside a class that has Laravel's `FormRequest` behavior (i.e. `$this->input(...)` resolves against the current request) — normally just `use`d directly on a `FormRequest` subclass.
- A bound [`SiteContext`](./Context.md#sitecontext) with a current site that has `languages` — used to resolve each submitted `language_id` to its ISO code for the label suffix.

#### What it adds

- `translationAttributes(array $labels): array` (`protected`) — given a `[$field => $humanLabel]` map, returns `["translations.$langId.$field" => "$humanLabel ($iso)"]` for every language/field actually present in the submitted `translations` input. Fields with no entry in `$labels` fall back to the raw field name.

#### Real usage

```php
// app/Http/Requests/Product/ProductRequest.php
use Gingerminds\LaravelMultisite\Http\Requests\Concerns\BuildsTranslationAttributesTrait;

class ProductRequest extends FormRequest implements FormRequestInterface
{
    use BuildsTranslationAttributesTrait;

    public function attributes(): array
    {
        return $this->translationAttributes([
            'name' => __('validation.attributes.name'),
            'hook' => __('validation.attributes.hook'),
        ]);
    }
}
```

If the request also has its own non-translated attributes to label (e.g. a top-level `category_id`), merge them in rather than calling `translationAttributes()` alone:

```php
public function attributes(): array
{
    $attributes = ['category_id' => __('validation.attributes.category_id')];

    return $attributes + $this->translationAttributes([
        'name' => __('validation.attributes.name'),
    ]);
}
```
