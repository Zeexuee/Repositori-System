<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MailDisposition;
use App\Models\User;
use App\Support\Roles;

class MailDispositionPolicy
{
    /**
     * Determine whether the user can view any mail dispositions.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasDocumentAccess($user);
    }

    /**
     * Determine whether the user can view the mail disposition.
     */
    public function view(User $user, MailDisposition $disposition): bool
    {
        return $this->hasDocumentAccess($user);
    }

    /**
     * Determine whether the user can create mail dispositions.
     */
    public function create(User $user): bool
    {
        return $this->hasDocumentAccess($user);
    }

    /**
     * Determine whether the user can delete the mail disposition.
     */
    public function delete(User $user, MailDisposition $disposition): bool
    {
        return $user->hasRole(Roles::MASTER);
    }

    private function hasDocumentAccess(User $user): bool
    {
        return $user->hasAnyRole([Roles::STAFF, Roles::MASTER]);
    }
}
