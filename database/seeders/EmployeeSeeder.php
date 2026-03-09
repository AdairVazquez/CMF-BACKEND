<?php

namespace Database\Seeders;

use App\Enums\CardStatus;
use App\Enums\EmployeeStatus;
use App\Enums\EmployeeType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\NfcCard;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Company::where('email', 'admin@hospitalcentral.com')->first();

        if (!$hospital) {
            $this->command->error('❌ Hospital Central no encontrado. Ejecuta CompanySeeder primero.');
            return;
        }

        // Obtener sucursales, departamentos y turnos
        $branches = Branch::where('company_id', $hospital->id)->get();
        $departments = Department::where('company_id', $hospital->id)->get();
        $shifts = Shift::where('company_id', $hospital->id)->get();

        if ($branches->isEmpty() || $departments->isEmpty() || $shifts->isEmpty()) {
            $this->command->error('❌ Faltan datos base. Ejecuta BranchAndDepartmentSeeder y ShiftSeeder primero.');
            return;
        }

        // Empleados de prueba
        $employees = [
            // Empleados tipo BASE (5)
            [
                'first_name' => 'Juan',
                'last_name' => 'García López',
                'email' => 'juan.garcia@hospital.com',
                'phone' => '+52 55 5001 0001',
                'employee_type' => EmployeeType::BASE,
                'position' => 'Enfermero',
                'department' => 'Enfermería',
                'shift' => 'Turno Mañana',
                'card_uid' => 'AB12F893',
            ],
            [
                'first_name' => 'María',
                'last_name' => 'Rodríguez Sánchez',
                'email' => 'maria.rodriguez@hospital.com',
                'phone' => '+52 55 5002 0002',
                'employee_type' => EmployeeType::BASE,
                'position' => 'Auxiliar Administrativo',
                'department' => 'Administración',
                'shift' => 'Turno Mañana',
                'card_uid' => 'CD34E567',
            ],
            [
                'first_name' => 'Pedro',
                'last_name' => 'Martínez Hernández',
                'email' => 'pedro.martinez@hospital.com',
                'phone' => '+52 55 5003 0003',
                'employee_type' => EmployeeType::BASE,
                'position' => 'Técnico de Laboratorio',
                'department' => 'Laboratorio Clínico',
                'shift' => 'Turno Tarde',
                'card_uid' => 'EF56A123',
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'López Fernández',
                'email' => 'ana.lopez@hospital.com',
                'phone' => '+52 55 5004 0004',
                'employee_type' => EmployeeType::BASE,
                'position' => 'Recepcionista',
                'department' => 'Consulta Externa',
                'shift' => 'Turno Mañana',
                'card_uid' => '12AB34CD',
            ],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Jiménez Torres',
                'email' => 'carlos.jimenez@hospital.com',
                'phone' => '+52 55 5005 0005',
                'employee_type' => EmployeeType::BASE,
                'position' => 'Camillero',
                'department' => 'Urgencias',
                'shift' => 'Turno Nocturno',
                'card_uid' => '56EF78AB',
            ],
            
            // Empleados tipo CONFIANZA (5)
            [
                'first_name' => 'Laura',
                'last_name' => 'González Morales',
                'email' => 'laura.gonzalez@hospital.com',
                'phone' => '+52 55 5006 0006',
                'employee_type' => EmployeeType::CONFIANZA,
                'position' => 'Médico Especialista',
                'department' => 'Médicos',
                'shift' => 'Turno Mañana',
                'card_uid' => '9ABC12DE',
            ],
            [
                'first_name' => 'Roberto',
                'last_name' => 'Ramírez Castro',
                'email' => 'roberto.ramirez@hospital.com',
                'phone' => '+52 55 5007 0007',
                'employee_type' => EmployeeType::CONFIANZA,
                'position' => 'Jefe de Enfermería',
                'department' => 'Enfermería',
                'shift' => 'Turno Mañana',
                'card_uid' => 'F1234ABC',
            ],
            [
                'first_name' => 'Patricia',
                'last_name' => 'Díaz Vargas',
                'email' => 'patricia.diaz@hospital.com',
                'phone' => '+52 55 5008 0008',
                'employee_type' => EmployeeType::CONFIANZA,
                'position' => 'Coordinadora de RH',
                'department' => 'Recursos Humanos',
                'shift' => 'Turno Mañana',
                'card_uid' => 'DE5678F9',
            ],
            [
                'first_name' => 'Miguel',
                'last_name' => 'Hernández Silva',
                'email' => 'miguel.hernandez@hospital.com',
                'phone' => '+52 55 5009 0009',
                'employee_type' => EmployeeType::CONFIANZA,
                'position' => 'Director de Urgencias',
                'department' => 'Urgencias',
                'shift' => 'Turno Mañana',
                'card_uid' => 'A1B2C3D4',
            ],
            [
                'first_name' => 'Sandra',
                'last_name' => 'Torres Ruiz',
                'email' => 'sandra.torres@hospital.com',
                'phone' => '+52 55 5010 0010',
                'employee_type' => EmployeeType::CONFIANZA,
                'position' => 'Gerente Administrativo',
                'department' => 'Administración',
                'shift' => 'Turno Mañana',
                'card_uid' => 'E5F6A7B8',
            ],
        ];

        $createdEmployees = [];
        $employeeCode = 1000;

        foreach ($employees as $empData) {
            // Buscar departamento y turno
            $department = $departments->firstWhere('name', $empData['department']);
            $shift = $shifts->firstWhere('name', $empData['shift']);
            $branch = $branches->first();

            if (!$department || !$shift) {
                $this->command->warn("⚠️  Departamento o turno no encontrado para {$empData['first_name']}");
                continue;
            }

            $cardUid = $empData['card_uid'];
            unset($empData['card_uid'], $empData['department'], $empData['shift']);

            // Crear empleado
            $employee = Employee::firstOrCreate(
                [
                    'company_id' => $hospital->id,
                    'email' => $empData['email'],
                ],
                array_merge($empData, [
                    'company_id' => $hospital->id,
                    'employee_code' => 'EMP' . str_pad($employeeCode++, 4, '0', STR_PAD_LEFT),
                    'branch_id' => $branch->id,
                    'department_id' => $department->id,
                    'shift_id' => $shift->id,
                    'status' => EmployeeStatus::ACTIVO,
                    'hire_date' => now()->subMonths(rand(1, 24)),
                    'hierarchy_level' => $empData['employee_type'] === EmployeeType::CONFIANZA ? 5 : 1,
                ])
            );

            // Crear tarjeta NFC asignada
            NfcCard::firstOrCreate(
                [
                    'card_uid' => $cardUid,
                ],
                [
                    'company_id' => $hospital->id,
                    'employee_id' => $employee->id,
                    'status' => CardStatus::ACTIVA,
                    'issued_at' => now()->subMonths(rand(1, 6)),
                ]
            );

            $createdEmployees[] = $employee;
        }

        $this->command->info('✅ Empleados creados para Hospital Central');
        $this->command->info('   - ' . count($createdEmployees) . ' empleados creados');
        $this->command->info('   - 5 empleados tipo BASE (pueden solicitar ausencias)');
        $this->command->info('   - 5 empleados tipo CONFIANZA (no pueden solicitar ausencias)');
        $this->command->info('   - Cada empleado tiene su tarjeta NFC asignada');
    }
}
