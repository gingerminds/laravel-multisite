<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Models\Trait;

trait HasTranslatedTitleAndSlugTrait
{
    public function getTitleAttribute(): ?string
    {
        return $this->currentTranslation?->title;
    }

    public function getSlugAttribute(): ?string
    {
        return $this->currentTranslation?->slug;
    }

    /**
     * @return array<string, string|null>
     */
    public function getSwitchLangAttribute(): array
    {
        $paths = [];

        foreach ($this->translations as $translation) {
            if ($translation->title === null || $translation->title === '') {
                continue;
            }

            $paths[$translation->language->iso] = $translation->slug;
        }

        return $paths;
    }
}
