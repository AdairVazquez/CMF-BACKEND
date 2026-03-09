<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // SuperAdmin, Director, Subdirector, JefeArea, RH, Operador, Empleado
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('hierarchy_level')->default(0); // Para ordenar roles
            $table->timestamps();

            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
