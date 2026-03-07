<?php

namespace App\Observers;

use App\Models\Plan;
use Illuminate\Support\Str;

class PlanObserver
{
    public function creating(Plan $plan): void
    {
        if (empty($plan->url)) {
            $plan->url = Str::slug($plan->name);
        }
    }

    public function updating(Plan $plan): void
    {
        if ($plan->isDirty('name') && !$plan->isDirty('url')) {
            $plan->url = Str::slug($plan->name);
        }
    }
}