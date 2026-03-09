<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando seeders del sistema SaaS Multi-tenant...');
        $this->command->newLine();

        // Orden correcto respetando dependencias
        $this->call([
            RolesAndPermissionsSeeder::class,
            CompanySeeder::class,
            BranchAndDepartmentSeeder::class,
            ShiftSeeder::class,
            UserSeeder::class,
            EmployeeSeeder::class,
            DeviceSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✅ ¡Todos los seeders ejecutados exitosamente!');
        $this->command->newLine();
        $this->command->info('📊 Resumen de datos creados:');
        $this->command->info('   - Roles: 7 (desde empleado hasta super_admin)');
        $this->command->info('   - Permisos: 60+ asignados por rol');
        $this->command->info('   - Empresas: 2 (Hospital Central + Empresa Demo)');
        $this->command->info('   - Sucursales: 3');
        $this->command->info('   - Departamentos: 7');
        $this->command->info('   - Turnos: 3');
        $this->command->info('   - Usuarios: 5 con diferentes roles');
        $this->command->info('   - Empleados: 10 (5 base + 5 confianza)');
        $this->command->info('   - Tarjetas NFC: 10 asignadas');
        $this->command->info('   - Dispositivos: 3 activos');
        $this->command->newLine();
        $this->command->info('🔐 Credenciales de prueba:');
        $this->command->info('   📧 super@saas.com      → Super Admin (dueño SaaS)');
        $this->command->info('   📧 director@hospital.com → Director (cliente)');
        $this->command->info('   📧 rh@hospital.com      → Recursos Humanos');
        $this->command->info('   📧 jefe@hospital.com    → Jefe de Área');
        $this->command->info('   📧 operador@hospital.com → Operador');
        $this->command->info('   🔑 Password: password (todos)');
        $this->command->newLine();
    }
}
