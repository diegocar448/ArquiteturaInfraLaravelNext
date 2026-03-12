<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            ['name' => 'Joao Silva', 'email' => 'joao@email.com', 'password' => 'password'],
            ['name' => 'Maria Santos', 'email' => 'maria@email.com', 'password' => 'password'],
            ['name' => 'Pedro Oliveira', 'email' => 'pedro@email.com', 'password' => 'password'],
        ];

        foreach ($clients as $data) {
            Client::firstOrCreate(
                ['email' => $data['email']],
                $data,
            );
        }

        $this->command->info('Clientes criados: joao@email.com, maria@email.com, pedro@email.com');
    }
}