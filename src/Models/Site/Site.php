<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Models\Site;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Gingerminds\LaravelCore\Models\CacheableResourceInterface;
use Gingerminds\LaravelCore\Models\EagerLoadableModelInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Models\SortableModelInterface;
use Gingerminds\LaravelCore\Models\Trait\CacheableResourceTrait;
use Gingerminds\LaravelCore\Models\Trait\EagerLoadableModelTrait;
use Gingerminds\LaravelMultisite\ApiProvider\Site\SiteProvider;
use Gingerminds\LaravelMultisite\Models\Language\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @property string $google_drive_file_id
 */
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => [Site::GROUP_LIST]],
            provider: SiteProvider::class
        ),
    ],
)]
#[ApiProperty(
    identifier: true,
    property: 'id',
    serialize: new Groups([
        Site::GROUP_LIST,
        Site::GROUP_READ,
    ])
)]
#[ApiProperty(
    property: 'code',
    serialize: new Groups([
        Site::GROUP_EDIT,
        Site::GROUP_LIST,
        Site::GROUP_READ,
    ])
)]
#[ApiProperty(
    property: 'url',
    serialize: new Groups([
        Site::GROUP_EDIT,
        Site::GROUP_LIST,
        Site::GROUP_READ,
    ])
)]
#[ApiProperty(
    property: 'languages',
    serialize: new Groups([
        Site::GROUP_READ,
    ])
)]
#[ApiProperty(
    property: 'default_language',
    serialize: new Groups([
        Site::GROUP_LIST,
        Site::GROUP_READ,
    ])
)]
#[ApiProperty(
    property: 'languages',
    serialize: new Groups([
        Site::GROUP_LIST,
        Site::GROUP_READ,
    ])
)]
#[ApiProperty(
    property: 'frontUrls',
    serialize: new Groups([
        Site::GROUP_LIST,
        Site::GROUP_READ,
    ])
)]
class Site extends Model implements
    ResourceModelInterface,
    SortableModelInterface,
    EagerLoadableModelInterface,
    CacheableResourceInterface
{
    use CacheableResourceTrait;
    use EagerLoadableModelTrait;

    public const string GROUP_LIST = 'sites:list';
    public const string GROUP_READ = 'sites:read';
    public const string GROUP_EDIT = 'sites:edit';

    /**
     * `languages`/`default_language`/`frontUrls` are all serialized in
     * GROUP_LIST/READ — without this every row triggers extra queries on
     * every listing.
     *
     * @return array<int, string>
     */
    public static function getEagerLoads(): array
    {
        return ['languages', 'defaultLanguage', 'frontUrls'];
    }

    public static function getCacheKey(): string
    {
        return 'site';
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
            'code',
            'url',
            'google_drive_file_id',
            'google_service_account_credentials',
        ];
    }

    /**
     * Google service account credentials must never leak through a default
     * model serialization (toArray()/toJson()), on top of not being
     * declared as an #[ApiProperty] on this resource.
     *
     * @return string[]
     */
    public function getHidden(): array
    {
        return [
            'google_service_account_credentials',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            // Stored encrypted at rest; accessed as a plain decoded array.
            'google_service_account_credentials' => 'encrypted:array',
        ];
    }

    /**
     * @return BelongsToMany<Language, $this>
     */
    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'site_language')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Language, $this>
     */
    public function defaultLanguage(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'site_language')
            ->wherePivot('is_default', true);
    }

    /**
     * @return HasMany<SiteFrontUrl, $this>
     */
    public function frontUrls(): HasMany
    {
        return $this->hasMany(SiteFrontUrl::class);
    }
}
