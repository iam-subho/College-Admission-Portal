<?php

namespace Modules\Payments\Services;

use Modules\Academics\Models\ProgramApplicationFee;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;

class FeeResolver
{
    /**
     * Resolve the application fee for a given application.
     *
     * Resolution order:
     *   1. If session.payment_mode = one_time → session.application_fee
     *   2. Else (per_programme):
     *      a. program_application_fees row for (program, student.category)
     *      b. programs.application_fee
     *      c. config('payments.default_application_fee') in paise → rupees
     *
     * @return array{amount: float, source: string}
     *               source ∈ {'session','override','programme','default'}
     */
    public function applicationFeeFor(Application $application): array
    {
        $session = $application->session ?? $application->load('session')->session;

        if ($session?->payment_mode === AcademicSession::PAYMENT_MODE_ONE_TIME) {
            if ($session->application_fee !== null) {
                return ['amount' => (float) $session->application_fee, 'source' => 'session'];
            }

            return $this->defaultFee();
        }

        $categoryId = $application->student?->reservation_category_id;

        if ($categoryId) {
            $override = ProgramApplicationFee::query()
                ->where('program_id', $application->program_id)
                ->where('reservation_category_id', $categoryId)
                ->value('application_fee');

            if ($override !== null) {
                return ['amount' => (float) $override, 'source' => 'override'];
            }
        }

        $program = $application->program ?? $application->load('program')->program;
        if ($program?->application_fee !== null) {
            return ['amount' => (float) $program->application_fee, 'source' => 'programme'];
        }

        return $this->defaultFee();
    }

    /**
     * In one-time mode, check whether the student already paid the
     * application fee for this session via any prior application.
     * Returns the covering PaymentOrder or null.
     */
    public function findCoveringOrder(Application $application): ?\Modules\Payments\Models\PaymentOrder
    {
        $session = $application->session ?? $application->load('session')->session;
        if ($session?->payment_mode !== AcademicSession::PAYMENT_MODE_ONE_TIME) {
            return null;
        }

        return \Modules\Payments\Models\PaymentOrder::query()
            ->whereHas('application', fn ($q) => $q
                ->where('student_id', $application->student_id)
                ->where('academic_session_id', $application->academic_session_id)
            )
            ->where('status', \Modules\Payments\Models\PaymentOrder::STATUS_PAID)
            ->latest('id')
            ->first();
    }

    public function convenienceFee(float $base, ?string $rule): float
    {
        if (! $rule) {
            return 0.0;
        }

        [$kind, $value] = array_pad(explode(':', $rule, 2), 2, null);

        return match ($kind) {
            'flat' => (float) $value,
            'pct' => round($base * ((float) $value) / 100, 2),
            default => 0.0,
        };
    }

    public function gstOn(float $amount): float
    {
        return round($amount * ((float) config('payments.gst_percent', 18)) / 100, 2);
    }

    protected function defaultFee(): array
    {
        $paise = (int) config('payments.default_application_fee', 50000);

        return ['amount' => $paise / 100, 'source' => 'default'];
    }
}
