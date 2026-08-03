<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Models\Site;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @property string $url
 */
#[ApiResource(
    operations: [],
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
    property: 'url',
    serialize: new Groups([
        Site::GROUP_LIST,
        Site::GROUP_READ,
    ])
)]
class SiteFrontUrl extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'url',
    ];

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
