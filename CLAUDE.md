# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Proyecto

**CMF - Control y Monitoreo de Fuerza Laboral**

SaaS white-label multi-tenant para control de asistencia mediante tarjetas NFC. Diseñado para hospitales, fábricas y universidades. El backend es una API REST en Laravel 12 que recibe registros de lectores NFC (ESP32) y los expone a un dashboard en Next.js 15.

---

## Comandos de desarrollo

```bash
# Instalación completa desde cero
composer setup

# Levantar todos los servicios en paralelo (servidor + queue + vite)
composer dev

# Ejecutar todas las pruebas
composer test
# o equivalente:
php artisan test

# Ejecutar un test específico
php artisan test --filter=NombreDelTest

# Migrar y poblar la base de datos desde cero
php artisan migrate:fresh --seed

# Diagnóstico completo del sistema (DB, Redis, Queue, Storage)
php artisan cmf:health

# Ver estado del queue
php artisan queue:health

# Ver rutas API
php artisan route:list --path=api

# Limpiar todos los caches de desarrollo
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
```

### Scripts de prueba (PowerShell, solo desarrollo Windows)

```powershell
.\test-auth.ps1          # Prueba automatizada de autenticación
.\test-2fa-flow.ps1      # Flujo interactivo completo de 2FA
.\start-queue-worker.ps1 # Worker con auto-restart en loop
.\monitor-queue.ps1      # Monitor continuo con auto-restart
```

### Comandos Artisan personalizados

```bash
php artisan user:create email@ejemplo.com "Nombre" --role=director
php artisan user:reset-2fa email@ejemplo.com
php artisan queue:auto-restart
php artisan queue:check-jobs
php artisan mail:test email@ejemplo.com
```

---

## Arquitectura

### Flujo de una request

```
NFC Reader (ESP32) → POST /api/v1/nfc/register
Sanctum Token      → GET/POST /api/v1/*
                       ↓
              bootstrap/app.php (middleware global)
                       ↓
              routes/api.php (prefijo: /api/v1)
                       ↓
              FormRequest (validación)
                       ↓
              Controller (App\Http\Controllers\Api\V1\)
                       ↓
              Service (App\Services\)
                       ↓
              Eloquent Model / SmartCacheService
```

### Multi-tenant

Todo dato pertenece a una empresa via `company_id`. El `TenantScope` middleware inyecta este scope automáticamente. El Super Admin (`is_super_admin = true`) omite el filtro y accede a todas las empresas.

- Siempre filtrar por `company_id` en queries de recursos
- Usar el middleware `tenant.scope` en rutas de recursos
- Usar middleware `permission:recurso.accion` para control granular

### Patrón de código

Los controladores usan el trait `ApiResponse` para respuestas estandarizadas y delegan toda la lógica de negocio a `Services`. Las validaciones van en `FormRequests`.

```php
// Estructura típica de un controlador
class ResourceController extends Controller
{
    use ApiResponse;

    public function __construct(private ResourceService $service) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->service->list($request->user()->company_id);
        return $this->successResponse($data, 'Lista de recursos');
    }
}
```

### Formato estándar de respuestas

```json
// Éxito
{ "success": true, "message": "...", "data": {...} }

// Paginado
{ "success": true, "message": "...", "data": [...], "meta": { "current_page": 1, "total": 100, ... } }

// Error
{ "success": false, "message": "...", "errors": { "campo": ["mensaje"] } }
```

### Resiliencia (SmartCache / SmartQueue)

El sistema tiene fallback automático Redis → Archivo/Database. **Usar siempre `SmartCacheService` en lugar del facade `Cache`** directamente:

```php
// ✅ Correcto
$cache = app(SmartCacheService::class);
$cache->remember('key', 3600, fn() => getData());

// ❌ Evitar
Cache::remember('key', 3600, fn() => getData());
```

El endpoint `GET /api/v1/system/health` reporta el estado actual (`healthy` / `degraded` / `critical`).

---

## Sistema de autenticación (ya implementado)

El módulo de autenticación está **completo**. Incluye:

- Login con email + password → retorna token temporal si 2FA está activo
- 2FA por email (código 6 dígitos, expira en 10 min)
- 8 códigos de recuperación encriptados en DB (formato `XXXX-XXXX-XXXX`)
- Bloqueo automático tras 5 intentos fallidos (15 min)
- Notificaciones por email: 2FA, login nuevo, cuenta bloqueada, reset de password
- Queue con fallback síncrono si el worker está caído

### Middleware registrados

| Alias | Clase | Uso |
|-------|-------|-----|
| `tenant.scope` | `TenantScope` | Filtro multi-tenant por company_id |
| `permission` | `CheckPermission` | `middleware('permission:employees.view')` |
| `account.locked` | `CheckAccountLocked` | Verifica bloqueo de cuenta |
| `two.factor` | `TwoFactorMiddleware` | Requiere 2FA completado |

### Logs de seguridad

Los eventos de autenticación se registran en canales separados:
- `storage/logs/security-YYYY-MM-DD.log` — logins, bloqueos, 2FA
- `storage/logs/queue-YYYY-MM-DD.log` — estado del worker
- `storage/logs/mail-YYYY-MM-DD.log` — envíos de email

---

## Convenciones de código

### Idioma

| Elemento | Idioma |
|----------|--------|
| Clases, métodos, variables, rutas API, columnas DB | **Inglés** |
| Valores de enums, mensajes de respuesta API, validaciones, comentarios | **Español** |

### Ejemplo

```php
enum EmployeeType: string
{
    case BASE = 'base';
    case CONFIANZA = 'confianza';

    public function label(): string
    {
        return match($this) {
            self::BASE => 'Base',
            self::CONFIANZA => 'Confianza',
        };
    }
}
```

### Permisos (slug format)

Los permisos siguen el patrón `recurso.accion`:
`employees.view`, `employees.create`, `nfc_cards.block`, `attendance.view_all`, etc.

---

## Estado del proyecto

### ✅ Completado

- Sistema de autenticación enterprise completo (Sanctum + 2FA + bloqueos + recovery)
- Modelos y migraciones de todas las entidades
- Seeders con datos de prueba
- Sistema de resiliencia Redis → Fallback automático
- Health check endpoint + comando `cmf:health`
- Roles y permisos (7 niveles de acceso)
- Rate limiting por tipo de endpoint

### 🔲 Pendiente (próximos módulos a implementar)

1. CRUD Companies
2. CRUD Branches
3. CRUD Departments
4. CRUD Employees
5. CRUD NFC Cards
6. CRUD Devices
7. Attendance registration (NFC public endpoint)
8. Leave requests system (doble aprobación: Jefe → RH)
9. Reports & exports (PDF, Excel, CSV)
10. WebSockets con Laravel Reverb

---

## Usuarios de prueba (seeders)

| Email | Password | Rol | Empresa |
|-------|----------|-----|---------|
| super@saas.com | password | Super Admin | — |
| director@hospital.com | password | Director | Hospital Central |
| rh@hospital.com | password | RH | Hospital Central |
| jefe@hospital.com | password | Jefe de Área | Hospital Central |
| operador@hospital.com | password | Operador | Hospital Central |

---

## Jerarquía de roles

| Rol | Nivel | Alcance |
|-----|-------|---------|
| Super Admin | 100 | Acceso total al sistema (todas las empresas) |
| Director | 90 | Toda su empresa |
| Recursos Humanos | 85 | Empleados, asistencia, reportes, ausencias |
| Subdirector | 80 | Su área + asistencia + reportes |
| Jefe de Área | 70 | Su departamento + aprobación de ausencias |
| Operador | 50 | Dispositivos + visualización de asistencia |
| Empleado | 10 | Sin acceso al panel (solo NFC físico) |

---

## Módulos del sistema

Los módulos premium se activan por empresa en la tabla `company_modules`. La validación de si un módulo está activo se realiza a nivel de servicio antes de ejecutar lógica de negocio.

| Módulo | Premium |
|--------|---------|
| Asistencia, Reportes básicos, Dispositivos | No |
| Ausencias, Geolocalización, Reportes avanzados, Notificaciones, Integración API | Sí |

---

## Variables de entorno clave

```env
# Queue - si Redis no está disponible, usa database
QUEUE_CONNECTION=database

# Cache - configuración dual con fallback automático
CACHE_STORE_PRIMARY=redis
CACHE_STORE_FALLBACK=file

# Mail - usar App Password de Gmail (no contraseña real)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls

# Requerido para encriptación de 2FA secrets
APP_KEY=base64:...
```
