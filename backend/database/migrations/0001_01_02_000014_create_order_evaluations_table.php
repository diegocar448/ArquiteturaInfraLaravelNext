<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('stars'); // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();

            // Um cliente so pode avaliar um pedido uma vez
            $table->unique(['order_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_evaluations');
    }
};