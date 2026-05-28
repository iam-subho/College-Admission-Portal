<?php

namespace Modules\Payments\Services;

use Carbon\Carbon;
use Modules\Admissions\Models\Application;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Models\RefundPolicy;

/**
 * Computes the refundable amount for a withdrawal request.
 *
 * Slab matching: each policy slab has from_days / to_days (relative to
 * session_start). days_to_start = session_start - withdrawal_date.
 *   - positive  → before session start
 *   - 0         → on session start
 *   - negative  → after session start
 *
 * A slab matches when:
 *   (from_days is null OR days_to_start <= from_days)
 *   AND (to_days is null OR days_to_start > to_days)
 *
 * Slabs should be ordered most-favourable to least-favourable (UGC convention)
 * but the matching is independent of order.
 */
class RefundPolicyEngine
{
    /**
     * @return array{
     *   refund_amount: float,
     *   deduction_amount: float,
     *   slab_label: string,
     *   refund_pct: float,
     *   fee_paid: float,
     *   has_payment: bool,
     *   policy_id: int|null,
     *   notes: string|null
     * }
     */
    public function compute(Application $application, ?Carbon $withdrawalDate = null, string $feeType = RefundPolicy::FEE_TYPE_APPLICATION): array
    {
        $withdrawalDate ??= now();
        $session = $application->session ?? $application->load('session')->session;
        $sessionStart = $session?->commencement_date ? Carbon::parse($session->commencement_date) : null;

        $feePaid = $this->totalPaidForFeeType($application, $feeType);

        if ($feePaid <= 0) {
            return [
                'refund_amount' => 0.0,
                'deduction_amount' => 0.0,
                'slab_label' => 'no_payment',
                'refund_pct' => 0.0,
                'fee_paid' => 0.0,
                'has_payment' => false,
                'policy_id' => null,
                'notes' => 'No paid '.$feeType.' fee on this application.',
            ];
        }

        $policy = RefundPolicy::query()
            ->where('academic_session_id', $application->academic_session_id)
            ->where('fee_type', $feeType)
            ->where('is_active', true)
            ->first();

        if (! $policy) {
            return [
                'refund_amount' => 0.0,
                'deduction_amount' => $feePaid,
                'slab_label' => 'no_policy',
                'refund_pct' => 0.0,
                'fee_paid' => $feePaid,
                'has_payment' => true,
                'policy_id' => null,
                'notes' => 'No refund policy configured for this session. Default: non-refundable.',
            ];
        }

        $daysToStart = $sessionStart ? (int) $withdrawalDate->startOfDay()->diffInDays($sessionStart->startOfDay(), false) : 0;

        $slab = $this->matchSlab($policy->slabs ?? [], $daysToStart);
        if (! $slab) {
            return [
                'refund_amount' => 0.0,
                'deduction_amount' => $feePaid,
                'slab_label' => 'no_matching_slab',
                'refund_pct' => 0.0,
                'fee_paid' => $feePaid,
                'has_payment' => true,
                'policy_id' => $policy->id,
                'notes' => "No slab matched days_to_start={$daysToStart}.",
            ];
        }

        $refundPct = (float) $slab['refund_pct'];
        $rawRefund = round($feePaid * $refundPct / 100, 2);
        $rawDeduction = $feePaid - $rawRefund;

        // UGC norm on a 100% slab: deduct a processing fee up to the cap
        // (e.g. ₹1000). Refund = fee_paid − min(fee_paid, cap).
        if ($policy->deduction_cap !== null && $refundPct >= 100.0) {
            $rawDeduction = min($feePaid, (float) $policy->deduction_cap);
            $rawRefund = $feePaid - $rawDeduction;
        }

        return [
            'refund_amount' => round($rawRefund, 2),
            'deduction_amount' => round($rawDeduction, 2),
            'slab_label' => (string) ($slab['label'] ?? $this->autoSlabLabel($slab)),
            'refund_pct' => $refundPct,
            'fee_paid' => round($feePaid, 2),
            'has_payment' => true,
            'policy_id' => $policy->id,
            'notes' => null,
        ];
    }

    protected function totalPaidForFeeType(Application $application, string $feeType): float
    {
        $purpose = $feeType === RefundPolicy::FEE_TYPE_APPLICATION
            ? PaymentOrder::PURPOSE_APPLICATION_FEE
            : PaymentOrder::PURPOSE_ADMISSION_FEE;

        return (float) $application->paymentOrders()
            ->where('purpose', $purpose)
            ->where('status', PaymentOrder::STATUS_PAID)
            ->sum('total');
    }

    protected function matchSlab(array $slabs, int $daysToStart): ?array
    {
        foreach ($slabs as $slab) {
            $from = $slab['from_days'] ?? null;
            $to = $slab['to_days'] ?? null;

            $matchesFrom = $from === null || $daysToStart <= (int) $from;
            $matchesTo = $to === null || $daysToStart > (int) $to;

            if ($matchesFrom && $matchesTo) {
                return $slab;
            }
        }

        return null;
    }

    protected function autoSlabLabel(array $slab): string
    {
        $from = $slab['from_days'] ?? null;
        $to = $slab['to_days'] ?? null;
        $pct = $slab['refund_pct'] ?? 0;

        if ($from === null && $to !== null) {
            return "more than {$to} days before session start ({$pct}%)";
        }
        if ($from !== null && $to !== null) {
            return "{$to}-{$from} days before session start ({$pct}%)";
        }
        if ($to === null) {
            return "after session start ({$pct}%)";
        }

        return "slab ({$pct}%)";
    }
}
