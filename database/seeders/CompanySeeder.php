<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\CompanyModule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class CompanySeeder extends Seeder

{

    public function run(): void
    {
        $yesterday = Carbon::yesterday();
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
                'modules' => [Company::MODULE_ASISTENCIA, Company::MODULE_REPORTES, Company::MODULE_AUSENCIAS, Company::MODULE_DISPOSITIVOS],
                'trial_ends_at' => null,
                'subscription_ends_at' => $yesterday->copy()->subYear(),
            ]
        );

        // Módulos activos para Hospital Central
        $hospitalModules = [
            ['module_name' => Company::MODULE_ASISTENCIA, 'is_active' => true, 'activated_at' => now()],
            ['module_name' => Company::MODULE_REPORTES, 'is_active' => true, 'activated_at' => now()],
            ['module_name' => Company::MODULE_AUSENCIAS, 'is_active' => true, 'activated_at' => now()],
            ['module_name' => Company::MODULE_DISPOSITIVOS, 'is_active' => true, 'activated_at' => now()],
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
                'modules' => [Company::MODULE_ASISTENCIA, Company::MODULE_REPORTES, Company::MODULE_DISPOSITIVOS],
                'trial_ends_at' => null,
                'subscription_ends_at' => now()->addMonths(6),
            ]
        );

        // Módulos activos para Empresa Demo
        $demoModules = [
            ['module_name' => Company::MODULE_ASISTENCIA, 'is_active' => true, 'activated_at' => now()],
            ['module_name' => Company::MODULE_REPORTES, 'is_active' => true, 'activated_at' => now()],
            ['module_name' => Company::MODULE_DISPOSITIVOS, 'is_active' => true, 'activated_at' => now()],
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

        // Generar 50 empresas adicionales con datos random
        $faker = \Faker\Factory::create('es_ES'); // Usar español para datos locales
        $plans = ['basic', 'premium', 'enterprise'];
        $statuses = [CompanyStatus::ACTIVO, CompanyStatus::INACTIVO, CompanyStatus::SUSPENDIDO, CompanyStatus::PRUEBA];
        $modulesList = [Company::MODULE_ASISTENCIA, Company::MODULE_REPORTES, Company::MODULE_AUSENCIAS, Company::MODULE_DISPOSITIVOS];

        for ($i = 1; $i <= 50; $i++) {
            $email = $faker->unique()->companyEmail();
            $name = $faker->company();
            $legalName = $name . ' S.A. de C.V.';
            $taxId = strtoupper($faker->unique()->bothify('???###########'));
            $phone = $faker->phoneNumber();
            $address = $faker->address();
            $plan = $faker->randomElement($plans);
            $status = $faker->randomElement($statuses);
            $timezone = 'America/Mexico_City';
            $modules = $faker->randomElements($modulesList, $faker->numberBetween(1, 4));

            // Fechas: algunas del año pasado, otras del presente
            $subscriptionEndsAt = $faker->boolean(50) ? // 50% chance de año pasado
                $faker->dateTimeBetween('-2 years', '-1 year') :
                $faker->dateTimeBetween('now', '+2 years');

            $trialEndsAt = $faker->boolean(30) ? $faker->dateTimeBetween('now', '+1 month') : null;

            $company = Company::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'legal_name' => $legalName,
                    'tax_id' => $taxId,
                    'phone' => $phone,
                    'address' => $address,
                    'plan' => $plan,
                    'status' => $status,
                    'timezone' => $timezone,
                    'modules' => $modules,
                    'trial_ends_at' => $trialEndsAt,
                    'subscription_ends_at' => $subscriptionEndsAt,
                ]
            );

            // Asignar módulos random si es premium/enterprise
            if ($plan !== 'basic') {
                $companyModules = [];
                foreach ($modules as $module) {
                    $companyModules[] = [
                        'company_id' => $company->id,
                        'module_name' => $module,
                        'is_active' => $faker->boolean(80), // 80% chance de activo
                        'activated_at' => $faker->dateTimeBetween('-1 year', 'now'),
                    ];
                }

                foreach ($companyModules as $moduleData) {
                    CompanyModule::firstOrCreate(
                        [
                            'company_id' => $moduleData['company_id'],
                            'module_name' => $moduleData['module_name'],
                        ],
                        $moduleData
                    );
                }
            }
        }

        $this->command->info('✅ 50 empresas adicionales creadas con datos random');
    }
}
