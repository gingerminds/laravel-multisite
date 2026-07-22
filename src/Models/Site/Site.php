<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Models\Site;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Models\SortableModelInterface;
use Gingerminds\LaravelMultisite\ApiProvider\Site\SiteProvider;
use Gingerminds\LaravelMultisite\Models\Language\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
class Site extends Model implements ResourceModelInterface, SortableModelInterface
{
    public const string GROUP_LIST = 'sites:list';
    public const string GROUP_READ = 'sites:read';
    public const string GROUP_EDIT = 'sites:edit';

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
}
