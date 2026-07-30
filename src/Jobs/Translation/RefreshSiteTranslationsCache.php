<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Jobs\Translation;

use Gingerminds\LaravelMultisite\Models\Site\Site;
use Gingerminds\LaravelMultisite\Services\Translation\TranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Invalidates the current translations cache for a site and re-downloads
 * the xlsx from Google Drive to repopulate it, so the next front /translations
 * call hits a warm cache instead of hitting Google Drive live.
 */
class RefreshSiteTranslationsCache implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly Site $site,
    ) {
    }

    public function handle(TranslationService $translationService): void
    {
        $translationService->resetCacheForSite($this->site);
        $translationService->getTranslationsForSite($this->site);
    }
}
