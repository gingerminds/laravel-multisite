<?php

namespace Gingerminds\LaravelMultisite\Http\Controllers\Language;

use Gingerminds\LaravelCore\Http\Controllers\AbstractController;
use Gingerminds\LaravelMultisite\Http\Requests\Language\LanguageRequest;
use Gingerminds\LaravelMultisite\Models\Language\Language;
use Gingerminds\LaravelMultisite\Repositories\Language\LanguageRepository;
use Gingerminds\LaravelMultisite\Resolver\ResourceResolver;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends AbstractController
{
    public const string LABEL_S = 'gingerminds-multisite::translation.languages.name_s';

    public function __construct(
        private readonly LanguageRepository $repository
    ) {
    }

    public function index(Request $request): Factory|View
    {
        $this->authorize('viewAny', ResourceResolver::model('language'));

        $items = $this->repository->get($request);

        /** @var view-string $view */
        $view = 'gingerminds-multisite::pages.languages.index';
        return view($view, [
            'resource' => ResourceResolver::model('language'),
            'items'    => $items,
        ]);
    }

    public function create(): View
    {
        /** @var view-string $view */
        $view = 'gingerminds-multisite::pages.languages.create';
        return view($view);
    }

    public function edit(Language $language): View
    {
        /** @var view-string $view */
        $view = 'gingerminds-multisite::pages.languages.edit';
        return view($view, ['language' => $language]);
    }

    public function store(LanguageRequest $request): RedirectResponse
    {
        $this->authorize('create', ResourceResolver::model('language'));

        /** @var Language $language */
        $language = $this->repository->update($request, new Language());

        return $this->redirectAfterStore('gingerminds-multisite.languages', $language->id)
            ->with('success', __('gingerminds-core::translation.successfully_created', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($language->label ?? $language->id),
            ]));
    }

    public function update(LanguageRequest $request, Language $language): RedirectResponse
    {
        $this->authorize('update', $language);

        $this->repository->update($request, $language);

        return $this->redirectAfterUpdate('gingerminds-multisite.languages', $language->id)
            ->with('success', __('gingerminds-core::translation.successfully_updated', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($language->label ?? $language->id),
            ]));
    }

    public function destroy(Language $language): RedirectResponse
    {
        $this->authorize('delete', $language);
        $language->delete();

        return redirect()->route('gingerminds-multisite.languages.index')
            ->with('success', __('gingerminds-core::translation.successfully_deleted', [
                'model' => __(self::LABEL_S)
                    . ' '
                    . ($language->label ?? $language->id),
            ]));
    }
}
