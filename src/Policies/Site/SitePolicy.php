<?php

namespace Gingerminds\LaravelMultisite\Policies\Site;

use Gingerminds\LaravelCore\Models\User\User;
use Gingerminds\LaravelMultisite\Models\Site\Site;

class SitePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(User $user, Site $site): bool
    {
        return $user->can('view sites');
    }

    public function create(User $user): bool
    {
        return $user->can('edit sites');
    }

    public function update(User $user, Site $site): bool
    {
        return $user->can('edit sites');
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->can('delete sites');
    }

    public function restore(User $user, Site $site): bool
    {
        return false;
    }

    public function forceDelete(User $user, Site $site): bool
    {
        return false;
    }
}
