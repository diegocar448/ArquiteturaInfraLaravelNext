<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('url')->index(); // slug — index para buscas rapidas
            $table->text('description')->nullable();
            $table->timestamps();

            // Slug unico por tenant (dois tenants podem ter "Bebidas")
            $table->unique(['tenant_id', 'url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
