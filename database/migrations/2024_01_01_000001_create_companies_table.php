<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tax_id')->unique()->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('logo')->nullable();
            $table->string('plan')->default('basic'); // basic, premium, enterprise
            $table->enum('status', ['activo', 'suspendido', 'inactivo', 'prueba'])->default('prueba');
            $table->string('timezone')->default('America/Mexico_City');
            $table->json('modules')->nullable(); // Módulos premium activos
            $table->date('trial_ends_at')->nullable();
            $table->date('subscription_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('plan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
