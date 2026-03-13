<?php

namespace App\Observers;

use App\Models\Table;
use Illuminate\Support\Str;

class TableObserver
{
    public function creating(Table $table): void
    {
        if (empty($table->uuid)) {
            $table->uuid = (string) Str::uuid();
        }
    }
}
