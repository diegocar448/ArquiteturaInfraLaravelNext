<?php

namespace App\Kafka\Events;

use App\Models\Evaluation;

class EvaluationCreatedEvent extends KafkaEvent
{
    public function __construct(
        public readonly Evaluation $evaluation,
    ) {
        parent::__construct();
    }

    public function topic(): string
    {
        return 'orderly.evaluations.created';
    }

    public function key(): string
    {
        return (string) $this->evaluation->order_id;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'evaluation' => [
                'id' => $this->evaluation->id,
                'order_id' => $this->evaluation->order_id,
                'stars' => $this->evaluation->stars,
                'comment' => $this->evaluation->comment,
                'client_id' => $this->evaluation->client_id,
                'created_at' => $this->evaluation->created_at->toISOString(),
            ],
        ]);
    }
}