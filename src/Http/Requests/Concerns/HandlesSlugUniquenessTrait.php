<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

trait HandlesSlugUniquenessTrait
{
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
}
