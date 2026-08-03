<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Http\Requests\Site;

use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SiteRequest extends FormRequest implements FormRequestInterface
{
    private const string RULE_NULLABLE_ARRAY = 'nullable|array';

    protected function prepareForValidation(): void
    {
        /** @var array<int, int|string|null> $rawLanguages */
        $rawLanguages = $this->input('languages', []);

        $selectedLanguages = collect($rawLanguages)
            ->filter(fn ($id): bool => $id !== null && $id !== '')
            ->map(fn ($id): int => (int) $id)
            ->values();

        $defaultLanguage = $this->input('default_language');

        $defaultLanguageId = ($defaultLanguage === null || $defaultLanguage === '')
            ? null
            : (int) $defaultLanguage;

        $languages = $selectedLanguages
            ->mapWithKeys(fn (int $id): array => [
                $id => [
                    'id'         => $id,
                    'is_default' => $defaultLanguageId !== null
                        && $defaultLanguageId === $id,
                ],
            ])
            ->all();

        $this->merge([
            'default_language' => $defaultLanguageId,
            'languages'        => $languages,
        ]);

        // The admin form exposes the service account credentials as a raw
        // JSON textarea, left blank on edit screens so the secret is never
        // redisplayed. Decode it to an array here so it lands on the
        // model's `encrypted:array` cast correctly; a blank value is
        // normalized to null, which SiteRepository::update() treats as
        // "leave the already-stored credentials untouched" rather than
        // erasing them. An invalid (non-JSON) value is left as the raw
        // string so the `array` rule below rejects it.
        $credentials = $this->input('google_service_account_credentials');
        if (is_string($credentials)) {
            $trimmed = trim($credentials);
            if ($trimmed === '') {
                $this->merge(['google_service_account_credentials' => null]);
            } else {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $this->merge(['google_service_account_credentials' => $decoded]);
                }
            }
        }

        // The admin form exposes front URLs as a single textarea, one URL
        // per line, rather than a dynamic list of inputs.
        $frontUrls = $this->input('front_urls');
        if (is_string($frontUrls)) {
            $this->merge([
                'front_urls' => collect(preg_split('/\r\n|\r|\n/', $frontUrls) ?: [])
                    ->map(fn ($url): string => trim((string) $url))
                    ->filter(fn (string $url): bool => $url !== '')
                    ->values()
                    ->all(),
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<int, array{id:int|string}> $languages */
            $languages = $this->input('languages', []);

            $selected = collect($languages)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $default = $this->input('default_language');

            if (
                $default    !== null
                && $default !== ''
                && !in_array((int) $default, $selected, true)
            ) {
                $validator->errors()->add(
                    'default_language',
                    __('validation.in')
                );
            }
        });
    }

    /** @return  string[] */
    public function rules(): array
    {
        return [
            'code'                 => 'required|string|max:255',
            'url'                  => 'required|url',
            'default_language'     => 'nullable|integer|exists:languages,id',
            'languages'            => self::RULE_NULLABLE_ARRAY,
            'google_drive_file_id' => 'nullable|string|max:255',
            // After prepareForValidation(), a valid payload has already been
            // decoded to an array; anything still a string means the JSON
            // pasted in the admin form was invalid.
            'google_service_account_credentials' => self::RULE_NULLABLE_ARRAY,
            'front_urls'                         => self::RULE_NULLABLE_ARRAY,
            'front_urls.*'                       => 'required|url|max:255',
        ];
    }
}
