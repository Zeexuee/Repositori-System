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
        return $user->hasAnyRole(['Super Admin', 'Direksi', 'Kepala Divisi', 'Staf Sekretariat']);
    }

    /**
     * Determine whether the user can view the outgoing mail.
     */
    public function view(User $user, OutgoingMail $outgoingMail): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Direksi', 'Kepala Divisi', 'Staf Sekretariat']);
    }

    /**
     * Determine whether the user can create outgoing mails.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Staf Sekretariat', 'Kepala Divisi', 'Direksi']);
    }

    /**
     * Determine whether the user can update the outgoing mail.
     */
    public function update(User $user, OutgoingMail $outgoingMail): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasAnyRole(['Staf Sekretariat', 'Kepala Divisi'])) {
            return !in_array($outgoingMail->status, ['APPROVED', 'SIGNED'], true);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the outgoing mail (Super Admin only).
     */
    public function delete(User $user, OutgoingMail $outgoingMail): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can execute digital signature.
     */
    public function sign(User $user, OutgoingMail $outgoingMail): bool
    {
        return $user->hasAnyRole(['Direksi', 'Super Admin']) && $outgoingMail->status === 'APPROVED';
    }
}
