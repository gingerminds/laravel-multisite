<?php

namespace Gingerminds\LaravelMultisite\Models\Site;

use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

trait SiteContextedModelTrait
{
    private const SITE_ID_COLUMN_SUFFIX = '.site_id';

    protected static function bootSiteContextedModelTrait(): void
    {
        static::addGlobalScope('site', function (Builder $builder) {
            static::applySiteScope($builder);
        });

        static::creating(function ($model) {
            static::assignSiteIdOnCreate($model);
        });
    }

    /**
     * Restricts the query to the current site (or sites with no site_id at all).
     */
    private static function applySiteScope(Builder $builder): void
    {
        $siteId = static::resolveContextSiteId();

        if (!$siteId) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $table = $builder->getModel()->getTable();

        $builder->where(function (Builder $q) use ($table, $siteId) {
            $q->where($table . self::SITE_ID_COLUMN_SUFFIX, $siteId)
                ->orWhereNull($table . self::SITE_ID_COLUMN_SUFFIX);
        });
    }

    /**
     * Resolves the site id from the current context, falling back to the
     * request (header, then host) and finally to any existing site.
     */
    private static function resolveContextSiteId(): int|string|null
    {
        $siteId = app(SiteContext::class)->site()?->id;

        if ($siteId) {
            return $siteId;
        }

        try {
            return static::resolveSiteIdFromRequest();
        } catch (Throwable) {
            return null;
        }
    }

    private static function resolveSiteIdFromRequest(): int|string|null
    {
        $siteId = request()->header('X-Site-Id');

        if ($siteId) {
            return $siteId;
        }

        $host = request()->getHost();
        $site = Site::where('url', 'LIKE', '%' . $host . '%')->first();

        return $site?->id ?? Site::value('id');
    }

    private static function assignSiteIdOnCreate($model): void
    {
        if (empty($model->site_id) && app(SiteContext::class)->has()) {
            $model->site_id = app(SiteContext::class)->site()?->id;
        }
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @param Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopeForSite(
        Builder $query,
        null|string|int $siteId = null
    ): Builder {

        $siteId ??= app(SiteContext::class)->id();

        $table = $this->getTable();

        return $query->where(function (Builder $q) use ($table, $siteId) {

            $q->where($table . self::SITE_ID_COLUMN_SUFFIX, $siteId)
                ->orWhereNull($table . self::SITE_ID_COLUMN_SUFFIX);
        });
    }
}
