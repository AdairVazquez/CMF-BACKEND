<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('module_name'); // leave_requests, advanced_reports, geolocation, etc.
            $table->boolean('is_active')->default(true);
            $table->date('activated_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->json('config')->nullable(); // Configuración específica del módulo
            $table->timestamps();

            $table->unique(['company_id', 'module_name']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_modules');
    }
};
