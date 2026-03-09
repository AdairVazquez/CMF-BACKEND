<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Company::where('email', 'admin@hospitalcentral.com')->first();

        if (!$hospital) {
            $this->command->error('❌ Hospital Central no encontrado. Ejecuta CompanySeeder primero.');
            return;
        }

        // Obtener roles
        $roleSuperAdmin = Role::where('slug', 'super_admin')->first();
        $roleDirector = Role::where('slug', 'director')->first();
        $roleRH = Role::where('slug', 'rh')->first();
        $roleJefeArea = Role::where('slug', 'jefe_area')->first();
        $roleOperador = Role::where('slug', 'operador')->first();

        // Crear usuarios de prueba
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'super@saas.com',
                'password' => Hash::make('password'),
                'company_id' => null,
                'is_super_admin' => true,
                'is_active' => true,
                'role' => $roleSuperAdmin,
            ],
            [
                'name' => 'Director Hospital',
                'email' => 'director@hospital.com',
                'password' => Hash::make('password'),
                'company_id' => $hospital->id,
                'phone' => '+52 55 1111 1111',
                'is_super_admin' => false,
                'is_active' => true,
                'role' => $roleDirector,
            ],
            [
                'name' => 'RH Hospital',
                'email' => 'rh@hospital.com',
                'password' => Hash::make('password'),
                'company_id' => $hospital->id,
                'phone' => '+52 55 2222 2222',
                'is_super_admin' => false,
                'is_active' => true,
                'role' => $roleRH,
            ],
            [
                'name' => 'Jefe de Urgencias',
                'email' => 'jefe@hospital.com',
                'password' => Hash::make('password'),
                'company_id' => $hospital->id,
                'phone' => '+52 55 3333 3333',
                'is_super_admin' => false,
                'is_active' => true,
                'role' => $roleJefeArea,
            ],
            [
                'name' => 'Operador Sistemas',
                'email' => 'operador@hospital.com',
                'password' => Hash::make('password'),
                'company_id' => $hospital->id,
                'phone' => '+52 55 4444 4444',
                'is_super_admin' => false,
                'is_active' => true,
                'role' => $roleOperador,
            ],
        ];

        $createdUsers = [];
        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Asignar rol
            if ($role && !$user->hasRole($role->slug)) {
                $user->roles()->syncWithoutDetaching($role->id);
            }

            $createdUsers[] = $user;
        }

        $this->command->info('✅ Usuarios creados exitosamente');
        $this->command->info('   - ' . count($createdUsers) . ' usuarios creados');
        $this->command->info('   - Password para todos: password');
        $this->command->newLine();
        $this->command->info('📧 Credenciales de acceso:');
        foreach ($createdUsers as $user) {
            $roleName = $user->roles->first()?->name ?? 'Sin rol';
            $this->command->info("   - {$user->email} ({$roleName})");
        }
    }
}
