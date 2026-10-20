<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Roles;

/**
 * User management is a Direksi (master) responsibility.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isMaster($user);
    }

    public function view(User $user, User $model): bool
    {
        return $this->isMaster($user);
    }

    public function create(User $user): bool
    {
        return $this->isMaster($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->isMaster($user);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->isMaster($user) && $user->isNot($model);
    }

    private function isMaster(User $user): bool
    {
        return $user->hasRole(Roles::MASTER);
    }
}
