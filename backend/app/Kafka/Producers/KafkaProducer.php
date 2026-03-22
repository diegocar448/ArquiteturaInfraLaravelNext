<?php

namespace App\Kafka\Producers;

use App\Kafka\Events\KafkaEvent;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Facades\Kafka;

class KafkaProducer
{
    public function publish(KafkaEvent $event): void
    {
        try {
            Kafka::publish()
                ->onTopic($event->topic())
                ->withBodyKey('data', $event->toArray())
                ->withHeaders([
                    'event_type' => class_basename($event),
                    'event_id' => $event->eventId,
                    'occurred_at' => $event->occurredAt,
                    'source' => 'orderly-backend',
                ])
                ->withKafkaKey($event->key())
                ->send();

            Log::info('Kafka event published', [
                'topic' => $event->topic(),
                'event_type' => class_basename($event),
                'event_id' => $event->eventId,
                'key' => $event->key(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to publish Kafka event', [
                'topic' => $event->topic(),
                'event_type' => class_basename($event),
                'error' => $e->getMessage(),
            ]);

            // Nao lanca excecao — o evento e best-effort
            // Em producao, considere salvar em uma tabela "outbox" para retry
        }
    }
}
