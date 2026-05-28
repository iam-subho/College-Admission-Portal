<?php

namespace Modules\Audit\Services;

use Illuminate\Http\Request;
use Modules\Users\Models\DpdpConsent;

/**
 * Records a DPDP (Digital Personal Data Protection Act) consent event.
 * Called from registration, profile-lock, payment-init, document-upload,
 * and digilocker-callback. Returns the persisted DpdpConsent row.
 */
class DpdpConsentRecorder
{
    public function record(
        string $scope,
        ?int $userId = null,
        ?Request $request = null,
        array $metadata = [],
        ?string $version = null,
    ): DpdpConsent {
        $request ??= request();
        $version ??= (string) config('dpdp.current_version', 'v1.0');

        return DpdpConsent::create([
            'user_id' => $userId ?? $request?->user()?->id,
            'scope' => $scope,
            'version' => $version,
            'ip' => $request?->ip(),
            'user_agent' => substr((string) $request?->userAgent(), 0, 500),
            'metadata' => $metadata ?: null,
            'accepted_at' => now(),
        ]);
    }
}
