<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Seeder;

class BranchAndDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Company::where('email', 'admin@hospitalcentral.com')->first();

        if (!$hospital) {
            $this->command->error('❌ Hospital Central no encontrado. Ejecuta CompanySeeder primero.');
            return;
        }

        // Crear sucursales para Hospital Central
        $branches = [
            ['name' => 'Edificio Principal', 'code' => 'EP', 'address' => 'Av. Reforma 123, CDMX', 'city' => 'Ciudad de México', 'state' => 'CDMX', 'postal_code' => '06600', 'phone' => '+52 55 1234 5678'],
            ['name' => 'Clínica Norte', 'code' => 'CN', 'address' => 'Av. Norte 456, CDMX', 'city' => 'Ciudad de México', 'state' => 'CDMX', 'postal_code' => '02100', 'phone' => '+52 55 2345 6789'],
            ['name' => 'Laboratorio Sur', 'code' => 'LS', 'address' => 'Calle Sur 789, CDMX', 'city' => 'Ciudad de México', 'state' => 'CDMX', 'postal_code' => '04500', 'phone' => '+52 55 3456 7890'],
        ];

        $createdBranches = [];
        foreach ($branches as $branchData) {
            $branch = Branch::firstOrCreate(
                [
                    'company_id' => $hospital->id,
                    'code' => $branchData['code'],
                ],
                array_merge(['company_id' => $hospital->id], $branchData)
            );
            $createdBranches[] = $branch;
        }

        // Crear departamentos para Hospital Central
        $departments = [
            ['name' => 'Urgencias', 'code' => 'URG', 'description' => 'Departamento de urgencias médicas', 'branch_id' => $createdBranches[0]->id],
            ['name' => 'Administración', 'code' => 'ADM', 'description' => 'Departamento administrativo', 'branch_id' => $createdBranches[0]->id],
            ['name' => 'Recursos Humanos', 'code' => 'RH', 'description' => 'Gestión de recursos humanos', 'branch_id' => $createdBranches[0]->id],
            ['name' => 'Enfermería', 'code' => 'ENF', 'description' => 'Personal de enfermería', 'branch_id' => $createdBranches[0]->id],
            ['name' => 'Médicos', 'code' => 'MED', 'description' => 'Personal médico', 'branch_id' => $createdBranches[0]->id],
            ['name' => 'Laboratorio Clínico', 'code' => 'LAB', 'description' => 'Análisis clínicos', 'branch_id' => $createdBranches[2]->id],
            ['name' => 'Consulta Externa', 'code' => 'CEX', 'description' => 'Consultas ambulatorias', 'branch_id' => $createdBranches[1]->id],
        ];

        $createdDepartments = [];
        foreach ($departments as $deptData) {
            $department = Department::firstOrCreate(
                [
                    'company_id' => $hospital->id,
                    'code' => $deptData['code'],
                ],
                array_merge(['company_id' => $hospital->id], $deptData)
            );
            $createdDepartments[] = $department;
        }

        $this->command->info('✅ Sucursales y departamentos creados para Hospital Central');
        $this->command->info('   - ' . count($createdBranches) . ' sucursales creadas');
        $this->command->info('   - ' . count($createdDepartments) . ' departamentos creados');
    }
}
