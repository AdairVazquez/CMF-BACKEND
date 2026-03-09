<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Ver empleados, editar asistencias, aprobar ausencias, etc.
            $table->string('slug')->unique();
            $table->string('module'); // employees, attendance, leaves, devices, reports, etc.
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('module');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
