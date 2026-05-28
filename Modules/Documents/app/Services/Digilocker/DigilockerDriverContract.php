<?php

namespace Modules\Documents\Services\Digilocker;

use App\Models\User;
use Modules\Documents\Models\DocumentType;

interface DigilockerDriverContract
{
    public function code(): string;

    public function buildAuthorizeUrl(User $user, string $redirectUri, string $state): string;

    public function exchangeCodeForToken(string $code, string $redirectUri): array;

    /**
     * Fetch the document file for $type from the user's linked locker.
     * Returns ['mime' => string, 'bytes' => string, 'issuer' => ?string, 'uri' => ?string]
     */
    public function fetchDocument(User $user, DocumentType $type): array;
}
