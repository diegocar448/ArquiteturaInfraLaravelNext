<?php

namespace App\Kafka\Consumers;

use Illuminate\Support\Facades\Log;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Facades\Kafka;

class RetryableHandler implements Handler
{
    public function __construct(
        private readonly Handler $innerHandler,
        private readonly int $maxRetries = 3,
    ) {}

    public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $attempts = 0;

        while ($attempts < $this->maxRetries) {
            try {
                ($this->innerHandler)($message, $consumer);

                return; // Sucesso, sai do loop
            } catch (\Exception $e) {
                $attempts++;
                $topic = $message->getTopicName();
                $headers = $message->getHeaders();
                $eventId = $headers['event_id'] ?? 'unknown';

                Log::channel('stderr')->warning('Kafka consumer retry', [
                    'topic' => $topic,
                    'event_id' => $eventId,
                    'attempt' => $attempts,
                    'max_retries' => $this->maxRetries,
                    'error' => $e->getMessage(),
                ]);

                if ($attempts >= $this->maxRetries) {
                    $this->sendToDlq($message, $e);

                    return;
                }

                // Backoff exponencial: 1s, 2s, 4s
                sleep(pow(2, $attempts - 1));
            }
        }
    }

    private function sendToDlq(ConsumerMessage $message, \Exception $error): void
    {
        $topic = $message->getTopicName();
        $dlqTopic = $topic.'.dlq';

        try {
            Kafka::publish()
                ->onTopic($dlqTopic)
                ->withBodyKey('original_message', $message->getBody())
                ->withHeaders(array_merge($message->getHeaders(), [
                    'dlq_reason' => $error->getMessage(),
                    'dlq_original_topic' => $topic,
                    'dlq_timestamp' => now()->toISOString(),
                ]))
                ->send();

            Log::channel('stderr')->error('Message sent to DLQ', [
                'original_topic' => $topic,
                'dlq_topic' => $dlqTopic,
                'error' => $error->getMessage(),
            ]);
        } catch (\Exception $e) {
            Log::channel('stderr')->critical('Failed to send message to DLQ', [
                'original_topic' => $topic,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
