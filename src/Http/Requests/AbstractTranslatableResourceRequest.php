<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Http\Requests;

use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelMultisite\Http\Requests\Concerns\BuildsTranslationAttributesTrait;
use Gingerminds\LaravelMultisite\Http\Requests\Concerns\HandlesSlugUniquenessTrait;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

abstract class AbstractTranslatableResourceRequest extends FormRequest implements FormRequestInterface
{
    use BuildsTranslationAttributesTrait;
    use HandlesSlugUniquenessTrait;

    /**
     * @return array<string, string>
     */
    abstract protected function translationFieldLabels(): array;

    /**
     * @return list<string>
     */
    protected function optionalTextFields(): array
    {
        return [];
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

    /**
     * @return list<int|string>
     */
    protected function submittedLanguageIds(): array
    {
        return array_keys($this->input('translations', []));
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

    /**
     * @return array<string, mixed>
     */
    protected function translationFieldRules(int|string $langId): array
    {
        $rules = [];

        foreach ($this->input("translations.$langId", []) as $field => $value) {
            $fieldRules = $this->requiredOrNullableRule($langId, (string) $field);

            if ($field === 'slug' && ($uniqueRule = $this->uniqueSlugRule($langId)) instanceof Unique) {
                $fieldRules[] = $uniqueRule;
            }

            $rules["translations.$langId.$field"] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function baseAttributes(): array
    {
        return $this->translationAttributes($this->translationFieldLabels());
    }

    /**
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
