<?php

namespace Modules\Audit\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Users\Models\DpdpConsent;

class DpdpConsentsController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->validate([
            'scope' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = DpdpConsent::query()
            ->with('user:id,name,email')
            ->orderByDesc('id');

        if (! empty($filter['scope'])) {
            $query->where('scope', $filter['scope']);
        }
        if (! empty($filter['email'])) {
            $email = $filter['email'];
            $query->whereHas('user', fn ($q) => $q->where('email', 'like', "%{$email}%"));
        }
        if (! empty($filter['from'])) {
            $query->whereDate('accepted_at', '>=', $filter['from']);
        }
        if (! empty($filter['to'])) {
            $query->whereDate('accepted_at', '<=', $filter['to']);
        }

        return Inertia::render('Admin/DpdpConsents', [
            'consents' => $query->paginate(50)->withQueryString(),
            'filter' => $filter,
            'scopes' => [
                DpdpConsent::SCOPE_REGISTRATION,
                DpdpConsent::SCOPE_PROFILE_LOCK,
                DpdpConsent::SCOPE_PAYMENT,
                DpdpConsent::SCOPE_DOCUMENT_UPLOAD,
                DpdpConsent::SCOPE_DIGILOCKER,
            ],
        ]);
    }
}
