<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\ApiProvider\Translation;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gingerminds\LaravelMultisite\Models\Site\Site;
use Gingerminds\LaravelMultisite\Models\Translation\Translation;
use Gingerminds\LaravelMultisite\Services\Context\SiteContext;
use Gingerminds\LaravelMultisite\Services\Translation\TranslationService;
use Illuminate\Http\Request;

/**
 * @implements ProviderInterface<Translation>
 */
class TranslationProvider implements ProviderInterface
{
    public function __construct(
        private readonly TranslationService $translationService,
        private readonly SiteContext $siteContext,
    ) {
    }

    /**
     * Returns every translation key for the current site, each with all of
     * its locale values ({key, values: {fr, en, de, it}}). No per-locale
     * filtering: the front receives the whole table in one call.
     *
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<int, Translation>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $site = $this->resolveSite(request());
        if (!$site instanceof Site) {
            return [];
        }

        $resources = [];
        foreach ($this->translationService->getTranslationsGroupedByKey($site) as $key => $values) {
            $resources[] = new Translation((string) $key, $values);
        }

        return $resources;
    }

    /**
     * Same resolution the rest of the package uses for the site context
     * (X-Site-Id header / host / current admin session), with an explicit
     * `?site=` query override for cases where the caller already knows the
     * site id or code (e.g. tooling, previews).
     */
    private function resolveSite(Request $request): ?Site
    {
        $siteParam = $request->query('site');

        if (is_string($siteParam) && $siteParam !== '') {
            return Site::where('id', $siteParam)
                ->orWhere('code', $siteParam)
                ->first();
        }

        return $this->siteContext->site();
    }
}
