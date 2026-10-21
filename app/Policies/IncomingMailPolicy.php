<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IncomingMail;
use App\Models\User;
use App\Support\IncomingMailStatus;
use App\Support\Roles;

class IncomingMailPolicy
{
    /**
     * Determine whether the user can view any incoming mails.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasDocumentAccess($user);
    }

    /**
     * Determine whether the user can view the incoming mail.
     */
    public function view(User $user, IncomingMail $incomingMail): bool
    {
        return $this->hasDocumentAccess($user);
    }

    /**
     * Determine whether the user can create incoming mails.
     */
    public function create(User $user): bool
    {
        return $this->hasDocumentAccess($user);
    }

    /**
     * Determine whether the user can update the incoming mail.
     *
     * Final documents are locked: no field, attachment, or workflow changes.
     */
    public function update(User $user, IncomingMail $incomingMail): bool
    {
        return $this->hasDocumentAccess($user) && ! $this->isFinal($incomingMail);
    }

    /**
     * Determine whether the user can force a final status through the manual
     * status override (bulk update). The standard return workflow remains
     * available to staff through the update ability.
     */
    public function finalize(User $user, IncomingMail $incomingMail): bool
    {
        return $user->hasRole(Roles::MASTER) && ! $this->isFinal($incomingMail);
    }

    /**
     * Determine whether the user can create a document that is already in a
     * final status.
     */
    public function finalizeAny(User $user): bool
    {
        return $user->hasRole(Roles::MASTER);
    }

    /**
     * Determine whether the user can delete the incoming mail.
     */
    public function delete(User $user, IncomingMail $incomingMail): bool
    {
        return $user->hasRole(Roles::MASTER);
    }

    private function hasDocumentAccess(User $user): bool
    {
        return $user->hasAnyRole([Roles::STAFF, Roles::MASTER]);
    }

    private function isFinal(IncomingMail $incomingMail): bool
    {
        // COMPLETED is a retired value that must stay locked for old records.
        // RETURN is intentionally not final: a returned document may still be
        // sent back for revision (RETURN -> REVISI).
        return strtoupper(trim((string) $incomingMail->status)) === 'COMPLETED';
    }
}
