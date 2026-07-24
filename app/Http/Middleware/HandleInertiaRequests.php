<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\SiteSetting;
use Modules\Students\Models\Student;
use Modules\Students\Services\ProfileCompletionService;
use Modules\Users\Services\OtpService;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    protected function profileStatus(Request $request): ?array
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole('student')) {
            return null;
        }
        $student = Student::firstOrCreate(['user_id' => $user->id]);

        return app(ProfileCompletionService::class)->status($student);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => SiteSetting::get('portal_name'),
            ],
            'auth' => fn () => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'mobile' => $request->user()->mobile,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getPermissionNames(),
                    'dashboard_route' => $request->user()->dashboardRoute(),
                ] : null,
            ],
            'site' => fn () => SiteSetting::resolved(),
            'active_session' => fn () => AcademicSession::where('is_active', true)
                ->first(['id', 'code', 'name']),
            'flash' => fn () => (array) $request->session()->get('flash', []),
            'csrf_token' => fn () => csrf_token(),
            'dpdp' => [
                'version' => config('dpdp.current_version'),
            ],
            // Surfaces the dev/staging master OTP only when NOT in production.
            // Used by the OTP verify pages to show a hint banner.
            'dev' => app()->environment('production') ? null : [
                'master_otp' => OtpService::DEV_MASTER_CODE,
                'env' => app()->environment(),
            ],
            'profile_status' => fn () => $this->profileStatus($request),
        ];
    }
}
