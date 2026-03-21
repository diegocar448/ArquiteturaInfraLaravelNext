<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            PermissionSeeder::class,
            ProfileSeeder::class,
            AdminUserSeeder::class,
            TenantSeeder::class,
            RoleSeeder::class,
            TableSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            ClientSeeder::class,
            OrderSeeder::class,
            EvaluationSeeder::class,
        ]);
    }
}
