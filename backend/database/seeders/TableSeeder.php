<?php

namespace Database\Seeders;

use App\Models\Table;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('name', 'Restaurante Demo')->first();

        if (! $tenant) {
            $this->command->warn('Tenant "Restaurante Demo" nao encontrado. Rode TenantSeeder primeiro.');

            return;
        }

        $tables = [
            ['identify' => 'Mesa 01', 'description' => 'Area interna - 4 lugares'],
            ['identify' => 'Mesa 02', 'description' => 'Area interna - 4 lugares'],
            ['identify' => 'Mesa 03', 'description' => 'Area interna - 6 lugares'],
            ['identify' => 'Mesa 04', 'description' => 'Varanda - 4 lugares'],
            ['identify' => 'Mesa 05', 'description' => 'Varanda - 4 lugares'],
            ['identify' => 'VIP-01', 'description' => 'Sala reservada - 8 lugares'],
        ];

        foreach ($tables as $data) {
            Table::firstOrCreate(
                ['tenant_id' => $tenant->id, 'identify' => $data['identify']],
                array_merge($data, ['tenant_id' => $tenant->id]),
            );
        }

        $this->command->info("Mesas criadas para o tenant '{$tenant->name}'.");
    }
}
