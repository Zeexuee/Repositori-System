<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OutgoingMail;
use App\Models\User;
use App\Support\OutgoingMailStatus;
use App\Support\Roles;

class OutgoingMailPolicy
{
    /**
     * Determine whether the user can view any outgoing mails.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasDocumentAccess($user);
    }

    /**
     * Determine whether the user can view the outgoing mail.
     */
    public function view(User $user, OutgoingMail $outgoingMail): bool
    {
        return $this->hasDocumentAccess($user);
    }

    /**
     * Determine whether the user can create outgoing mails.
     */
    public function create(User $user): bool
    {
        return $this->hasDocumentAccess($user);
    }

    /**
     * Determine whether the user can update the outgoing mail.
     *
     * Final documents are locked: no field, attachment, or workflow changes.
     */
    public function update(User $user, OutgoingMail $outgoingMail): bool
    {
        return $this->hasDocumentAccess($user) && ! $this->isFinal($outgoingMail);
    }

    /**
     * Determine whether the user can delete the outgoing mail.
     */
    public function delete(User $user, OutgoingMail $outgoingMail): bool
    {
        return $user->hasRole(Roles::MASTER);
    }

    /**
     * Determine whether the user can execute digital signature.
     */
    public function sign(User $user, OutgoingMail $outgoingMail): bool
    {
        return $user->hasRole(Roles::MASTER) && $outgoingMail->status === 'APPROVED';
    }

    private function hasDocumentAccess(User $user): bool
    {
        return $user->hasAnyRole([Roles::STAFF, Roles::MASTER]);
    }

    private function isFinal(OutgoingMail $outgoingMail): bool
    {
        // APPROVED and SIGNED are legacy aliases that resolve to RECEIVE.
        return OutgoingMailStatus::canonical($outgoingMail->status) === OutgoingMailStatus::RECEIVE;
    }
}
