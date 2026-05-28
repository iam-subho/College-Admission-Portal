<?php

namespace Modules\Admissions\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Services\ApplicationNumberGenerator;
use Modules\Admissions\Services\EligibilityEngine;
use Modules\Payments\Services\FeeResolver;

class ApplicationSubmitAction
{
    public function __construct(
        protected EligibilityEngine $engine,
        protected ApplicationNumberGenerator $numberer,
        protected FeeResolver $feeResolver,
    ) {}

    /**
     * Submit the application. Always succeeds — eligibility verdict is
     * stored for admin review, NEVER blocks submission.
     *
     * Also resolves the initial payment_status:
     *   - one-time mode + prior paid order in this session → 'covered'
     *   - resolved fee = 0                                  → 'not_required'
     *   - otherwise                                         → 'pending'
     */
    public function execute(Application $application): Application
    {
        return DB::transaction(function () use ($application) {
            if ($application->status !== Application::STATUS_DRAFT) {
                return $application;
            }

            $verdict = $this->engine->run($application);

            if (! $application->application_number) {
                [$serial, $number] = $this->numberer->next($application);
                $application->forceFill([
                    'serial' => $serial,
                    'application_number' => $number,
                ]);
            }

            [$paymentStatus, $coveredOrderId] = $this->resolveInitialPayment($application);

            $application->forceFill([
                'status' => Application::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'status_changed_at' => now(),
                'eligibility_verdict' => $verdict['verdict'],
                'eligibility_reasons' => $verdict['reasons'],
                'payment_status' => $paymentStatus,
                'covered_by_payment_order_id' => $coveredOrderId,
            ])->save();

            $fresh = $application->fresh();
            event(new \Modules\Notifications\Events\ApplicationSubmittedEvent($fresh));

            return $fresh;
        });
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    protected function resolveInitialPayment(Application $application): array
    {
        $covering = $this->feeResolver->findCoveringOrder($application);
        if ($covering) {
            return [Application::PAYMENT_COVERED, $covering->id];
        }

        $fee = $this->feeResolver->applicationFeeFor($application);
        if (($fee['amount'] ?? 0) <= 0) {
            return [Application::PAYMENT_NOT_REQUIRED, null];
        }

        return [Application::PAYMENT_PENDING, null];
    }
}
