<?php

namespace App\Observers;

use App\Models\Order;
use Illuminate\Support\Str;

class OrderObserver
{
    public function creating(Order $order): void
    {
        if (empty($order->uuid)) {
            $order->uuid = (string) Str::uuid();
        }

        if (empty($order->identify)) {
            $order->identify = $this->generateIdentify($order);
        }
    }

    private function generateIdentify(Order $order): string
    {
        $lastOrder = Order::withoutGlobalScopes()
            ->where('tenant_id', $order->tenant_id)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastOrder ? ((int) Str::after($lastOrder->identify, 'ORD-')) + 1 : 1;

        return 'ORD-'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
