<?php

namespace Gingerminds\LaravelMultisite\Repositories\Site;

use Gingerminds\LaravelCore\Http\Requests\FormRequestInterface;
use Gingerminds\LaravelCore\Models\ResourceModelInterface;
use Gingerminds\LaravelCore\Repositories\AbstractRepository;
use Gingerminds\LaravelCore\Repositories\RepositoryInterface;
use Gingerminds\LaravelMultisite\Models\Site\Site;
use InvalidArgumentException;

/**
 * @extends AbstractRepository<Site>
 * @implements RepositoryInterface<Site>
 */
class SiteRepository extends AbstractRepository implements RepositoryInterface
{
    public function getModelClass(): string
    {
        return Site::class;
    }

    public function update(
        ?FormRequestInterface $request,
        ResourceModelInterface $resourceModel
    ): ResourceModelInterface {
        if (!$resourceModel instanceof Site) {
            throw new InvalidArgumentException('ResourceModelInterface must be an instance of ' . Site::class);
        }

        if (!$request instanceof FormRequestInterface) {
            return $resourceModel;
        }

        $data = $request->all();

        // A blank credentials textarea means "leave the existing service
        // account secret untouched" (it's never redisplayed once saved),
        // not "erase it" — only overwrite when something was submitted.
        if (
            array_key_exists('google_service_account_credentials', $data)
            && $data['google_service_account_credentials'] === null
        ) {
            unset($data['google_service_account_credentials']);
        }

        $resourceModel->fill($data);
        $resourceModel->save();

        $this->syncLanguages($resourceModel, $request->all());
        $this->syncFrontUrls($resourceModel, $request->all());

        return $resourceModel;
    }

    /**
     * @param array<mixed> $data
     */
    private function syncLanguages(Site $site, array $data): void
    {
        if (!array_key_exists('languages', $data)) {
            return;
        }

        $languages = $data['languages'] ?? [];

        if (empty($languages)) {
            $site->languages()->detach();
            return;
        }

        $defaults = array_filter($languages, fn ($l) => (bool)($l['is_default'] ?? false));
        if (count($defaults) > 1) {
            throw new InvalidArgumentException('Only one language can be set as default.');
        }

        /**
         * @var array<int, array{
         *     id: int|string,
         *     is_default?: bool
         * }> $languages
         */
        $sync = collect($languages)
            ->mapWithKeys(
                fn (array $l): array => [
                    $l['id'] => [
                        'is_default' => (bool)($l['is_default'] ?? false),
                    ],
                ]
            )
            ->all();

        $site->languages()->sync($sync);
    }

    /**
     * @param array<mixed> $data
     */
    private function syncFrontUrls(Site $site, array $data): void
    {
        if (!array_key_exists('front_urls', $data)) {
            return;
        }

        /** @var array<int, string> $urls */
        $urls = array_values(array_unique(array_filter((array) $data['front_urls'])));

        $site->frontUrls()->whereNotIn('url', $urls)->delete();

        foreach ($urls as $url) {
            $site->frontUrls()->firstOrCreate(['url' => $url]);
        }
    }
}
