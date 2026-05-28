<?php

namespace Modules\Merit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Merit\Models\MeritList;

class MeritListPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public MeritList $meritList) {}
}
