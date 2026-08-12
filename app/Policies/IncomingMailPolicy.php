<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IncomingMail;
use App\Models\User;

class IncomingMailPolicy
{
    /**
     * Determine whether the user can view any incoming mails.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Staf', 'Direksi']);
    }

    /**
     * Determine whether the user can view the incoming mail.
     */
    public function view(User $user, IncomingMail $incomingMail): bool
    {
        return $user->hasAnyRole(['Staf', 'Direksi']);
    }

    /**
     * Determine whether the user can create incoming mails.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Staf');
    }

    /**
     * Determine whether the user can update the incoming mail.
     */
    public function update(User $user, IncomingMail $incomingMail): bool
    {
        if ($user->hasRole('Staf')) {
            return $incomingMail->status !== 'COMPLETED';
        }

        return false;
    }

    /**
     * Determine whether the user can delete the incoming mail.
     */
    public function delete(User $user, IncomingMail $incomingMail): bool
    {
        return $user->hasRole('Staf');
    }
}
