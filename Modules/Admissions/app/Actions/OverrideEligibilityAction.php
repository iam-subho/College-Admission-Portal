<?php

namespace Modules\Admissions\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Admissions\Models\Application;

class OverrideEligibilityAction
{
    public function execute(Application $application, User $decidedBy, string $newVerdict, string $remark): Application
    {
        return DB::transaction(function () use ($application, $decidedBy, $newVerdict, $remark) {
            $application->forceFill([
                'eligibility_verdict' => $newVerdict,
                'eligibility_remark' => $remark,
                'eligibility_decided_by' => $decidedBy->id,
                'eligibility_decided_at' => now(),
            ])->save();

            activity('application')
                ->performedOn($application)
                ->causedBy($decidedBy)
                ->withProperties([
                    'verdict' => $newVerdict,
                    'remark' => $remark,
                ])
                ->log("Eligibility overridden to {$newVerdict}");

            return $application->fresh();
        });
    }
}
