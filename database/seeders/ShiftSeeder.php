<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Company::where('email', 'admin@hospitalcentral.com')->first();

        if (!$hospital) {
            $this->command->error('❌ Hospital Central no encontrado. Ejecuta CompanySeeder primero.');
            return;
        }

        // Crear turnos para Hospital Central
        $shifts = [
            [
                'name' => 'Turno Mañana',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'tolerance_minutes' => 10,
                'break_minutes' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Turno Tarde',
                'start_time' => '16:00:00',
                'end_time' => '00:00:00',
                'tolerance_minutes' => 10,
                'break_minutes' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Turno Nocturno',
                'start_time' => '00:00:00',
                'end_time' => '08:00:00',
                'tolerance_minutes' => 15,
                'break_minutes' => 60,
                'is_active' => true,
            ],
        ];

        $createdShifts = [];
        foreach ($shifts as $shiftData) {
            $shift = Shift::firstOrCreate(
                [
                    'company_id' => $hospital->id,
                    'name' => $shiftData['name'],
                ],
                array_merge(['company_id' => $hospital->id], $shiftData)
            );
            $createdShifts[] = $shift;
        }

        // Crear regla de asistencia para Hospital Central
        $hospital->attendanceRules()->firstOrCreate(
            ['company_id' => $hospital->id],
            [
                'late_tolerance_minutes' => 10,
                'early_departure_tolerance_minutes' => 10,
                'allow_overtime' => true,
                'overtime_multiplier' => 1.5,
                'max_overtime_hours_per_day' => 4,
                'require_checkout' => true,
                'auto_checkout_enabled' => false,
                'apply_penalty_for_late' => false,
            ]
        );

        $this->command->info('✅ Turnos creados para Hospital Central');
        $this->command->info('   - ' . count($createdShifts) . ' turnos creados');
        $this->command->info('   - Reglas de asistencia configuradas');
    }
}
