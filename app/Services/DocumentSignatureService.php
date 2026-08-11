<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class DocumentSignatureService
{
    /**
     * Sign a document using PSrE digital signature provider.
     *
     * @param string $filePath Path to the document in storage/S3
     * @param User $signer The user issuing the signature
     * @return bool Returns true on success, false on failure
     */
    public function signDocument(string $filePath, User $signer): bool
    {
        // Logica awal / placeholder untuk integrasi API PSrE
        return true;
    }
}
