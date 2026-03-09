# CMF - Control y Monitoreo de Fuerza Laboral

Sistema SaaS white-label de control de accesos y asistencia laboral mediante tarjetas NFC. Diseñado para operar en hospitales, fábricas, universidades e instituciones que requieren gestión automatizada de asistencia y control de accesos.

## Descripción del proyecto

CMF es una plataforma multi-tenant que permite a múltiples organizaciones gestionar de forma independiente y segura la asistencia de su personal mediante tecnología NFC (Near Field Communication). El sistema registra automáticamente entradas y salidas, calcula horas trabajadas, gestiona permisos y ausencias, y genera reportes detallados para la toma de decisiones.

La arquitectura multi-tenant garantiza que cada empresa cliente tenga sus datos completamente aislados mediante el uso de `company_id` en todas las operaciones, eliminando cualquier posibilidad de filtración de información entre organizaciones.

## Características principales

- **Multi-tenant:** Aislamiento total de datos por empresa mediante `company_id`
- **Registro automático NFC:** Captura de asistencia sin intervención manual
- **Jerarquía de roles:** Sistema de permisos granulares con 7 niveles de acceso
- **Módulos premium:** Activación selectiva de funcionalidades por cliente
- **Tipos de empleado:** Diferenciación entre personal base y de confianza
- **Reportes avanzados:** Exportación en PDF, Excel y CSV
- **Tiempo real:** Notificaciones instantáneas con Laravel Reverb
- **Geolocalización:** Registro de ubicación GPS en cada marcaje (opcional)
- **API REST:** Interfaz completa para integración con sistemas externos
- **Dashboard responsive:** Panel de administración construido con Next.js 15

## Stack tecnológico

| Componente | Tecnología | Versión |
|------------|-----------|---------|
| **Backend** | Laravel | 12.x |
| **Lenguaje** | PHP | 8.4 |
| **Frontend** | Next.js | 15.x |
| **Framework UI** | React | 19.x |
| **Base de datos** | MySQL | 8.0 |
| **Cache** | Redis | 7.x |
| **Colas** | Laravel Horizon | 5.x |
| **WebSockets** | Laravel Reverb | 1.x |
| **Autenticación** | Laravel Sanctum | 4.x |
| **CSS** | Tailwind CSS | 4.x |
| **Estado global** | Zustand | 5.x |
| **Consultas** | TanStack Query | 5.x |

## Arquitectura del sistema

```
┌─────────────┐
│ Tarjeta NFC │
└──────┬──────┘
       │
       ▼
┌──────────────────┐
│  Lector NFC      │
│  (Dispositivo)   │
└──────┬───────────┘
       │
       ▼
┌──────────────────────┐
│  Controlador ESP32   │
│  (Microcontrolador)  │
└──────┬───────────────┘
       │ HTTP POST
       ▼
┌──────────────────────────────┐
│  API Laravel (Backend)       │
│  - Validación de tarjeta     │
│  - Verificación de empleado  │
│  - Registro en base de datos │
│  - Emisión de eventos        │
└──────┬───────────────────────┘
       │
       ├──────────────┐
       │              │
       ▼              ▼
┌─────────────┐  ┌─────────────────┐
│   MySQL     │  │  Laravel Reverb │
│ (Persistencia)  │  (WebSockets)   │
└─────────────┘  └────────┬────────┘
                          │
                          ▼
                 ┌──────────────────┐
                 │ Dashboard Next.js│
                 │ (Actualización   │
                 │  en tiempo real) │
                 └──────────────────┘
```

## Estructura del proyecto

```
system-core-api/
├── app/
│   ├── Enums/                 # Enumeraciones del sistema
│   │   ├── AttendanceType.php
│   │   ├── CardStatus.php
│   │   ├── CompanyStatus.php
│   │   ├── DeviceStatus.php
│   │   ├── EmployeeStatus.php
│   │   ├── EmployeeType.php
│   │   └── LeaveStatus.php
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           └── V1/        # Controladores API versión 1
│   ├── Models/                # Modelos Eloquent
│   │   ├── AttendanceLog.php
│   │   ├── AttendanceRule.php
│   │   ├── Branch.php
│   │   ├── Company.php
│   │   ├── CompanyModule.php
│   │   ├── Department.php
│   │   ├── Device.php
│   │   ├── Employee.php
│   │   ├── LeaveRequest.php
│   │   ├── NfcCard.php
│   │   ├── Permission.php
│   │   ├── Role.php
│   │   ├── Shift.php
│   │   └── User.php
│   ├── Services/              # Lógica de negocio
│   └── Repositories/          # Capa de acceso a datos
├── database/
│   ├── migrations/            # Esquema de base de datos
│   └── seeders/               # Datos de prueba
│       ├── BranchAndDepartmentSeeder.php
│       ├── CompanySeeder.php
│       ├── DeviceSeeder.php
│       ├── EmployeeSeeder.php
│       ├── RolesAndPermissionsSeeder.php
│       ├── ShiftSeeder.php
│       └── UserSeeder.php
├── routes/
│   ├── api.php                # Rutas de API
│   └── web.php                # Rutas web
├── tests/                     # Pruebas automatizadas
├── .env                       # Variables de entorno
├── composer.json              # Dependencias PHP
└── artisan                    # CLI de Laravel
```

## Requisitos del sistema

### Requisitos mínimos

- **PHP:** >= 8.4
- **Node.js:** >= 20.x
- **MySQL:** >= 8.0
- **Redis:** >= 7.x
- **Composer:** >= 2.7
- **NPM:** >= 10.x

### Extensiones PHP requeridas

- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO MySQL
- Tokenizer
- XML
- Redis

## Instalación local

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/system-core-api.git
cd system-core-api
```

### 2. Instalar dependencias de PHP

```bash
composer install
```

### 3. Instalar dependencias de Node.js

```bash
npm install
```

### 4. Configurar variables de entorno

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Configurar base de datos

Editar el archivo `.env` con las credenciales de MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=system_core_api
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Ejecutar migraciones y seeders

```bash
php artisan migrate:fresh --seed
```

### 7. Instalar Laravel Sanctum

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 8. Iniciar servicios

En terminales separadas:

```bash
# Terminal 1: Servidor Laravel
php artisan serve

# Terminal 2: Cola de trabajos
php artisan queue:listen

# Terminal 3: WebSockets (Reverb)
php artisan reverb:start

# Terminal 4: Compilación de assets
npm run dev
```

El servidor estará disponible en `http://localhost:8000`

## Variables de entorno

| Variable | Descripción | Valor por defecto |
|----------|-------------|-------------------|
| `APP_NAME` | Nombre de la aplicación | CMF |
| `APP_ENV` | Entorno de ejecución | local |
| `APP_KEY` | Llave de encriptación | (generada automáticamente) |
| `APP_DEBUG` | Modo debug | true |
| `APP_URL` | URL base de la aplicación | http://localhost:8000 |
| `DB_CONNECTION` | Motor de base de datos | mysql |
| `DB_HOST` | Host de base de datos | 127.0.0.1 |
| `DB_PORT` | Puerto de base de datos | 3306 |
| `DB_DATABASE` | Nombre de base de datos | system_core_api |
| `DB_USERNAME` | Usuario de base de datos | root |
| `DB_PASSWORD` | Contraseña de base de datos | (vacío) |
| `REDIS_HOST` | Host de Redis | 127.0.0.1 |
| `REDIS_PORT` | Puerto de Redis | 6379 |
| `REDIS_PASSWORD` | Contraseña de Redis | null |
| `QUEUE_CONNECTION` | Driver de colas | redis |
| `CACHE_DRIVER` | Driver de caché | redis |
| `SESSION_DRIVER` | Driver de sesiones | redis |
| `REVERB_APP_ID` | ID de aplicación Reverb | (generado) |
| `REVERB_APP_KEY` | Llave de aplicación Reverb | (generado) |
| `REVERB_APP_SECRET` | Secreto de aplicación Reverb | (generado) |
| `REVERB_HOST` | Host de Reverb | 0.0.0.0 |
| `REVERB_PORT` | Puerto de Reverb | 8080 |
| `MAIL_MAILER` | Driver de correo | smtp |
| `MAIL_HOST` | Host SMTP | smtp.mailtrap.io |
| `MAIL_PORT` | Puerto SMTP | 2525 |

## Módulos del sistema

| Módulo | Descripción | Premium |
|--------|-------------|---------|
| **Asistencia** | Registro de entrada/salida, cálculo de horas trabajadas | No |
| **Reportes** | Generación de reportes básicos en PDF | No |
| **Dispositivos** | Gestión de lectores NFC y monitoreo | No |
| **Ausencias** | Solicitudes de permisos, vacaciones y días libres | Sí |
| **Geolocalización** | Registro de ubicación GPS en cada marcaje | Sí |
| **Reportes Avanzados** | Exportación Excel/CSV, gráficas, analíticas | Sí |
| **Notificaciones** | Alertas en tiempo real por correo y WebSocket | Sí |
| **Integración API** | Acceso completo a endpoints REST | Sí |

## Roles y permisos

### Jerarquía de roles

| Rol | Nivel | Descripción | Permisos |
|-----|-------|-------------|----------|
| **Super Admin** | 100 | Dueños del SaaS | Acceso total al sistema |
| **Director** | 90 | Máximo rol del cliente | Ve toda la empresa excepto gestión de empresas |
| **Recursos Humanos** | 85 | Gestión de personal | Empleados, asistencia, reportes, ausencias |
| **Subdirector** | 80 | Gestión de área | Ve su área asignada + asistencia + reportes |
| **Jefe de Área** | 70 | Gestión de departamento | Ve su departamento + aprobación de ausencias |
| **Operador** | 50 | Monitoreo básico | Dispositivos y visualización de asistencia |
| **Empleado** | 10 | Sin acceso al panel | Solo registro físico NFC |

### Permisos por módulo

**Empresas:** view, create, edit, delete  
**Sucursales:** view, create, edit, delete  
**Departamentos:** view, create, edit, delete  
**Empleados:** view, create, edit, delete  
**Tarjetas NFC:** view, create, edit, delete, assign, block  
**Dispositivos:** view, create, edit, delete, monitor  
**Asistencia:** view, view_own_department, view_all, manual_register  
**Reportes:** view, export_pdf, export_excel, export_csv  
**Ausencias:** view, create, approve_jefe, approve_rh, reject  
**Turnos:** view, create, edit, delete  
**Reglas:** view, create, edit, delete  
**Usuarios:** view, create, edit, delete  

## Tipos de empleado

El sistema maneja dos tipos de empleado con reglas de negocio diferenciadas:

### Empleado Base

**Características:**
- Tiene derecho a solicitar días libres, permisos y vacaciones
- Debe cumplir con horarios estrictos de entrada y salida
- Aplican penalizaciones por retardos según configuración
- Requiere aprobación de jefe de área y RH para ausencias

**Flujo de solicitud de ausencia:**
1. Empleado crea solicitud en el sistema (si módulo está activo)
2. Jefe de área aprueba o rechaza
3. Recursos Humanos valida y aprueba final
4. Sistema registra automáticamente en asistencia

### Empleado de Confianza

**Características:**
- NO tiene acceso al módulo de solicitud de ausencias
- Mayor flexibilidad en horarios
- Sin penalizaciones automáticas por retardos
- Gestión de tiempo a discreción de superiores
- Registro de asistencia obligatorio (entrada/salida)

**Nota importante:** La distinción entre tipos de empleado es validada a nivel de negocio en el backend. El campo `employee_type` en la base de datos puede ser `'base'` o `'confianza'`.

## Credenciales de prueba

Después de ejecutar los seeders, estarán disponibles los siguientes usuarios:

| Correo | Contraseña | Rol | Empresa | Acceso |
|--------|-----------|-----|---------|--------|
| super@saas.com | password | Super Admin | (ninguna) | Sistema completo |
| director@hospital.com | password | Director | Hospital Central | Toda la empresa |
| rh@hospital.com | password | Recursos Humanos | Hospital Central | Empleados y asistencia |
| jefe@hospital.com | password | Jefe de Área | Hospital Central | Su departamento |
| operador@hospital.com | password | Operador | Hospital Central | Dispositivos |

**Datos de prueba incluidos:**
- 2 empresas (Hospital Central, Empresa Demo)
- 3 sucursales
- 7 departamentos
- 3 turnos (Mañana, Tarde, Nocturno)
- 10 empleados (5 base + 5 confianza)
- 10 tarjetas NFC asignadas
- 3 dispositivos NFC activos

## Convenciones de código

### Estructura del código (inglés)

Todos los elementos estructurales del código se escriben en inglés:

- **Nombres de tablas:** `attendance_logs`, `nfc_cards`, `leave_requests`
- **Nombres de columnas:** `company_id`, `employee_type`, `created_at`
- **Clases y modelos:** `AttendanceLog`, `NfcCard`, `EmployeeType`
- **Métodos y funciones:** `canRequestLeave()`, `isOnline()`, `getFullName()`
- **Variables:** `$employeeCode`, `$recordedAt`, `$companyId`
- **Rutas API:** `/api/v1/employees`, `/api/v1/attendance`

### Datos y mensajes (español)

Todo contenido visible para el usuario final se escribe en español:

- **Valores de enumeraciones:** `'activo'`, `'inactivo'`, `'entrada'`, `'salida'`
- **Mensajes de respuesta API:** `"Empleado no encontrado"`, `"Registro exitoso"`
- **Mensajes de validación:** `"El campo nombre es obligatorio"`
- **Labels de interfaz:** `"Activo"`, `"Inactivo"`, `"Pendiente"`
- **Comentarios de código:** `// Validar que el empleado sea tipo base`
- **Documentación:** Toda la documentación técnica

### Ejemplo práctico

```php
// Correcto
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

## Pruebas automatizadas

### Ejecutar todas las pruebas

```bash
php artisan test
```

### Ejecutar pruebas específicas

```bash
php artisan test --filter=EmployeeTest
```

### Generar reporte de cobertura

```bash
php artisan test --coverage
```

## Comandos útiles

```bash
# Limpiar caché de aplicación
php artisan cache:clear

# Limpiar caché de configuración
php artisan config:clear

# Limpiar caché de rutas
php artisan route:clear

# Limpiar caché de vistas
php artisan view:clear

# Listar todas las rutas
php artisan route:list

# Monitorear colas en tiempo real
php artisan horizon

# Ver logs en tiempo real
php artisan pail

# Optimizar para producción
php artisan optimize
```

## Despliegue en producción

### Preparación

```bash
# Optimizar autoload
composer install --optimize-autoloader --no-dev

# Cachear configuración
php artisan config:cache

# Cachear rutas
php artisan route:cache

# Cachear vistas
php artisan view:cache

# Compilar assets para producción
npm run build
```

### Variables de entorno en producción

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

# Deshabilitar logs detallados
LOG_LEVEL=error

# Usar driver de sesiones apropiado
SESSION_DRIVER=redis

# Configurar correo SMTP real
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
```

## Licencia

Este proyecto es propietario y confidencial. Todos los derechos reservados.

## Autoría y créditos

**Desarrollado por:** Joshua Getzael Paz Sanchez  
**Asistencia técnica:** Claude (Anthropic)  
**Versión:** 1.0.0  
**Fecha de inicio:** Marzo 2026  

## Contacto y soporte

Para consultas técnicas o solicitudes de soporte:

- **Email:** contacto@cmf-system.com
- **Documentación:** https://docs.cmf-system.com
- **Repositorio:** (privado)

---

**Nota:** Este proyecto se encuentra en desarrollo activo. Consulte el archivo `CHANGELOG.md` para ver el historial de cambios y actualizaciones.
