<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->string('device_code')->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->enum('status', ['activo', 'inactivo', 'mantenimiento'])->default('activo');
            $table->string('ip_address')->nullable();
            $table->string('mac_address')->nullable();
            $table->timestamp('last_ping_at')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
