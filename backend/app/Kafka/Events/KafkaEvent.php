<?php

namespace App\Kafka\Events;

use Illuminate\Support\Str;

abstract class KafkaEvent
{
    public readonly string $eventId;

    public readonly string $occurredAt;

    public function __construct()
    {
        $this->eventId = (string) Str::uuid();
        $this->occurredAt = now()->toISOString();
    }

    abstract public function topic(): string;

    abstract public function key(): string;

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_type' => class_basename($this),
            'occurred_at' => $this->occurredAt,
        ];
    }
}
