<?php

namespace App\Console\Commands;

use App\Kafka\Consumers\OrderEventsHandler;
use App\Kafka\Consumers\RetryableHandler;
use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;

class ConsumeOrderEvents extends Command
{
    protected $signature = 'kafka:consume-orders';

    protected $description = 'Consume order events from Kafka topics';

    public function handle(): int
    {
        $this->info('Starting Kafka consumer for order events...');
        $this->info('Topics: orderly.orders.created, orderly.orders.status-changed');
        $this->info('Group: orderly-orders-consumer');
        $this->info('Retry: 3 attempts with exponential backoff');
        $this->info('DLQ: {topic}.dlq');
        $this->info('Press Ctrl+C to stop.');
        $this->newLine();

        try {
            $handler = new RetryableHandler(
                innerHandler: new OrderEventsHandler,
                maxRetries: 3,
            );

            Kafka::consumer()
                ->subscribe([
                    'orderly.orders.created',
                    'orderly.orders.status-changed',
                ])
                ->withConsumerGroupId('orderly-orders-consumer')
                ->withHandler($handler)
                ->withAutoCommit()
                ->build()
                ->consume();
        } catch (\Exception $e) {
            $this->error("Consumer error: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}