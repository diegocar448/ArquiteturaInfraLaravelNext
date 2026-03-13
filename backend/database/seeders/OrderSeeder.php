<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\Table;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('name', 'Restaurante Demo')->first();

        if (! $tenant) {
            $this->command->warn('Tenant "Restaurante Demo" nao encontrado. Rode TenantSeeder primeiro.');

            return;
        }

        $mesa01 = Table::where('tenant_id', $tenant->id)->where('identify', 'Mesa 01')->first();
        $mesa04 = Table::where('tenant_id', $tenant->id)->where('identify', 'Mesa 04')->first();

        $margherita = Product::where('tenant_id', $tenant->id)->where('title', 'Pizza Margherita')->first();
        $xbacon = Product::where('tenant_id', $tenant->id)->where('title', 'X-Bacon Artesanal')->first();
        $coca = Product::where('tenant_id', $tenant->id)->where('title', 'Coca-Cola 350ml')->first();
        $suco = Product::where('tenant_id', $tenant->id)->where('title', 'Suco Natural Laranja')->first();

        if (! $margherita || ! $xbacon || ! $coca) {
            $this->command->warn('Produtos nao encontrados. Rode ProductSeeder primeiro.');

            return;
        }

        // Pedido 1: Mesa 01 - Pizza + Coca (delivered)
        $order1 = Order::firstOrCreate(
            ['tenant_id' => $tenant->id, 'identify' => 'ORD-000001'],
            [
                'tenant_id' => $tenant->id,
                'table_id' => $mesa01?->id,
                'status' => Order::STATUS_DELIVERED,
                'comment' => 'Sem azeitona na pizza',
            ],
        );
        $order1->products()->syncWithoutDetaching([
            $margherita->id => ['qty' => 1, 'price' => $margherita->price],
            $coca->id => ['qty' => 2, 'price' => $coca->price],
        ]);
        $order1->update(['total' => $order1->calculateTotal()]);

        // Pedido 2: Mesa 04 - X-Bacon + Suco (preparing)
        $order2 = Order::firstOrCreate(
            ['tenant_id' => $tenant->id, 'identify' => 'ORD-000002'],
            [
                'tenant_id' => $tenant->id,
                'table_id' => $mesa04?->id,
                'status' => Order::STATUS_PREPARING,
                'comment' => null,
            ],
        );
        $order2->products()->syncWithoutDetaching([
            $xbacon->id => ['qty' => 2, 'price' => $xbacon->price],
            $suco->id => ['qty' => 1, 'price' => $suco->price],
        ]);
        $order2->update(['total' => $order2->calculateTotal()]);

        // Pedido 3: Sem mesa - delivery (open)
        $order3 = Order::firstOrCreate(
            ['tenant_id' => $tenant->id, 'identify' => 'ORD-000003'],
            [
                'tenant_id' => $tenant->id,
                'table_id' => null,
                'status' => Order::STATUS_OPEN,
                'comment' => 'Pedido para retirada',
            ],
        );
        $order3->products()->syncWithoutDetaching([
            $margherita->id => ['qty' => 2, 'price' => $margherita->price],
        ]);
        $order3->update(['total' => $order3->calculateTotal()]);

        $this->command->info("Pedidos criados para o tenant '{$tenant->name}'.");
    }
}
