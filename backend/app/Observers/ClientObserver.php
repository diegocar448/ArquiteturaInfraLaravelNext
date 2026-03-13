<?php

namespace App\Observers;

use App\Models\Client;
use Illuminate\Support\Str;

class ClientObserver
{
    public function creating(Client $client): void
    {
        if (empty($client->uuid)) {
            $client->uuid = (string) Str::uuid();
        }
    }
}
