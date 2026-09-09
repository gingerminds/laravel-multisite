<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Services\Context;

use Gingerminds\LaravelMultisite\Http\Middleware\Context\SiteContextResolver;
use Gingerminds\LaravelMultisite\Models\Site\Site;
use Illuminate\Http\Request;

class SiteContext
{
    private ?Site $site = null;

    public function __construct(
        private readonly SiteContextResolver $resolver,
        private readonly Request $request,
    ) {
    }

    public function site(): ?Site
    {
        if ($this->site instanceof Site) {
            return $this->site;
        }

        return $this->site = $this->resolver->resolve($this->request);
    }

    public function has(): bool
    {
        return $this->site() instanceof Site;
    }
}
