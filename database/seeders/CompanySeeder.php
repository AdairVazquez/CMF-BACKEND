<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\CompanyModule;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        // Empresa 1: Hospital Central
        $hospital = Company::firstOrCreate(
            ['email' => 'admin@hospitalcentral.com'],
            [
                'name' => 'Hospital Central',
                'legal_name' => 'Hospital Central S.A. de C.V.',
                'tax_id' => 'HCE980123ABC',
                'phone' => '+52 55 1234 5678',
                'address' => 'Av. Reforma 123, CDMX',
                'plan' => 'premium',
                'status' => CompanyStatus::ACTIVO,
                'timezone' => 'America/Mexico_City',
                'modules' => ['attendance', 'reports', 'leave_requests', 'devices', 'geolocation'],
                'trial_ends_at' => null,
                'subscription_ends_at' => now()->addYear(),
            ]
        );

        // Módulos activos para Hospital Central
        $hospitalModules = [
            ['module_name' => 'attendance', 'is_active' => true, 'activated_at' => now()],
            ['module_name' => 'reports', 'is_active' => true, 'activated_at' => now()],
            ['module_name' => 'leave_requests', 'is_active' => true, 'activated_at' => now()],
            ['module_name' => 'devices', 'is_active' => true, 'activated_at' => now()],
            ['module_name' => 'geolocation', 'is_active' => true, 'activated_at' => now()],
        ];

        foreach ($hospitalModules as $moduleData) {
            CompanyModule::firstOrCreate(
                [
                    'company_id' => $hospital->id,
                    'module_name' => $moduleData['module_name'],
                ],
                $moduleData
            );
        }

        // Empresa 2: Empresa Demo
        $demo = Company::firstOrCreate(
            ['email' => 'admin@empresademo.com'],
            [
                'name' => 'Empresa Demo',
                'legal_name' => 'Empresa Demo S.A. de C.V.',
                'tax_id' => 'EDM950615XYZ',
                'phone' => '+52 33 9876 5432',
                'address' => 'Calle Industria 456, Guadalajara',
                'plan' => 'basic',
                'status' => CompanyStatus::ACTIVO,
                'timezone' => 'America/Mexico_City',
                'modules' => ['attendance', 'reports', 'devices'],
                'trial_ends_at' => null,
                'subscription_ends_at' => now()->addMonths(6),
            ]
        );

        // Módulos activos para Empresa Demo
        $demoModules = [
            ['module_name' => 'attendance', 'is_active' => true, 'activated_at' => now()],
            ['module_name' => 'reports', 'is_active' => true, 'activated_at' => now()],
            ['module_name' => 'devices', 'is_active' => true, 'activated_at' => now()],
        ];

        foreach ($demoModules as $moduleData) {
            CompanyModule::firstOrCreate(
                [
                    'company_id' => $demo->id,
                    'module_name' => $moduleData['module_name'],
                ],
                $moduleData
            );
        }

        $this->command->info('✅ Empresas creadas exitosamente');
        $this->command->info('   - Hospital Central (ID: ' . $hospital->id . ') - Plan: Premium');
        $this->command->info('   - Empresa Demo (ID: ' . $demo->id . ') - Plan: Basic');
    }
}
