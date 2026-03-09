# Sistema SaaS Multi-tenant - Control de Accesos y Asistencia

## ✅ COMPLETADO

### 📦 Enums Generados (7)
- `EmployeeType`: base, confianza
- `AttendanceType`: entrada, salida
- `LeaveStatus`: pendiente, aprobado_jefe, aprobado_rh, rechazado
- `DeviceStatus`: activo, inactivo, mantenimiento
- `CompanyStatus`: activo, suspendido, inactivo, prueba
- `CardStatus`: activa, inactiva, bloqueada, perdida
- `EmployeeStatus`: activo, inactivo, baja, suspendido

### 🗄️ Migraciones Generadas (16)
1. ✅ companies
2. ✅ branches
3. ✅ departments
4. ✅ shifts
5. ✅ attendance_rules
6. ✅ users (con sessions y password_reset_tokens)
7. ✅ employees
8. ✅ nfc_cards
9. ✅ devices
10. ✅ attendance_logs
11. ✅ roles
12. ✅ permissions
13. ✅ role_user
14. ✅ permission_role
15. ✅ leave_requests
16. ✅ company_modules

### 📋 Modelos Generados (14)
Todos con:
- ✅ $fillable completo
- ✅ $casts con Enums
- ✅ Relaciones (belongsTo, hasMany, belongsToMany)
- ✅ Scope: scopeForCompany()
- ✅ SoftDeletes donde aplica
- ✅ Métodos helper de negocio

**Modelos:**
1. Company
2. Branch
3. Department
4. Shift
5. AttendanceRule
6. User (con Sanctum y roles)
7. Employee (con scopes base/confianza)
8. NfcCard
9. Device
10. AttendanceLog
11. Role
12. Permission
13. LeaveRequest
14. CompanyModule

### 🎯 Características Implementadas

**Multi-tenancy:**
- Todos los modelos principales tienen `company_id`
- Scope `scopeForCompany()` en todos los modelos necesarios
- Aislamiento total por empresa

**Sistema de Roles y Permisos:**
- Roles: SuperAdmin, Director, Subdirector, JefeArea, RH, Operador, Empleado
- Permisos granulares por módulo
- Tablas pivot para many-to-many

**Empleados:**
- Tipo: Base (puede solicitar ausencias) / Confianza (no puede)
- Scopes especializados: `scopeBase()`, `scopeConfianza()`
- Método `canRequestLeave()` valida tipo + módulo activo

**Asistencia:**
- Registro NFC automático
- Registro manual con auditoría
- Geolocalización opcional
- Tipos: entrada/salida

**Módulos Premium:**
- `company_modules` para activar/desactivar features
- `leave_requests` como módulo premium
- Validación en modelos con `hasModuleActive()`

**Reglas de Asistencia:**
- Tolerancias configurables
- Horas extra con multiplicador
- Penalizaciones
- Auto-checkout

## 📁 Estructura Creada

```
app/
├── Enums/
│   ├── AttendanceType.php
│   ├── CardStatus.php
│   ├── CompanyStatus.php
│   ├── DeviceStatus.php
│   ├── EmployeeStatus.php
│   ├── EmployeeType.php
│   └── LeaveStatus.php
├── Models/
│   ├── AttendanceLog.php
│   ├── AttendanceRule.php
│   ├── Branch.php
│   ├── Company.php
│   ├── CompanyModule.php
│   ├── Department.php
│   ├── Device.php
│   ├── Employee.php
│   ├── LeaveRequest.php
│   ├── NfcCard.php
│   ├── Permission.php
│   ├── Role.php
│   ├── Shift.php
│   └── User.php
├── Http/
│   └── Controllers/
│       └── Api/
│           └── V1/
├── Services/
└── Repositories/

database/
└── migrations/
    ├── 2024_01_01_000001_create_companies_table.php
    ├── 2024_01_01_000002_create_branches_table.php
    ├── 2024_01_01_000003_create_departments_table.php
    ├── 2024_01_01_000004_create_shifts_table.php
    ├── 2024_01_01_000005_create_attendance_rules_table.php
    ├── 2024_01_01_000006_create_users_table.php
    ├── 2024_01_01_000007_create_employees_table.php
    ├── 2024_01_01_000008_create_nfc_cards_table.php
    ├── 2024_01_01_000009_create_devices_table.php
    ├── 2024_01_01_000010_create_attendance_logs_table.php
    ├── 2024_01_01_000011_create_roles_table.php
    ├── 2024_01_01_000012_create_permissions_table.php
    ├── 2024_01_01_000013_create_role_user_table.php
    ├── 2024_01_01_000014_create_permission_role_table.php
    ├── 2024_01_01_000015_create_leave_requests_table.php
    └── 2024_01_01_000016_create_company_modules_table.php
```

## 🔐 Seguridad Implementada

1. **Aislamiento Multi-tenant**: Todos los queries filtran por `company_id`
2. **SoftDeletes**: Companies, Users, Employees, NfcCards mantienen historial
3. **Índices**: Optimizados para queries frecuentes
4. **Foreign Keys**: Cascade y set null según lógica de negocio
5. **Validación de tipos**: Enums estrictos en toda la aplicación

## 🎯 Lógica de Negocio Clave

### Visibilidad por Rol
```php
// Director/RH → ve toda la empresa
Employee::forCompany($companyId)->get();

// Subdirector → ve su área
Employee::forCompany($companyId)
    ->where('branch_id', $user->branch_id)
    ->get();

// Jefe de Área → ve su departamento
Employee::forCompany($companyId)
    ->where('department_id', $user->department_id)
    ->get();
```

### Solicitud de Ausencias
```php
// Solo empleados tipo "base" pueden solicitar
if ($employee->canRequestLeave()) {
    // Crear solicitud
}

// Flujo de aprobación
$request->status = LeaveStatus::PENDIENTE;
// Jefe aprueba
$request->status = LeaveStatus::APROBADO_JEFE;
// RH valida
$request->status = LeaveStatus::APROBADO_RH;
```

### Registro NFC
```php
// Dispositivo envía: device_id, card_uid, timestamp
$card = NfcCard::where('card_uid', $uid)->active()->first();
$employee = $card->employee;

// Determinar tipo (entrada/salida)
$lastLog = AttendanceLog::forEmployee($employee->id)
    ->latest('recorded_at')
    ->first();

$type = !$lastLog || $lastLog->type === AttendanceType::SALIDA 
    ? AttendanceType::ENTRADA 
    : AttendanceType::SALIDA;

// Registrar
AttendanceLog::create([...]);
```

## 📊 Próximos Pasos Sugeridos

1. **Seeders**: Roles, permisos iniciales, empresa de prueba
2. **DTOs**: Request/Response para validación tipada
3. **Controllers API**: CRUD por cada entidad
4. **Services**: Lógica de negocio compleja
5. **Middleware**: Tenant scope automático
6. **Guards**: Verificación de roles/permisos
7. **Tests**: Unitarios y de integración
8. **WebSockets**: Reverb para notificaciones en tiempo real
9. **Queues**: Procesamiento de reportes pesados
10. **Cache**: Redis para queries frecuentes

---

**Estado:** ✅ Base de datos y modelos listos
**Siguiente:** Decide qué construir (Seeders, Controllers, Auth, etc.)
