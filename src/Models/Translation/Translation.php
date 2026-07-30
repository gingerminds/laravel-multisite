<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Models\Translation;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Gingerminds\LaravelMultisite\ApiProvider\Translation\TranslationProvider;
use Symfony\Component\Serializer\Attribute\Groups;

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
    property: 'locale',
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
     * @param array<string, string> $values key => value
     */
    public function __construct(
        public readonly string $locale,
        public readonly array $values,
    ) {
    }
}
