<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Services\Translation;

use Gingerminds\LaravelMultisite\Models\Site\Site;
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

        $cacheKey = sprintf('gingerminds-multisite.translations.site.%d', (int) $site->id);
        $ttl      = (int) config('gingerminds-multisite.translation.cache_ttl', 300);

        /** @var array<string, array<string, string>> $translations */
        $translations = $this->cache->remember($cacheKey, $ttl, fn (): array => $this->fetchFromDrive($site));

        return $translations;
    }

    /**
     * @return array<string, string> key => value for the given locale
     */
    public function getTranslationsForSiteAndLocale(Site $site, string $locale): array
    {
        return $this->getTranslationsForSite($site)[strtolower($locale)] ?? [];
    }

    /**
     * Same data as getTranslationsForSite() but pivoted to be keyed by
     * translation key, each holding every locale's value:
     * key => [locale => value].
     *
     * @return array<string, array<string, string>>
     */
    public function getTranslationsGroupedByKey(Site $site): array
    {
        $byLocale = $this->getTranslationsForSite($site);

        $byKey = [];
        foreach ($byLocale as $locale => $values) {
            foreach ($values as $key => $value) {
                $byKey[$key][$locale] = $value;
            }
        }

        return $byKey;
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
            // diagnose which site is misconfigured.
            Log::warning('Unable to fetch front translations from Google Drive.', [
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
