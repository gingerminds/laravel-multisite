<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Cache;

use Gingerminds\LaravelCore\Cache\CacheContextResolverInterface;
use Gingerminds\LaravelMultisite\Services\Context\LanguageContext;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;

/**
 * Rebinds core's CacheContextResolverInterface (a no-op by default, since
 * core has no dependency on this package) to the real site/language axes,
 * so AbstractRepository's cache keys actually vary with what changes the
 * underlying query: the current site and the resolved current-language id.
 *
 * The site's fallback language is deliberately not included here: fallback
 * is a deterministic function of the site (its default language), and site
 * is already part of the context — two requests with the same site always
 * have the same fallback, so it can't introduce a discrepancy on its own.
 */
class SiteLanguageCacheContextResolver implements CacheContextResolverInterface
{
    public function __construct(
        private readonly SiteContext $siteContext,
        private readonly LanguageContext $languageContext,
    ) {
    }

    /**
     * @return array<string, int|string|null>
     */
    public function resolve(): array
    {
        return [
            'site' => $this->siteContext->site()?->id,
            'lang' => $this->languageContext->current()?->id,
        ];
    }
}
