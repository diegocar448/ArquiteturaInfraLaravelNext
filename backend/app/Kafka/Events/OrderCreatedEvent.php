<?php

namespace App\Kafka\Events;

use App\Models\Order;

class OrderCreatedEvent extends KafkaEvent
{
    public function __construct(
        public readonly Order $order,
    ) {
        parent::__construct();
    }

    public function topic(): string
    {
        return 'orderly.orders.created';
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
                'status' => $this->order->status,
                'total' => (float) $this->order->total,
                'tenant_id' => $this->order->tenant_id,
                'client_id' => $this->order->client_id,
                'table_id' => $this->order->table_id,
                'products_count' => $this->order->products->count(),
                'created_at' => $this->order->created_at->toISOString(),
            ],
        ]);
    }
}