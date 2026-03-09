<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('nfc_card_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['entrada', 'salida']);
            $table->timestamp('recorded_at'); // Timestamp del dispositivo
            $table->boolean('is_manual')->default(false); // Si fue registrado manualmente
            $table->foreignId('registered_by')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'employee_id', 'recorded_at']);
            $table->index(['company_id', 'device_id']);
            $table->index(['employee_id', 'type', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
