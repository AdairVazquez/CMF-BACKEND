<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Mañana, Tarde, Noche, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('tolerance_minutes')->default(10); // Tolerancia entrada
            $table->integer('break_minutes')->default(0); // Tiempo de comida
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
