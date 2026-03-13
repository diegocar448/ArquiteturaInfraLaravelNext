<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('identify')->index(); // "ORD-000001"
            $table->foreignId('table_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('client_id')->nullable(); // FK sera adicionada na Fase 8
            $table->string('status')->default('open')->index();
            $table->decimal('total', 10, 2)->default(0);
            $table->text('comment')->nullable();
            $table->timestamps();

            // Identify unico por tenant
            $table->unique(['tenant_id', 'identify']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
