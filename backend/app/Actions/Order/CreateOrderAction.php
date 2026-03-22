<?php

namespace App\Actions\Order;

use App\DTOs\Order\CreateOrderDTO;
use App\Kafka\Events\OrderCreatedEvent;
use App\Kafka\Producers\KafkaProducer;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class CreateOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
        private readonly KafkaProducer $producer,
    ) {}

    public function execute(CreateOrderDTO $dto): Order
    {
        $order = DB::transaction(function () use ($dto) {
            // 1. Criar o pedido
            $order = $this->repository->create([
                'table_id' => $dto->tableId,
                'client_id' => $dto->clientId,
                'comment' => $dto->comment,
                'status' => Order::STATUS_OPEN,
            ]);

            // 2. Vincular produtos com qty e price snapshot
            $pivotData = [];
            foreach ($dto->products as $item) {
                $product = Product::findOrFail($item['product_id']);
                $pivotData[$product->id] = [
                    'qty' => $item['qty'],
                    'price' => $product->price, // snapshot do preco atual
                ];
            }
            $order->products()->attach($pivotData);

            // 3. Calcular e salvar total
            $order->load('products');
            $order->update(['total' => $order->calculateTotal()]);

            return $order->fresh(['products', 'table']);
        });

        // Publicar evento no Kafka
        $this->producer->publish(new OrderCreatedEvent($order));

        return $order;
    }
}
