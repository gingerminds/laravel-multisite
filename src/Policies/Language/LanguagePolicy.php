<?php

namespace Gingerminds\LaravelMultisite\Policies\Language;

use Gingerminds\LaravelCore\Models\User\User;

class LanguagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view languages');
    }

    public function view(User $user): bool
    {
        return $user->can('view languages');
    }

    public function create(User $user): bool
    {
        return $user->can('edit languages');
    }

    public function update(User $user): bool
    {
        return $user->can('edit languages');
    }

    public function delete(User $user): bool
    {
        return $user->can('delete languages');
    }

    public function restore(): bool
    {
        return false;
    }

    public function forceDelete(): bool
    {
        return false;
    }
}
