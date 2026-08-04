<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Models\Trait;

use Gingerminds\LaravelCore\Models\EagerLoadableModelInterface;
use Gingerminds\LaravelMultisite\Models\Language\Language;
use Gingerminds\LaravelMultisite\Services\Context\LanguageContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Throwable;

trait TranslatableModelTrait
{
    /**
     * `translations`/`currentTranslation` are always eager-loaded, and so is
     * `language` on each — it's `TranslationModelTrait`'s own relation, used
     * by essentially every `getSwitchLangAttribute()`-style accessor
     * (`Product`, `Page`, `Event`...) to key its result by ISO, so it's not
     * project- or model-specific the way `mainVisual`/`thumbnail`/
     * `downloadableFile` are. On top of that, if the translation model
     * itself declares `EagerLoadableModelInterface` (e.g. `EventTranslation`'s
     * `downloadableFile`, or a translation that overrides the owner's
     * `mainVisual`/`thumbnail` — see `HasMainVisualAndThumbnailTrait`), those
     * relations are nested in too. Otherwise every one of those still
     * lazy-loads per row/translation the moment it's actually read.
     */
    protected static function bootTranslatableModelTrait(): void
    {
        static::addGlobalScope('translations', function (Builder $builder) {
            $with = ['translations', 'currentTranslation', 'translations.language', 'currentTranslation.language'];

            $translationModel = new static()->getTranslationModel();

            if (is_subclass_of($translationModel, EagerLoadableModelInterface::class)) {
                foreach ($translationModel::getEagerLoads() as $relation) {
                    $with[] = "translations.$relation";
                    $with[] = "currentTranslation.$relation";
                }
            }

            $builder->with($with);
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(
            $this->getTranslationModel(),
            $this->getTranslationForeignKey()
        );
    }

    public function currentTranslation(): HasOne
    {
        $relation = $this->hasOne(
            $this->getTranslationModel(),
            $this->getTranslationForeignKey()
        );

        $ids = $this->resolveCurrentTranslationLanguageIds();

        if ($ids === []) {
            return $relation;
        }

        return $relation
            ->whereIn('language_id', $ids)
            ->orderByRaw($this->buildCaseOrderSql($ids));
    }

    /**
     * Language ids to try for the current translation, best match first:
     * the bound context's current/fallback languages, or (if unbound, or
     * neither is set) the Accept-Language header as a safe fallback.
     *
     * @return array<int>
     */
    private function resolveCurrentTranslationLanguageIds(): array
    {
        if (!app()->bound(LanguageContext::class)) {
            return $this->resolveLanguageIdsFromHeader();
        }

        $context = app(LanguageContext::class);

        $ids = array_values(array_filter([
            $context->current()?->id,
            $context->fallback()?->id,
        ]));

        return $ids !== [] ? $ids : $this->resolveLanguageIdsFromHeader();
    }

    /**
     * Try to resolve preferred language ids from the current HTTP request
     * Accept-Language header. Returns ordered language ids (best first).
     *
     * @return array<int>
     */
    protected function resolveLanguageIdsFromHeader(): array
    {
        $header = $this->safeAcceptLanguageHeader();

        if (!$header) {
            return [];
        }

        $requestedLocales = collect(explode(',', $header))
            ->map(fn ($locale) => trim(explode(';', $locale)[0]))
            ->map(fn ($locale) => strtolower(explode('-', $locale)[0]))
            ->filter()
            ->values();

        if ($requestedLocales->isEmpty()) {
            return [];
        }

        return $this->mapLocalesToLanguageIds($requestedLocales);
    }

    private function safeAcceptLanguageHeader(): ?string
    {
        try {
            return request()->header('Accept-Language');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Map requested locales to existing language ids, preserving order.
     *
     * @param Collection<int, string> $requestedLocales
     * @return array<int>
     */
    private function mapLocalesToLanguageIds(Collection $requestedLocales): array
    {
        try {
            $languages = Language::query()
                ->whereIn('iso', $requestedLocales->all())
                ->get(['id', 'iso'])
                ->keyBy(fn ($l) => strtolower($l->iso));
        } catch (Throwable) {
            return [];
        }

        $ids = [];
        foreach ($requestedLocales as $iso) {
            $lang = $languages->get($iso);
            if ($lang) {
                $ids[] = (int) $lang->id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Build a CASE SQL to preserve given order of language ids.
     */
    protected function buildCaseOrderSql(array $ids): string
    {
        $cases = [];
        foreach ($ids as $index => $id) {
            $cases[] = 'WHEN language_id = ' . (int)$id . ' THEN ' . (int)$index;
        }
        $else = count($ids);
        return 'CASE ' . implode(' ', $cases) . ' ELSE ' . $else . ' END';
    }

    public function translation(
        null|int|Language $language = null,
        bool $fallback = true,
    ): ?Model {

        if ($language === null) {
            return $this->currentTranslation;
        }

        $languageId = match (true) {
            $language instanceof Language => $language->id,
            is_int($language)             => $language,
        };

        $translation = $this->translations()
            ->where('language_id', $languageId)
            ->first();

        if ($translation) {
            return $translation;
        }

        return $fallback ? $this->resolveFallbackTranslation() : null;
    }

    private function resolveFallbackTranslation(): ?Model
    {
        if (!app()->bound(LanguageContext::class)) {
            return null;
        }

        $fallbackId = app(LanguageContext::class)->fallback()?->id;

        if (!$fallbackId) {
            return null;
        }

        return $this->translations()
            ->where('language_id', $fallbackId)
            ->first();
    }

    public function getTranslatedAttribute(): ?Model
    {
        return $this->translation();
    }

    protected function getTranslationForeignKey(): string
    {
        return $this->getForeignKey();
    }

    protected function getTranslationModel(): string
    {
        if (property_exists($this, 'translationModel')) {
            return $this->translationModel;
        }

        return static::class . 'Translation';
    }

    public function syncTranslations(array $translations): void
    {
        foreach ($translations as $languageId => $fields) {
            $this->translations()->updateOrCreate(
                ['language_id' => $languageId],
                $fields
            );
        }
    }
}
