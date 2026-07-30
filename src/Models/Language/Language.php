<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Models\Language;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Gingerminds\LaravelCore\Models\CacheableResourceInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Models\SortableModelInterface;
use Gingerminds\LaravelCore\Models\Trait\CacheableResourceTrait;
use Gingerminds\LaravelMultisite\Models\Site\Site;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @property string $iso
**/
#[ApiResource(
    operations: [],
)]
#[ApiProperty(
    identifier: true,
    property: 'id',
    serialize: new Groups([
        Language::GROUP_LIST,
        Language::GROUP_READ,
        Site::GROUP_LIST,
        Site::GROUP_READ,
    ])
)]
#[ApiProperty(
    property: 'iso',
    serialize: new Groups([
        Language::GROUP_LIST,
        Language::GROUP_READ,
        Site::GROUP_LIST,
        Site::GROUP_READ,
    ])
)]
#[ApiProperty(
    property: 'label',
    serialize: new Groups([
        Language::GROUP_LIST,
        Language::GROUP_READ,
        Site::GROUP_LIST,
        Site::GROUP_READ,
    ])
)]
class Language extends Model implements ResourceModelInterface, SortableModelInterface, CacheableResourceInterface
{
    use CacheableResourceTrait;

    public const string GROUP_LIST = 'languages:list';
    public const string GROUP_READ = 'languages:read';

    public static function getCacheKey(): string
    {
        return 'language';
    }

    /**
     * Barely changes — 24h instead of the default 1h
     * (config('cache.resource_ttl_seconds')).
     */
    public static function getCacheTtl(): string|int|null
    {
        return 86400;
    }

    /**
     * @return string[]
     */
    public function getFillable(): array
    {
        return [
            'iso',
            'label',
        ];
    }
}
