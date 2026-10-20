<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Roles;

/**
 * The immutable audit trail is only visible to Direksi (master).
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isMaster($user);
    }

    public function view(User $user): bool
    {
        return $this->isMaster($user);
    }

    public function export(User $user): bool
    {
        return $this->isMaster($user);
    }

    private function isMaster(User $user): bool
    {
        return $user->hasRole(Roles::MASTER);
    }
}
