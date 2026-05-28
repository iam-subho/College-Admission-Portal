<?php

namespace Modules\Payments\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Payments\Actions\ReconcileAction;
use Modules\Payments\Models\PaymentGateway;

class ReconcilePaymentsCommand extends Command
{
    protected $signature = 'payments:reconcile
        {--date= : Date to reconcile (YYYY-MM-DD, defaults to yesterday)}
        {--gateway= : Limit to a single gateway code}';

    protected $description = 'Pull captured payments from each active gateway and mark missed orders as paid';

    public function handle(ReconcileAction $action): int
    {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))
            : now()->subDay();

        $query = PaymentGateway::where('is_active', true);
        if ($code = $this->option('gateway')) {
            $query->where('code', $code);
        }

        foreach ($query->get() as $gateway) {
            $this->line("Reconciling {$gateway->display_name} for {$date->toDateString()}…");
            $result = $action->execute($gateway, $date);
            $this->info("  rows seen: {$result['rows_seen']}, orders paid: {$result['orders_paid']}");
        }

        return self::SUCCESS;
    }
}
