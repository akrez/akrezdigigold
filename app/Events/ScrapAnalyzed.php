<?php

namespace App\Events;

use App\Models\Scrap;
use Illuminate\Foundation\Events\Dispatchable;

class ScrapAnalyzed
{
    use Dispatchable;

    public function __construct(public Scrap $scrap) {}
}
