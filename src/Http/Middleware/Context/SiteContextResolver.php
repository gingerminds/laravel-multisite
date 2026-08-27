<?php

namespace Gingerminds\LaravelMultisite\Http\Middleware\Context;

use Gingerminds\LaravelMultisite\Models\Site\Site;
use Gingerminds\LaravelMultisite\Resolver\ResourceResolver;
use Illuminate\Http\Request;

class SiteContextResolver
{
    public function resolve(Request $request): ?Site
    {
        $modelClass = ResourceResolver::model('site');
        $siteId     = $this->resolveSiteId($request);

        if ($siteId) {
            return $modelClass::find((int) $siteId);
        }

        return $modelClass::where('url', 'LIKE', '%' . $request->getHost() . '%')->first() ?? $modelClass::first();
    }

    private function resolveSiteId(Request $request): mixed
    {
        if ($request->hasSession() && ($siteId = $request->session()->get('admin_site_id'))) {
            return $siteId;
        }

        return $request->header('X-Site-Id');
    }
}
