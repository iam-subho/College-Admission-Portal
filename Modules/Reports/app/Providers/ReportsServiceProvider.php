<?php

namespace Modules\Reports\Providers;

use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\WithdrawalRequest;
use Modules\Documents\Models\DocumentVerification;
use Modules\Merit\Models\MeritList;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Models\Refund;
use Modules\Reports\Services\ReportCache;
use Modules\Seats\Models\SeatAllocation;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ReportsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Reports';

    protected string $nameLower = 'reports';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $cache = $this->app->make(ReportCache::class);

        // Closure-based listeners flush the relevant tags on saved/deleted.
        // Cheap when cache store has no tag support (ReportCache::flush noops).
        $bust = fn (array $tags) => fn ($model) => $cache->flush($tags);

        foreach (['saved', 'deleted'] as $hook) {
            Application::{$hook}($bust([ReportCache::TAG_APPLICATIONS]));
            PaymentOrder::{$hook}($bust([ReportCache::TAG_PAYMENTS]));
            Refund::{$hook}($bust([ReportCache::TAG_REFUNDS]));
            MeritList::{$hook}($bust([ReportCache::TAG_MERIT]));
            SeatAllocation::{$hook}($bust([ReportCache::TAG_SEATS]));
            DocumentVerification::{$hook}($bust([ReportCache::TAG_DOCUMENTS]));
            WithdrawalRequest::{$hook}($bust([ReportCache::TAG_WITHDRAWALS]));
        }
    }
}
