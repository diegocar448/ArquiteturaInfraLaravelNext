<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('name', 'Restaurante Demo')->first();

        if (!$tenant) {
            $this->command->warn('Tenant "Restaurante Demo" nao encontrado. Rode TenantSeeder primeiro.');
            return;
        }

        // Buscar categorias
        $pizzas = Category::where('tenant_id', $tenant->id)->where('name', 'Pizzas')->first();
        $hamburgueres = Category::where('tenant_id', $tenant->id)->where('name', 'Hambúrgueres')->first();
        $bebidas = Category::where('tenant_id', $tenant->id)->where('name', 'Bebidas')->first();
        $sobremesas = Category::where('tenant_id', $tenant->id)->where('name', 'Sobremesas')->first();
        $combos = Category::where('tenant_id', $tenant->id)->where('name', 'Combos')->first();

        $products = [
            [
                'data' => ['title' => 'Pizza Margherita', 'price' => 39.90, 'flag' => 'featured', 'description' => 'Molho de tomate, mussarela e manjericao'],
                'categories' => [$pizzas],
            ],
            [
                'data' => ['title' => 'Pizza Calabresa', 'price' => 42.90, 'flag' => 'active', 'description' => 'Calabresa fatiada com cebola'],
                'categories' => [$pizzas],
            ],
            [
                'data' => ['title' => 'X-Bacon Artesanal', 'price' => 32.90, 'flag' => 'featured', 'description' => 'Hamburguer 180g, bacon crocante, queijo cheddar'],
                'categories' => [$hamburgueres],
            ],
            [
                'data' => ['title' => 'X-Salada', 'price' => 27.90, 'flag' => 'active', 'description' => 'Hamburguer 150g, alface, tomate, queijo'],
                'categories' => [$hamburgueres],
            ],
            [
                'data' => ['title' => 'Coca-Cola 350ml', 'price' => 7.50, 'flag' => 'active', 'description' => 'Lata gelada'],
                'categories' => [$bebidas],
            ],
            [
                'data' => ['title' => 'Suco Natural Laranja', 'price' => 9.90, 'flag' => 'active', 'description' => 'Suco natural 300ml'],
                'categories' => [$bebidas],
            ],
            [
                'data' => ['title' => 'Petit Gateau', 'price' => 19.90, 'flag' => 'active', 'description' => 'Bolo de chocolate com sorvete de creme'],
                'categories' => [$sobremesas],
            ],
            [
                'data' => ['title' => 'Combo X-Bacon', 'price' => 44.90, 'flag' => 'featured', 'description' => 'X-Bacon + Coca-Cola + Batata frita'],
                'categories' => [$hamburgueres, $combos],
            ],
        ];

        foreach ($products as $item) {
            $product = Product::firstOrCreate(
                ['tenant_id' => $tenant->id, 'title' => $item['data']['title']],
                array_merge($item['data'], ['tenant_id' => $tenant->id]),
            );

            // Sync categorias (sem duplicar se rodar novamente)
            $categoryIds = collect($item['categories'])
                ->filter()
                ->pluck('id')
                ->toArray();

            $product->categories()->syncWithoutDetaching($categoryIds);
        }

        $this->command->info("Produtos criados para o tenant '{$tenant->name}'.");
    }
}