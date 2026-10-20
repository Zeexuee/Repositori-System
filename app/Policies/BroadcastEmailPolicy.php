<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BroadcastEmail;
use App\Models\User;
use App\Support\Roles;

/**
 * Broadcast email is a Direksi (master) communication channel.
 */
class BroadcastEmailPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isMaster($user);
    }

    public function view(User $user, BroadcastEmail $broadcast): bool
    {
        return $this->isMaster($user);
    }

    public function create(User $user): bool
    {
        return $this->isMaster($user);
    }

    private function isMaster(User $user): bool
    {
        return $user->hasRole(Roles::MASTER);
    }
}
