<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class CreateTestUser extends Command
{
    protected $signature = 'user:create {email} {name} {--role=director} {--password=password}';

    protected $description = 'Crear un usuario de prueba rápidamente';

    public function handle()
    {
        $email = $this->argument('email');
        $name = $this->argument('name');
        $roleSlug = $this->option('role');
        $password = $this->option('password');

        // Verificar si el usuario ya existe
        if (User::where('email', $email)->exists()) {
            $this->error("El usuario con email {$email} ya existe.");
            return 1;
        }

        // Buscar el rol
        $role = Role::where('slug', $roleSlug)->first();
        if (!$role) {
            $this->error("El rol '{$roleSlug}' no existe.");
            $this->info("Roles disponibles: super_admin, director, rh, jefe_area, operador");
            return 1;
        }

        // Crear el usuario
        $user = User::create([
            'company_id' => 1, // Hospital Central (por defecto)
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'phone' => '+52 55 0000 0000',
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        // Asignar rol
        $user->roles()->attach($role);

        $this->info("✓ Usuario creado exitosamente");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("Email:    {$user->email}");
        $this->line("Password: {$password}");
        $this->line("Nombre:   {$user->name}");
        $this->line("Rol:      {$role->name}");
        $this->line("Empresa:  Hospital Central (ID: 1)");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        return 0;
    }
}

