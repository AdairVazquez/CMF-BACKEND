<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('set null');
            $table->string('employee_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('employee_type', ['base', 'confianza'])->default('base');
            $table->enum('status', ['activo', 'inactivo', 'baja', 'suspendido'])->default('activo');
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('position')->nullable(); // Puesto
            $table->integer('hierarchy_level')->default(0); // Para control de visibilidad
            $table->json('metadata')->nullable(); // Datos adicionales flexibles
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'employee_type']);
            $table->index(['company_id', 'department_id']);
            $table->index(['company_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
