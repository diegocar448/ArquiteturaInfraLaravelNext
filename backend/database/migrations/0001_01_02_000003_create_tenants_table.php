<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained();
            $table->uuid('uuid')->unique();
            $table->string('cnpj')->unique()->nullable();
            $table->string('name');
            $table->string('url')->unique(); // slug do tenant
            $table->string('email');
            $table->string('logo')->nullable();
            $table->boolean('active')->default(true);

            // Campos de assinatura (para integracao futura com gateway de pagamento)
            $table->string('subscription')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('subscription_id')->nullable();
            $table->boolean('subscription_active')->default(false);
            $table->boolean('subscription_suspended')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};