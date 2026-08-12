<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MailDisposition;
use App\Models\User;

class MailDispositionPolicy
{
    /**
     * Determine whether the user can view any mail dispositions.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the mail disposition.
     */
    public function view(User $user, MailDisposition $disposition): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create mail dispositions.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Direksi', 'Kepala Divisi', 'Staf Sekretariat']);
    }

    /**
     * Determine whether the user can delete the mail disposition.
     */
    public function delete(User $user, MailDisposition $disposition): bool
    {
        return $user->hasRole('Super Admin');
    }
}
