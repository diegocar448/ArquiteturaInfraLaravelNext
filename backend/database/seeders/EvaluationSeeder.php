<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderEvaluation;
use Illuminate\Database\Seeder;

class EvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $joao = Client::where('email', 'joao@email.com')->first();
        $maria = Client::where('email', 'maria@email.com')->first();

        if (! $joao || ! $maria) {
            $this->command->warn('Clientes nao encontrados. Rode ClientSeeder primeiro.');

            return;
        }

        // Vincular clientes aos pedidos entregues
        $order1 = Order::where('identify', 'ORD-000001')->first(); // delivered

        if (! $order1 || $order1->status !== 'delivered') {
            $this->command->warn('Pedido ORD-000001 nao encontrado ou nao esta entregue.');

            return;
        }

        // Vincular client_id ao pedido
        $order1->update(['client_id' => $joao->id]);

        // Avaliacao do Joao para o pedido 1
        OrderEvaluation::firstOrCreate(
            ['order_id' => $order1->id, 'client_id' => $joao->id],
            [
                'stars' => 5,
                'comment' => 'Pizza excelente! Entrega rapida.',
            ],
        );

        $this->command->info('Avaliacoes criadas com sucesso.');
    }
}
