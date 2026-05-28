<?php

namespace Modules\Admissions\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Admissions\Models\Application;

class ApplicationNumberGenerator
{
    public function next(Application $application): array
    {
        return DB::transaction(function () use ($application) {
            $type = $application->program?->type ?? 'UG';
            $year = optional($application->session)->commencement_date
                ? Carbon::parse($application->session->commencement_date)->year
                : Carbon::now()->year;

            $college = config('admissions.college_code', 'SVNC');

            $maxSerial = Application::where('academic_session_id', $application->academic_session_id)
                ->whereHas('program', fn ($q) => $q->where('type', $type))
                ->lockForUpdate()
                ->max('serial') ?? 0;

            $serial = $maxSerial + 1;
            $number = sprintf('%s/%s/%d/%06d', $college, $type, $year, $serial);

            return [$serial, $number];
        });
    }
}
