<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Models\Trait;

/**
 * Declares `syncTranslations()` (provided by `TranslatableModelTrait`), so
 * code working generically against a translatable model can call it on a
 * type narrower than the bare Eloquent `Model` without losing PHPStan
 * checking.
 */
interface SyncsTranslationsInterface
{
    /**
     * @param array<int|string, array<string, mixed>> $translations
     */
    public function syncTranslations(array $translations): void;
}
