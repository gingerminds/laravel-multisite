<?php

namespace Gingerminds\LaravelMultisite\Http\Controllers\Site;

use Gingerminds\LaravelCore\Http\Controllers\AbstractController;
use Gingerminds\LaravelMultisite\Http\Requests\Site\SiteRequest;
use Gingerminds\LaravelMultisite\Models\Language\Language;
use Gingerminds\LaravelMultisite\Models\Site\Site;
use Gingerminds\LaravelMultisite\Repositories\Site\SiteRepository;
use Gingerminds\LaravelMultisite\Resolver\ResourceResolver;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SiteController extends AbstractController
{
    public const string LABEL_S = 'gingerminds-multisite::translation.sites.name_s';

    public function __construct(
        private readonly SiteRepository $repository,
    ) {
    }

    public function index(Request $request): Factory|View
    {
        $this->authorize('viewAny', ResourceResolver::model('site'));

        $items = $this->repository->get($request);

        /** @var view-string $view */
        $view = 'gingerminds-multisite::pages.sites.index';
        return view($view, [
            'resource' => ResourceResolver::model('site'),
            'items'    => $items,
        ]);
    }

    public function create(): View
    {
        $languages = Language::all();

        /** @var view-string $view */
        $view = 'gingerminds-multisite::pages.sites.create';
        return view($view, ['languages' => $languages]);
    }

    public function edit(Site $site): View
    {
        $languages = Language::all();

        /** @var view-string $view */
        $view = 'gingerminds-multisite::pages.sites.edit';
        return view($view, ['site' => $site, 'languages' => $languages]);
    }

    public function store(SiteRequest $request): RedirectResponse
    {
        $this->authorize('create', ResourceResolver::model('site'));

        /** @var Site $site */
        $site = $this->repository->update($request, new Site());

        return $this->redirectAfterStore('gingerminds-multisite.sites', $site->id)
            ->with('success', __('gingerminds-core::translation.successfully_created', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($site->name ?? $site->id),
            ]));
    }

    public function update(SiteRequest $request, Site $site): RedirectResponse
    {
        $this->authorize('update', $site);

        $this->repository->update($request, $site);

        return $this->redirectAfterUpdate('gingerminds-multisite.sites', $site->id)
            ->with('success', __('gingerminds-core::translation.successfully_updated', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($site->name ?? $site->id),
            ]));
    }

    public function destroy(Site $site): RedirectResponse
    {
        $this->authorize('delete', $site);
        $site->delete();

        return redirect()->route('gingerminds-multisite.sites.index')
            ->with('success', __('gingerminds-core::translation.successfully_deleted', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($site->name ?? $site->id),
            ]));
    }
}
