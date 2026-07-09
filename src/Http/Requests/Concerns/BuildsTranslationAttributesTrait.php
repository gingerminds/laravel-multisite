<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Http\Requests\Concerns;

use Gingerminds\LaravelMultisite\Services\Context\SiteContext;

trait BuildsTranslationAttributesTrait
{
    /**
     * @param  array<string, string>  $labels  field name => human label, used for every submitted language
     * @return array<string, string>
     */
    protected function translationAttributes(array $labels): array
    {
        $attributes = [];

        $languages = app(SiteContext::class)->site()->languages ?? collect();

        foreach ($this->input('translations', []) as $langId => $fields) {
            $language      = $languages->firstWhere('id', $langId);
            $languageLabel = $language->iso ?? $langId;

            foreach ($fields as $field => $value) {
                $fieldLabel                                = $labels[$field] ?? $field;
                $attributes["translations.$langId.$field"] = "$fieldLabel ($languageLabel)";
            }
        }

        return $attributes;
    }
}
