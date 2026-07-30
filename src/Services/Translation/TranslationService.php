<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Services\Translation;

use Gingerminds\LaravelMultisite\Models\Site\Site;
use Gingerminds\LaravelMultisite\Resolver\ResourceResolver;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orchestrates the "front translations" feature: reads the per-site Google
 * Drive xlsx file live at call time, with a short cache to protect the
 * Google API quota. Nothing is ever persisted to disk beyond the lifetime
 * of a single request (see GoogleDriveTranslationClient).
 */
class TranslationService
{
    public function __construct(
        private readonly GoogleDriveTranslationClient $client,
        private readonly TranslationFileParser $parser,
        private readonly CacheRepository $cache,
    ) {
    }

    public function isEnabledForSite(Site $site): bool
    {
        return (bool) config('gingerminds-multisite.translation.enabled', true)
            && filled($site->google_drive_file_id)
            && !empty($site->google_service_account_credentials);
    }

    /**
     * @return array<string, array<string, string>> locale => [key => value]
     */
    public function getTranslationsForSite(Site $site): array
    {
        if (!$this->isEnabledForSite($site)) {
            return [];
        }

        $cacheKey = $this->cacheKeyForSite($site);
        $ttl      = ResourceResolver::model('translations')::getCacheTtl();
        $callback = fn (): array => $this->fetchFromDrive($site);

        // Cache::remember()/put() treat a string $ttl by casting it to an int
        // of seconds — passing the literal 'forever' through would cast to 0
        // and immediately forget() the entry, so 'forever' needs its own
        // dedicated call instead of being funneled through remember().
        /** @var array<string, array<string, string>> $translations */
        $translations = is_string($ttl)
            ? $this->cache->rememberForever($cacheKey, $callback)
            : $this->cache->remember($cacheKey, $ttl, $callback);

        return $translations;
    }

    public function resetCacheForSite(Site $site): void
    {
        if (!$this->isEnabledForSite($site)) {
            return;
        }

        $this->cache->forget($this->cacheKeyForSite($site));
    }

    /**
     * Deliberately not using CacheKeyBuilder/CacheContextResolverInterface
     * here: those resolve their context (site, language) from the current
     * HTTP request, which doesn't exist when this runs from a queued job.
     * The site id is the only scoping this cache key needs.
     */
    private function cacheKeyForSite(Site $site): string
    {
        $translationModel = ResourceResolver::model('translations');

        return $translationModel::getCacheKey() . '.site.' . $site->id;
    }

    /**
     * @return array<string, string> key => value for the given locale
     */
    public function getTranslationsForSiteAndLocale(Site $site, string $locale): array
    {
        return $this->getTranslationsForSite($site)[strtolower($locale)] ?? [];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function fetchFromDrive(Site $site): array
    {
        /** @var array<string, mixed> $credentials */
        $credentials = $site->google_service_account_credentials ?? [];
        $xlsxPath    = null;

        try {
            $xlsxPath = $this->client->downloadXlsx((string) $site->google_drive_file_id, $credentials);

            return $this->parser->parse($xlsxPath);
        } catch (Throwable $exception) {
            // Never log the credentials or the file content, only enough to
            // diagnose which site is misconfigured. Routed to its own
            // channel (see LaravelMultisiteServiceProvider::configureGoogleLogChannel())
            // so Google API noise doesn't drown out the app's default logs.
            $channel = (string) config('gingerminds-multisite.translation.google_log_channel', 'google');

            Log::channel($channel)->warning('Unable to fetch front translations from Google Drive.', [
                'site_id' => $site->id,
                'message' => $exception->getMessage(),
            ]);

            return [];
        } finally {
            if ($xlsxPath !== null && is_file($xlsxPath)) {
                @unlink($xlsxPath);
            }
        }
    }
}
