<?php

namespace App\Kafka\Events;

use App\Models\Order;

class OrderStatusChangedEvent extends KafkaEvent
{
    public function __construct(
        public readonly Order $order,
        public readonly string $previousStatus,
    ) {
        parent::__construct();
    }

    public function topic(): string
    {
        return 'orderly.orders.status-changed';
    }

    public function key(): string
    {
        return (string) $this->order->id;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'order' => [
                'id' => $this->order->id,
                'uuid' => $this->order->uuid,
                'identify' => $this->order->identify,
                'previous_status' => $this->previousStatus,
                'new_status' => $this->order->status,
                'tenant_id' => $this->order->tenant_id,
            ],
        ]);
    }
}