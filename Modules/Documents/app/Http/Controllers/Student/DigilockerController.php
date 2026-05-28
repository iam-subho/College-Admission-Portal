<?php

namespace Modules\Documents\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Documents\Models\DigilockerLink;
use Modules\Documents\Services\Digilocker\DigilockerException;
use Modules\Documents\Services\Digilocker\DigilockerManager;

class DigilockerController extends Controller
{
    public function link(Request $request, DigilockerManager $manager): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('digilocker.state', $state);

        $url = $manager->driver()->buildAuthorizeUrl(
            $request->user(),
            url('/digilocker/callback'),
            $state,
        );

        return redirect($url);
    }

    public function callback(Request $request, DigilockerManager $manager): RedirectResponse
    {
        $expected = $request->session()->pull('digilocker.state');
        if (! $expected || $request->input('state') !== $expected) {
            return redirect()->route('student.uploads.index')->with('flash', [
                'error' => 'DigiLocker linking failed: state mismatch.',
            ]);
        }

        try {
            $token = $manager->driver()->exchangeCodeForToken(
                (string) $request->input('code'),
                url('/digilocker/callback'),
            );
        } catch (DigilockerException $e) {
            return redirect()->route('student.uploads.index')->with('flash', [
                'error' => 'DigiLocker linking failed: '.$e->getMessage(),
            ]);
        }

        $link = DigilockerLink::firstOrNew(['user_id' => $request->user()->id]);
        $link->digilocker_user_id = $token['digilocker_user_id'] ?? null;
        $link->access_token = $token['access_token'] ?? null;
        $link->refresh_token = $token['refresh_token'] ?? null;
        $link->linked_at = now();
        $link->revoked_at = null;
        $link->save();

        return redirect()->route('student.uploads.index')->with('flash', [
            'success' => 'DigiLocker linked successfully.',
        ]);
    }

    public function unlink(Request $request): RedirectResponse
    {
        DigilockerLink::where('user_id', $request->user()->id)
            ->update(['revoked_at' => now()]);

        return back()->with('flash', ['success' => 'DigiLocker unlinked.']);
    }
}
