<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Models\Translation;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Gingerminds\LaravelMultisite\ApiProvider\Translation\TranslationProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * A single front translation entry, read live from the spreadsheet linked to
 * the current site on Google Drive. Not backed by a database table: see
 * TranslationProvider / TranslationService for how it is resolved.
 *
 * One entry per translation key, holding every locale found in the file
 * (e.g. values => ['fr' => '...', 'en' => '...', 'de' => '...', 'it' => '...']).
 */
#[ApiResource(
    uriTemplate: '/translations',
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => [Translation::GROUP_READ]],
            provider: TranslationProvider::class,
        ),
    ],
)]
#[ApiProperty(
    identifier: true,
    property: 'key',
    serialize: new Groups([Translation::GROUP_READ])
)]
#[ApiProperty(
    property: 'values',
    serialize: new Groups([Translation::GROUP_READ])
)]
class Translation
{
    public const string GROUP_READ = 'translations:read';

    /**
     * @param array<string, string> $values locale => value
     */
    public function __construct(
        public readonly string $key,
        public readonly array $values,
    ) {
    }
}
