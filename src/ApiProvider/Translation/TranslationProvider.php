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
     * Returns every locale for the current site, each with all of its
     * translation key values ({locale, values: {key1, key2, ...}}). Without
     * an `Accept-Language` header the front receives the whole table in one
     * call; with one, only the requested locale is returned.
     *
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<int, Translation>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = request();

        $site = $this->resolveSite($request);
        if (!$site instanceof Site) {
            return [];
        }

        $translations = $this->translationService->getTranslationsForSite($site);

        $locale = $this->resolveRequestedLocale($request);
        if ($locale !== null) {
            $translations = array_intersect_key($translations, [$locale => true]);
        }

        $resources = [];
        foreach ($translations as $locale => $values) {
            $resources[] = new Translation((string) $locale, $values);
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

    /**
     * Extracts the primary locale from the `Accept-Language` header (e.g.
     * "fr-FR,en;q=0.8" => "fr"), null when the header is absent or empty.
     */
    private function resolveRequestedLocale(Request $request): ?string
    {
        $header = $request->header('Accept-Language');
        if (!$header) {
            return null;
        }

        $primary = strtolower(trim(explode(';', explode(',', $header)[0])[0]));
        $primary = explode('-', $primary)[0];

        return $primary !== '' ? $primary : null;
    }
}
