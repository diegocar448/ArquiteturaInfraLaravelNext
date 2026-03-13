<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('identify'); // "Mesa 01", "VIP-03"
            $table->text('description')->nullable();
            $table->timestamps();

            // Identify unico por tenant (dois tenants podem ter "Mesa 01")
            $table->unique(['tenant_id', 'identify']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
