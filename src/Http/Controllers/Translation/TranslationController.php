<?php

namespace Gingerminds\LaravelMultisite\Http\Controllers\Translation;

use Gingerminds\LaravelMultisite\Jobs\Translation\RefreshSiteTranslationsCache;
use Gingerminds\LaravelMultisite\Models\Site\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TranslationController extends Controller
{
    /**
     * BO action: dispatches a background job that invalidates and
     * repopulates the translations cache for the selected site.
     */
    public function refresh(Request $request): RedirectResponse
    {
        abort_unless((bool) auth()->user()?->can('manage translations'), 403);

        $site = Site::find($request->input('site_id'));

        if (!$site instanceof Site) {
            return back()->withErrors([
                'site_id' => __('gingerminds-multisite::translation.translations.site_required'),
            ]);
        }

        RefreshSiteTranslationsCache::dispatch($site);

        return back()->with('success', __('gingerminds-multisite::translation.translations.refresh_dispatched'));
    }
}
