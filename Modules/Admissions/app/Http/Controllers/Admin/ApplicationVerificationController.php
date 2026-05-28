<?php

namespace Modules\Admissions\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Actions\OverrideEligibilityAction;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;

class ApplicationVerificationController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->validate([
            'view' => ['nullable', 'string', Rule::in(['ready', 'all', 'awaiting_payment', 'draft', 'verified', 'rejected'])],
            'verdict' => ['nullable', 'string'],
            'q' => ['nullable', 'string'],
        ]);

        // Default view: applications that are submitted AND paid (or covered/not-required)
        // — i.e. ready for verification. Admin can switch to 'all' or 'awaiting_payment'.
        $view = $filter['view'] ?? 'ready';

        $query = Application::query()
            ->with(['student.user:id,name,email,mobile', 'program:id,code,name,type', 'session:id,code'])
            ->orderByDesc('id');

        $paidStatuses = [
            Application::PAYMENT_PAID,
            Application::PAYMENT_COVERED,
            Application::PAYMENT_NOT_REQUIRED,
        ];

        match ($view) {
            'ready' => $query
                ->where('status', Application::STATUS_SUBMITTED)
                ->whereIn('payment_status', $paidStatuses),
            'awaiting_payment' => $query
                ->where('status', Application::STATUS_SUBMITTED)
                ->where('payment_status', Application::PAYMENT_PENDING),
            'draft' => $query->where('status', Application::STATUS_DRAFT),
            'verified' => $query->where('status', Application::STATUS_VERIFIED),
            'rejected' => $query->where('status', Application::STATUS_REJECTED),
            'all' => null,
            default => null,
        };

        if (! empty($filter['verdict'])) {
            $query->where('eligibility_verdict', $filter['verdict']);
        }
        if (! empty($filter['q'])) {
            $query->where(function ($q) use ($filter) {
                $q->where('application_number', 'like', "%{$filter['q']}%")
                    ->orWhereHas('student.user', fn ($u) => $u->where('email', 'like', "%{$filter['q']}%")->orWhere('name', 'like', "%{$filter['q']}%"));
            });
        }

        $counts = [
            'ready' => Application::where('status', Application::STATUS_SUBMITTED)->whereIn('payment_status', $paidStatuses)->count(),
            'awaiting_payment' => Application::where('status', Application::STATUS_SUBMITTED)->where('payment_status', Application::PAYMENT_PENDING)->count(),
            'draft' => Application::where('status', Application::STATUS_DRAFT)->count(),
            'verified' => Application::where('status', Application::STATUS_VERIFIED)->count(),
            'rejected' => Application::where('status', Application::STATUS_REJECTED)->count(),
            'all' => Application::count(),
        ];

        return Inertia::render('Admin/Applications', [
            'applications' => $query->paginate(25)->withQueryString(),
            'filters' => $filter + ['view' => $view],
            'counts' => $counts,
            'active_session' => AcademicSession::where('is_active', true)->first(['id', 'code']),
        ]);
    }

    public function show(Application $application): Response
    {
        $application->load([
            'student.user',
            'student.category:id,code,name',
            'student.academicRecords',
            'student.entranceExams',
            'program.department',
            'session',
            'eligibilityDecidedBy:id,name',
            'courseSelections.pool',
            'paymentOrders' => fn ($q) => $q->orderByDesc('id'),
            'paymentOrders.gateway:id,code,display_name,mode',
            'paymentOrders.transactions',
            'paymentOrders.refunds',
        ]);

        $documents = \Modules\Documents\Models\UploadedDocument::query()
            ->where('student_id', $application->student_id)
            ->with(['type:id,code,label,required_by_default', 'verifications.verifier:id,name'])
            ->orderBy('document_type_id')
            ->get();

        $auditEntries = \Spatie\Activitylog\Models\Activity::query()
            ->where('subject_type', Application::class)
            ->where('subject_id', $application->id)
            ->with('causer:id,name')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return Inertia::render('Admin/ApplicationDetail', [
            'application' => $application,
            'documents' => $documents,
            'audit' => $auditEntries,
        ]);
    }

    public function override(Request $request, Application $application, OverrideEligibilityAction $action): RedirectResponse
    {
        $data = $request->validate([
            'verdict' => ['required', Rule::in([Application::VERDICT_OVERRIDE_PASS, Application::VERDICT_OVERRIDE_FAIL])],
            'remark' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $action->execute($application, $request->user(), $data['verdict'], $data['remark']);

        event(new \Modules\Notifications\Events\EligibilityOverriddenEvent($application->fresh()));

        return back()->with('flash', ['success' => 'Eligibility verdict overridden.']);
    }
}
