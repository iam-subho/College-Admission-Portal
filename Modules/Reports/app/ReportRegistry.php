<?php

namespace Modules\Reports;

use Modules\Reports\Reports\AntiRaggingComplianceReport;
use Modules\Reports\Reports\ApplicationFunnelReport;
use Modules\Reports\Reports\BaseReport;
use Modules\Reports\Reports\CategoryGenderDomicileReport;
use Modules\Reports\Reports\DailyAdmissionReport;
use Modules\Reports\Reports\DocumentRejectionReport;
use Modules\Reports\Reports\FeeCollectionRegister;
use Modules\Reports\Reports\GatewayReconciliationReport;
use Modules\Reports\Reports\MeritCutoffHistoryReport;
use Modules\Reports\Reports\WithdrawalRefundRegister;
use Modules\Reports\Services\AisheExport;

/**
 * Single source of truth for what reports exist. Resolves a report by its
 * key (used in URLs and sidebar selection) and returns the report instance
 * pulled from the container so caching is injected.
 */
class ReportRegistry
{
    public const REPORTS = [
        // Operational
        DailyAdmissionReport::class,
        ApplicationFunnelReport::class,
        DocumentRejectionReport::class,
        // Financial
        FeeCollectionRegister::class,
        WithdrawalRefundRegister::class,
        GatewayReconciliationReport::class,
        // Compliance
        CategoryGenderDomicileReport::class,
        MeritCutoffHistoryReport::class,
        AntiRaggingComplianceReport::class,
        // Statutory (AISHE handled separately as it's a single static export)
    ];

    /** @return array<string, BaseReport> keyed by report key */
    public function all(): array
    {
        $out = [];
        foreach (self::REPORTS as $class) {
            /** @var BaseReport $r */
            $r = app($class);
            $out[$r->key()] = $r;
        }

        return $out;
    }

    public function find(string $key): ?BaseReport
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Return reports grouped by their `group()` for sidebar display.
     */
    public function grouped(): array
    {
        $groups = [];
        foreach ($this->all() as $r) {
            $groups[$r->group()] ??= [];
            $groups[$r->group()][] = [
                'key' => $r->key(),
                'title' => $r->title(),
            ];
        }

        return $groups;
    }
}
