<?php

use Modules\Admissions\Database\Seeders\ReservationCategorySeeder;
use Modules\Admissions\Models\ReservationCategory;
use Modules\Fees\Database\Seeders\FeeHeadSeeder;
use Modules\Fees\Models\FeeHead;

it('seeds all 12 reservation categories', function () {
    $this->seed(ReservationCategorySeeder::class);

    expect(ReservationCategory::count())->toBe(12);

    $codes = ReservationCategory::pluck('code')->all();
    expect($codes)->toContain('UR', 'OBC_NCL', 'SC', 'ST', 'EWS', 'PWD', 'KM', 'SGC', 'NCC', 'SPORTS', 'MINORITY', 'CW');
});

it('marks horizontal categories correctly', function () {
    $this->seed(ReservationCategorySeeder::class);

    $horizontal = ReservationCategory::where('is_horizontal', true)->pluck('code')->all();
    expect($horizontal)->toContain('PWD', 'KM', 'SGC', 'NCC', 'SPORTS', 'CW');

    $vertical = ReservationCategory::where('is_horizontal', false)->pluck('code')->all();
    expect($vertical)->toContain('UR', 'OBC_NCL', 'SC', 'ST', 'EWS', 'MINORITY');
});

it('seeds 7 fee heads with correct categories', function () {
    $this->seed(FeeHeadSeeder::class);

    expect(FeeHead::count())->toBe(7);
    expect(FeeHead::where('code', 'APPLICATION')->first()->category)->toBe(FeeHead::CAT_APPLICATION);
    expect(FeeHead::where('code', 'CAUTION')->first()->is_refundable)->toBeTrue();
    expect(FeeHead::where('code', 'TUITION')->first()->is_refundable)->toBeFalse();
});
