<?php

namespace App\Observers;

use App\Models\Tenant;
use Illuminate\Support\Str;

class TenantObserver
{
    public function creating(Tenant $tenant): void
    {
        if (empty($tenant->uuid)) {
            $tenant->uuid = (string) Str::uuid();
        }

        if (empty($tenant->url)) {
            $tenant->url = Str::slug($tenant->name);
        }
    }

    public function updating(Tenant $tenant): void
    {
        if ($tenant->isDirty('name') && ! $tenant->isDirty('url')) {
            $tenant->url = Str::slug($tenant->name);
        }
    }
}
