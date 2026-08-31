<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OutgoingMail;
use App\Models\User;

class OutgoingMailPolicy
{
    /**
     * Determine whether the user can view any outgoing mails.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the outgoing mail.
     */
    public function view(User $user, OutgoingMail $outgoingMail): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create outgoing mails.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the outgoing mail.
     */
    public function update(User $user, OutgoingMail $outgoingMail): bool
    {
        return ! in_array($outgoingMail->status, ['APPROVED', 'SIGNED'], true);
    }

    /**
     * Determine whether the user can delete the outgoing mail.
     */
    public function delete(User $user, OutgoingMail $outgoingMail): bool
    {
        return true;
    }

    /**
     * Determine whether the user can execute digital signature.
     */
    public function sign(User $user, OutgoingMail $outgoingMail): bool
    {
        return $user->hasRole('Direksi') && $outgoingMail->status === 'APPROVED';
    }
}
