<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->integer('late_tolerance_minutes')->default(10);
            $table->integer('early_departure_tolerance_minutes')->default(10);
            $table->boolean('allow_overtime')->default(false);
            $table->decimal('overtime_multiplier', 4, 2)->default(1.5); // 1.5x pago
            $table->integer('max_overtime_hours_per_day')->default(3);
            $table->boolean('require_checkout')->default(true);
            $table->boolean('auto_checkout_enabled')->default(false);
            $table->time('auto_checkout_time')->nullable();
            $table->boolean('apply_penalty_for_late')->default(false);
            $table->decimal('penalty_amount_per_minute', 8, 2)->default(0);
            $table->timestamps();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_rules');
    }
};
