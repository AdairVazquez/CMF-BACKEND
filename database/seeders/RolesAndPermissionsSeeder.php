<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Definir permisos por módulo
        $permissions = [
            // Companies
            ['name' => 'Ver empresas', 'slug' => 'companies.view', 'module' => 'companies'],
            ['name' => 'Crear empresas', 'slug' => 'companies.create', 'module' => 'companies'],
            ['name' => 'Editar empresas', 'slug' => 'companies.edit', 'module' => 'companies'],
            ['name' => 'Eliminar empresas', 'slug' => 'companies.delete', 'module' => 'companies'],

            // Branches
            ['name' => 'Ver sucursales', 'slug' => 'branches.view', 'module' => 'branches'],
            ['name' => 'Crear sucursales', 'slug' => 'branches.create', 'module' => 'branches'],
            ['name' => 'Editar sucursales', 'slug' => 'branches.edit', 'module' => 'branches'],
            ['name' => 'Eliminar sucursales', 'slug' => 'branches.delete', 'module' => 'branches'],

            // Departments
            ['name' => 'Ver departamentos', 'slug' => 'departments.view', 'module' => 'departments'],
            ['name' => 'Crear departamentos', 'slug' => 'departments.create', 'module' => 'departments'],
            ['name' => 'Editar departamentos', 'slug' => 'departments.edit', 'module' => 'departments'],
            ['name' => 'Eliminar departamentos', 'slug' => 'departments.delete', 'module' => 'departments'],

            // Employees
            ['name' => 'Ver empleados', 'slug' => 'employees.view', 'module' => 'employees'],
            ['name' => 'Crear empleados', 'slug' => 'employees.create', 'module' => 'employees'],
            ['name' => 'Editar empleados', 'slug' => 'employees.edit', 'module' => 'employees'],
            ['name' => 'Eliminar empleados', 'slug' => 'employees.delete', 'module' => 'employees'],

            // NFC Cards
            ['name' => 'Ver tarjetas NFC', 'slug' => 'nfc_cards.view', 'module' => 'nfc_cards'],
            ['name' => 'Crear tarjetas NFC', 'slug' => 'nfc_cards.create', 'module' => 'nfc_cards'],
            ['name' => 'Editar tarjetas NFC', 'slug' => 'nfc_cards.edit', 'module' => 'nfc_cards'],
            ['name' => 'Eliminar tarjetas NFC', 'slug' => 'nfc_cards.delete', 'module' => 'nfc_cards'],
            ['name' => 'Asignar tarjetas NFC', 'slug' => 'nfc_cards.assign', 'module' => 'nfc_cards'],
            ['name' => 'Bloquear tarjetas NFC', 'slug' => 'nfc_cards.block', 'module' => 'nfc_cards'],

            // Devices
            ['name' => 'Ver dispositivos', 'slug' => 'devices.view', 'module' => 'devices'],
            ['name' => 'Crear dispositivos', 'slug' => 'devices.create', 'module' => 'devices'],
            ['name' => 'Editar dispositivos', 'slug' => 'devices.edit', 'module' => 'devices'],
            ['name' => 'Eliminar dispositivos', 'slug' => 'devices.delete', 'module' => 'devices'],
            ['name' => 'Monitorear dispositivos', 'slug' => 'devices.monitor', 'module' => 'devices'],

            // Attendance
            ['name' => 'Ver asistencias', 'slug' => 'attendance.view', 'module' => 'attendance'],
            ['name' => 'Ver asistencias de su departamento', 'slug' => 'attendance.view_own_department', 'module' => 'attendance'],
            ['name' => 'Ver todas las asistencias', 'slug' => 'attendance.view_all', 'module' => 'attendance'],
            ['name' => 'Registro manual de asistencia', 'slug' => 'attendance.manual_register', 'module' => 'attendance'],

            // Reports
            ['name' => 'Ver reportes', 'slug' => 'reports.view', 'module' => 'reports'],
            ['name' => 'Exportar reportes PDF', 'slug' => 'reports.export_pdf', 'module' => 'reports'],
            ['name' => 'Exportar reportes Excel', 'slug' => 'reports.export_excel', 'module' => 'reports'],
            ['name' => 'Exportar reportes CSV', 'slug' => 'reports.export_csv', 'module' => 'reports'],

            // Leave Requests
            ['name' => 'Ver solicitudes de ausencia', 'slug' => 'leave_requests.view', 'module' => 'leave_requests'],
            ['name' => 'Crear solicitudes de ausencia', 'slug' => 'leave_requests.create', 'module' => 'leave_requests'],
            ['name' => 'Aprobar como jefe', 'slug' => 'leave_requests.approve_jefe', 'module' => 'leave_requests'],
            ['name' => 'Aprobar como RH', 'slug' => 'leave_requests.approve_rh', 'module' => 'leave_requests'],
            ['name' => 'Rechazar solicitudes', 'slug' => 'leave_requests.reject', 'module' => 'leave_requests'],

            // Shifts
            ['name' => 'Ver turnos', 'slug' => 'shifts.view', 'module' => 'shifts'],
            ['name' => 'Crear turnos', 'slug' => 'shifts.create', 'module' => 'shifts'],
            ['name' => 'Editar turnos', 'slug' => 'shifts.edit', 'module' => 'shifts'],
            ['name' => 'Eliminar turnos', 'slug' => 'shifts.delete', 'module' => 'shifts'],

            // Rules
            ['name' => 'Ver reglas de asistencia', 'slug' => 'rules.view', 'module' => 'rules'],
            ['name' => 'Crear reglas de asistencia', 'slug' => 'rules.create', 'module' => 'rules'],
            ['name' => 'Editar reglas de asistencia', 'slug' => 'rules.edit', 'module' => 'rules'],
            ['name' => 'Eliminar reglas de asistencia', 'slug' => 'rules.delete', 'module' => 'rules'],

            // Users
            ['name' => 'Ver usuarios', 'slug' => 'users.view', 'module' => 'users'],
            ['name' => 'Crear usuarios', 'slug' => 'users.create', 'module' => 'users'],
            ['name' => 'Editar usuarios', 'slug' => 'users.edit', 'module' => 'users'],
            ['name' => 'Eliminar usuarios', 'slug' => 'users.delete', 'module' => 'users'],
        ];

        // Crear permisos
        $createdPermissions = [];
        foreach ($permissions as $permission) {
            $createdPermissions[$permission['slug']] = Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Definir roles con jerarquía
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'hierarchy_level' => 100, 'description' => 'Dueños del SaaS, acceso total'],
            ['name' => 'Director', 'slug' => 'director', 'hierarchy_level' => 90, 'description' => 'Máximo rol del cliente, ve todo'],
            ['name' => 'Recursos Humanos', 'slug' => 'rh', 'hierarchy_level' => 85, 'description' => 'Gestión de empleados y asistencia'],
            ['name' => 'Subdirector', 'slug' => 'subdirector', 'hierarchy_level' => 80, 'description' => 've su área asignada'],
            ['name' => 'Jefe de Área', 'slug' => 'jefe_area', 'hierarchy_level' => 70, 'description' => 've su departamento'],
            ['name' => 'Operador', 'slug' => 'operador', 'hierarchy_level' => 50, 'description' => 'Opera dispositivos y monitoreo'],
            ['name' => 'Empleado', 'slug' => 'empleado', 'hierarchy_level' => 10, 'description' => 'Sin acceso al panel'],
        ];

        // Crear roles y asignar permisos
        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );

            // Asignar permisos según el rol
            $permissionsToAssign = [];

            switch ($roleData['slug']) {
                case 'super_admin':
                    // TODOS los permisos
                    $permissionsToAssign = array_values($createdPermissions);
                    break;

                case 'director':
                    // Todo excepto companies.create y companies.delete
                    foreach ($createdPermissions as $slug => $permission) {
                        if (!in_array($slug, ['companies.create', 'companies.delete'])) {
                            $permissionsToAssign[] = $permission;
                        }
                    }
                    break;

                case 'rh':
                    // employees.*, attendance.*, reports.*, leave_requests.*
                    foreach ($createdPermissions as $slug => $permission) {
                        if (str_starts_with($slug, 'employees.') ||
                            str_starts_with($slug, 'attendance.') ||
                            str_starts_with($slug, 'reports.') ||
                            str_starts_with($slug, 'leave_requests.') ||
                            str_starts_with($slug, 'nfc_cards.')) {
                            $permissionsToAssign[] = $permission;
                        }
                    }
                    break;

                case 'subdirector':
                    // Ver su área + attendance.view + reports.view
                    $slugs = ['branches.view', 'departments.view', 'employees.view', 'attendance.view', 'reports.view', 'shifts.view'];
                    foreach ($slugs as $slug) {
                        if (isset($createdPermissions[$slug])) {
                            $permissionsToAssign[] = $createdPermissions[$slug];
                        }
                    }
                    break;

                case 'jefe_area':
                    // attendance.view_own_department + leave_requests.approve_jefe
                    $slugs = ['employees.view', 'attendance.view_own_department', 'leave_requests.view', 'leave_requests.approve_jefe', 'reports.view'];
                    foreach ($slugs as $slug) {
                        if (isset($createdPermissions[$slug])) {
                            $permissionsToAssign[] = $createdPermissions[$slug];
                        }
                    }
                    break;

                case 'operador':
                    // devices.monitor + attendance.view
                    $slugs = ['devices.view', 'devices.monitor', 'attendance.view'];
                    foreach ($slugs as $slug) {
                        if (isset($createdPermissions[$slug])) {
                            $permissionsToAssign[] = $createdPermissions[$slug];
                        }
                    }
                    break;

                case 'empleado':
                    // Sin permisos (sin acceso al panel)
                    break;
            }

            // Sincronizar permisos
            if (!empty($permissionsToAssign)) {
                $role->permissions()->sync(collect($permissionsToAssign)->pluck('id'));
            }
        }

        $this->command->info('✅ Roles y permisos creados exitosamente');
        $this->command->info('   - ' . count($permissions) . ' permisos creados');
        $this->command->info('   - ' . count($roles) . ' roles creados con sus permisos asignados');
    }
}
