<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pendiente', 'aprobado_jefe', 'aprobado_rh', 'rechazado'])->default('pendiente');
            $table->string('leave_type'); // Vacaciones, permiso, enfermedad, etc.
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days_requested');
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by_manager')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_by_manager_at')->nullable();
            $table->foreignId('approved_by_hr')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_by_hr_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'employee_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index(['employee_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
