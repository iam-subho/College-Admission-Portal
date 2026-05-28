<?php

namespace Modules\Documents\Services\Digilocker\Drivers;

use App\Models\User;
use Modules\Documents\Models\DigilockerLink;
use Modules\Documents\Models\DocumentType;
use Modules\Documents\Services\Digilocker\DigilockerDriverContract;
use Modules\Documents\Services\Digilocker\DigilockerException;

class StubDigilockerDriver implements DigilockerDriverContract
{
    public function code(): string
    {
        return 'stub';
    }

    public function buildAuthorizeUrl(User $user, string $redirectUri, string $state): string
    {
        return $redirectUri.'?code=STUB_AUTH_CODE&state='.urlencode($state);
    }

    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        if ($code !== 'STUB_AUTH_CODE') {
            throw new DigilockerException('Invalid stub authorization code.');
        }

        return [
            'access_token' => 'stub_access_'.bin2hex(random_bytes(8)),
            'refresh_token' => 'stub_refresh_'.bin2hex(random_bytes(8)),
            'expires_in' => 3600,
            'digilocker_user_id' => 'STUB_USER_'.uniqid(),
        ];
    }

    public function fetchDocument(User $user, DocumentType $type): array
    {
        if (! $type->digilocker_doc_type) {
            throw new DigilockerException("Document type {$type->code} is not mapped to a DigiLocker URI.");
        }

        $link = DigilockerLink::where('user_id', $user->id)->first();
        if (! $link || ! $link->isActive()) {
            throw new DigilockerException('User has not linked DigiLocker.');
        }

        // Generate a tiny canned "PDF" — enough for tests to assert size + checksum behaviour.
        $bytes = "%PDF-1.4\n%STUB DigiLocker document for {$type->code} (user_id={$user->id})\n%%EOF";

        return [
            'mime' => 'application/pdf',
            'bytes' => $bytes,
            'issuer' => match (true) {
                str_contains($type->digilocker_doc_type, 'CBSE') => 'Central Board of Secondary Education',
                str_contains($type->digilocker_doc_type, 'AADHAAR') => 'Unique Identification Authority of India',
                default => 'Government of India',
            },
            'uri' => "digilocker://stub/{$type->digilocker_doc_type}/{$user->id}",
        ];
    }
}
