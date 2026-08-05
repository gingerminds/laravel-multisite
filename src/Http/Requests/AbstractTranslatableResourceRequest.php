<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Http\Requests;

use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelMultisite\Http\Requests\Concerns\BuildsTranslationAttributesTrait;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Shared scaffolding for a FormRequest backing a per-site, per-language
 * translatable resource with owner-level + per-translation file fields and
 * (optionally) CMS content blocks — the shape `App\Http\Requests\Event\EventRequest`,
 * `App\Http\Requests\News\NewsRequest` and `Gingerminds\LaravelCms\Http\Request\Page\PageRequest`
 * each independently reimplemented (dynamic per-language required/nullable
 * rules, file field handling, slug uniqueness, attribute labelling).
 *
 * Deliberately has no dependency on `gingerminds/laravel-cms`: content-block
 * validation (`ContentFieldSupport`) is opt-in via the `contentBlockRules()`/
 * `contentBlockAttributes()`/`decodeTranslations()` hooks below, which are
 * no-ops here so a resource without content blocks doesn't need to override
 * them at all.
 */
abstract class AbstractTranslatableResourceRequest extends FormRequest implements FormRequestInterface
{
    use BuildsTranslationAttributesTrait;

    /**
     * Owner + per-translation file field names, e.g. `['main_visual', 'thumbnail']`.
     *
     * @return list<string>
     */
    abstract protected function fileFields(): array;

    /**
     * Per-language label used in validation attributes for each
     * `translations.*` field — combined with content-block labels (if any)
     * in `baseAttributes()`.
     *
     * @return array<string, string>
     */
    abstract protected function translationFieldLabels(): array;

    /**
     * Translation text fields allowed to stay empty even for the default
     * language (e.g. `slug`, generated from `title` client-side).
     *
     * @return list<string>
     */
    protected function optionalTextFields(): array
    {
        return [];
    }

    /**
     * @return array{image?: bool, maxKb?: int}
     */
    protected function fileRuleOptions(string $field): array
    {
        return ['image' => true, 'maxKb' => 5120];
    }

    /**
     * Table backing `slug` uniqueness for translations (e.g.
     * `'event_translations'`); `null` skips the uniqueness rule entirely.
     */
    protected function slugTranslationsTable(): ?string
    {
        return null;
    }

    /**
     * Existing translation row id for `$langId`, to `ignore()` it in the
     * slug-uniqueness check when editing.
     */
    protected function existingTranslationId(int|string $langId): ?int
    {
        return null;
    }

    /**
     * Input key holding CMS content blocks (e.g. `'content'`); `null`
     * disables all content-block handling below.
     */
    protected function contentFieldName(): ?string
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function contentBlockRules(int|string $langId): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function contentBlockAttributes(int|string $langId, string $languageLabel): array
    {
        return [];
    }

    /**
     * Hook to decode/normalize the submitted translations before
     * validation runs (e.g. the JSON-encoded content blocks payload).
     *
     * @param  array<int|string, array<string, mixed>>  $translations
     * @return array<int|string, array<string, mixed>>
     */
    protected function decodeTranslations(array $translations): array
    {
        return $translations;
    }

    protected function siteId(): ?int
    {
        return app(SiteContext::class)->site()?->id;
    }

    protected function defaultLanguageId(): ?int
    {
        return app(SiteContext::class)->site()?->defaultLanguage()->first()?->id;
    }

    protected function languageLabelFor(int|string $langId): string
    {
        $languages = app(SiteContext::class)->site()?->languages ?? collect(); // @phpstan-ignore nullsafe.neverNull

        return (string) ($languages->firstWhere('id', $langId)->iso ?? $langId);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'translations' => $this->decodeTranslations($this->input('translations', [])),
        ]);
    }

    /**
     * Every language id that has *something* submitted for it, whether a
     * text field or a file upload — either input alone would miss
     * languages that only have the other kind of field set.
     *
     * @return list<int|string>
     */
    protected function submittedLanguageIds(): array
    {
        return array_values(array_unique(array_merge(
            array_keys($this->input('translations', [])),
            array_keys($this->file('translations', []))
        )));
    }

    protected function isFileOrRemoveField(string $field): bool
    {
        return in_array($field, $this->fileFields(), true) || str_ends_with($field, '_remove');
    }

    /**
     * @return array<string>
     */
    protected function requiredOrNullableRule(int|string $langId, string $field): array
    {
        $isDefaultLanguage = (string) $langId === (string) $this->defaultLanguageId();

        if ($isDefaultLanguage && !in_array($field, $this->optionalTextFields(), true)) {
            return ['required', 'string'];
        }

        return ['nullable', 'string'];
    }

    protected function uniqueSlugRule(int|string $langId): ?Unique
    {
        $table = $this->slugTranslationsTable();

        if ($table === null) {
            return null;
        }

        return Rule::unique($table, 'slug')
            ->where(fn ($query) => $query
                ->where('site_id', $this->siteId())
                ->where('language_id', $langId))
            ->ignore($this->existingTranslationId($langId));
    }

    /**
     * @return string[]
     */
    protected function fileRule(string $key): array
    {
        if (!$this->hasFile($key)) {
            return ['nullable'];
        }

        // `translations.3.main_visual` resolves options the same way as the
        // bare `main_visual` key — only the trailing segment identifies the field.
        $field   = str_contains($key, '.') ? Str::afterLast($key, '.') : $key;
        $options = $this->fileRuleOptions($field);

        return ($options['image'] ?? true)
            ? ['file', 'image', 'max:' . ($options['maxKb'] ?? 5120)]
            : ['file', 'max:' . ($options['maxKb'] ?? 5120)];
    }

    /**
     * Every rule for one submitted language: its file fields, plus every
     * other translation field (required/nullable, and `slug`'s extra
     * uniqueness check), plus content-block rules if `contentFieldName()`
     * is set.
     *
     * @return array<string, mixed>
     */
    protected function translationFieldRules(int|string $langId): array
    {
        $rules = [];

        foreach ($this->fileFields() as $field) {
            $rules["translations.$langId.$field"]          = $this->fileRule("translations.$langId.$field");
            $rules["translations.$langId.{$field}_remove"] = ['nullable', 'boolean'];
        }

        $contentField = $this->contentFieldName();

        foreach ($this->input("translations.$langId", []) as $field => $value) {
            if ($this->isFileOrRemoveField((string) $field) || $field === $contentField) {
                continue;
            }

            $fieldRules = $this->requiredOrNullableRule($langId, (string) $field);

            if ($field === 'slug' && ($uniqueRule = $this->uniqueSlugRule($langId)) !== null) {
                $fieldRules[] = $uniqueRule;
            }

            $rules["translations.$langId.$field"] = $fieldRules;
        }

        return [...$rules, ...$this->contentBlockRules($langId)];
    }

    /**
     * `translationAttributes()` (from `BuildsTranslationAttributesTrait`)
     * only labels top-level translation fields; content-block fields (if
     * any) get their own per-field label via `contentBlockAttributes()`.
     * Concrete requests fold this into `attributes()` alongside their own
     * non-translated field labels (e.g. `code`, `categories`).
     *
     * @return array<string, string>
     */
    protected function baseAttributes(): array
    {
        $attributes = $this->translationAttributes($this->translationFieldLabels());

        foreach ($this->submittedLanguageIds() as $langId) {
            $attributes += $this->contentBlockAttributes($langId, $this->languageLabelFor($langId));
        }

        return $attributes;
    }

    /**
     * Generic `(required, string, max:255, unique per site)` rule for a
     * resource-level business identifier (e.g. `code`) — every resource
     * built on this base needs the exact same shape, only the table/ignored
     * id differ.
     *
     * @return array<int, mixed>
     */
    protected function uniqueCodeRule(string $table, ?int $ignoreId, string $column = 'code'): array
    {
        return [
            'required', 'string', 'max:255',
            Rule::unique($table, $column)
                ->where(fn ($query) => $query->where('site_id', $this->siteId()))
                ->ignore($ignoreId),
        ];
    }
}
