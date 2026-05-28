<?php

namespace Modules\Admissions\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Admissions\Models\AcademicSession;

class ActivateSessionAction
{
    public function execute(AcademicSession $session): AcademicSession
    {
        return DB::transaction(function () use ($session) {
            AcademicSession::query()
                ->where('id', '!=', $session->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'status' => AcademicSession::STATUS_ARCHIVED,
                ]);

            $session->forceFill([
                'is_active' => true,
                'status' => AcademicSession::STATUS_ACTIVE,
            ])->save();

            return $session->fresh();
        });
    }
}
