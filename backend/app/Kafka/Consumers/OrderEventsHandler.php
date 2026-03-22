<?php

namespace App\Kafka\Consumers;

use Illuminate\Support\Facades\Log;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageConsumer;

class OrderEventsHandler implements Handler
{
    public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $body = $message->getBody();
        $headers = $message->getHeaders();
        $topic = $message->getTopicName();

        $eventType = $headers['event_type'] ?? 'unknown';
        $data = $body['data'] ?? [];

        Log::channel('stderr')->info('Kafka event consumed', [
            'topic' => $topic,
            'event_type' => $eventType,
            'event_id' => $data['event_id'] ?? null,
            'order_id' => $data['order']['id'] ?? null,
        ]);

        match ($eventType) {
            'OrderCreatedEvent' => $this->handleOrderCreated($data),
            'OrderStatusChangedEvent' => $this->handleOrderStatusChanged($data),
            default => Log::channel('stderr')->warning("Unknown event type: {$eventType}"),
        };
    }

    private function handleOrderCreated(array $data): void
    {
        $order = $data['order'] ?? [];

        Log::channel('stderr')->info('New order received', [
            'identify' => $order['identify'] ?? null,
            'total' => $order['total'] ?? 0,
            'products_count' => $order['products_count'] ?? 0,
        ]);

        // Aqui voce pode adicionar logica de negocio:
        // - Enviar notificacao push para a cozinha
        // - Atualizar dashboard em tempo real (via WebSocket)
        // - Enviar email de confirmacao ao cliente
    }

    private function handleOrderStatusChanged(array $data): void
    {
        $order = $data['order'] ?? [];

        Log::channel('stderr')->info('Order status changed', [
            'identify' => $order['identify'] ?? null,
            'from' => $order['previous_status'] ?? null,
            'to' => $order['new_status'] ?? null,
        ]);

        // Aqui voce pode adicionar logica de negocio:
        // - Notificar cliente que o pedido esta sendo preparado
        // - Atualizar metricas de tempo medio por status
        // - Disparar alerta se pedido ficou muito tempo em "accepted"
    }
}
