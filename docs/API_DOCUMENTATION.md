# Documentación de la API REST - CMF Backend

Sistema de Control y Monitoreo de Fuerza Laboral - Especificación completa de endpoints y contratos de la API RESTful.

## Información general

- **Base URL:** `/api/v1`
- **Autenticación:** Bearer Token (Laravel Sanctum)
- **Formato de datos:** JSON
- **Charset:** UTF-8
- **Versionado:** v1 (actual)
- **Rate limiting:** 60 peticiones por minuto por usuario autenticado

## Autenticación

Todos los endpoints (excepto `/api/v1/auth/login` y `/api/v1/nfc/register`) requieren autenticación mediante Bearer Token en el header:

```http
Authorization: Bearer {token}
```

El token se obtiene mediante el endpoint de login y tiene una vigencia configurada en el archivo `.env`.

## Formato estándar de respuestas

### Respuesta exitosa

```json
{
  "success": true,
  "message": "Operación completada exitosamente",
  "data": {
    "id": 1,
    "name": "Ejemplo"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

### Respuesta de error

```json
{
  "success": false,
  "message": "Mensaje descriptivo del error en español",
  "errors": {
    "field_name": [
      "El campo es obligatorio"
    ]
  }
}
```

### Respuesta de error de autenticación

```json
{
  "success": false,
  "message": "No autenticado"
}
```

## Códigos de respuesta HTTP

| Código | Significado | Uso en la API |
|--------|-------------|---------------|
| 200 | OK | Petición exitosa con datos |
| 201 | Created | Recurso creado exitosamente |
| 204 | No Content | Petición exitosa sin contenido (DELETE) |
| 400 | Bad Request | Datos de entrada inválidos |
| 401 | Unauthorized | Token inválido o ausente |
| 403 | Forbidden | Sin permisos para ejecutar la acción |
| 404 | Not Found | Recurso no encontrado |
| 409 | Conflict | Conflicto con estado actual (ej: UID duplicado) |
| 422 | Unprocessable Entity | Error de validación |
| 429 | Too Many Requests | Límite de peticiones excedido |
| 500 | Internal Server Error | Error del servidor |

---

## 1. Autenticación

### POST /api/v1/auth/login

Iniciar sesión y obtener token de acceso.

**Headers:**
```http
Content-Type: application/json
Accept: application/json
```

**Body:**
```json
{
  "email": "director@hospital.com",
  "password": "password"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Inicio de sesión exitoso",
  "data": {
    "user": {
      "id": 2,
      "name": "Director Hospital",
      "email": "director@hospital.com",
      "company_id": 1,
      "is_super_admin": false,
      "is_active": true,
      "roles": [
        {
          "id": 2,
          "name": "Director",
          "slug": "director"
        }
      ]
    },
    "token": "1|laravel_sanctum_token_string_here"
  }
}
```

**Respuesta de error (401):**
```json
{
  "success": false,
  "message": "Credenciales incorrectas"
}
```

**Roles con acceso:** Público (sin autenticación)

---

### POST /api/v1/auth/logout

Cerrar sesión y revocar token actual.

**Headers:**
```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Sesión cerrada exitosamente"
}
```

**Roles con acceso:** Todos los usuarios autenticados

---

### GET /api/v1/auth/me

Obtener información del usuario autenticado.

**Headers:**
```http
Authorization: Bearer {token}
Accept: application/json
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Director Hospital",
    "email": "director@hospital.com",
    "company_id": 1,
    "company": {
      "id": 1,
      "name": "Hospital Central",
      "status": "activo"
    },
    "roles": [
      {
        "id": 2,
        "name": "Director",
        "slug": "director",
        "permissions": [
          "employees.view",
          "employees.create",
          "attendance.view_all"
        ]
      }
    ]
  }
}
```

**Roles con acceso:** Todos los usuarios autenticados

---

## 2. Empresas

### GET /api/v1/companies

Listar todas las empresas (solo Super Admin).

**Headers:**
```http
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**
- `page` (opcional): Número de página (default: 1)
- `per_page` (opcional): Elementos por página (default: 15)
- `status` (opcional): Filtrar por estado (`activo`, `inactivo`, `suspendido`, `prueba`)
- `search` (opcional): Buscar por nombre o email

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Hospital Central",
      "email": "admin@hospitalcentral.com",
      "plan": "premium",
      "status": "activo",
      "timezone": "America/Mexico_City",
      "subscription_ends_at": "2027-03-08",
      "modules": [
        "asistencia",
        "reportes",
        "ausencias",
        "dispositivos"
      ],
      "branches_count": 3,
      "employees_count": 10,
      "created_at": "2026-03-08T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 2,
    "per_page": 15
  }
}
```

**Roles con acceso:** Super Admin

---

### POST /api/v1/companies

Crear nueva empresa.

**Headers:**
```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Body:**
```json
{
  "name": "Nueva Empresa S.A.",
  "legal_name": "Nueva Empresa Sociedad Anónima",
  "tax_id": "NEX123456ABC",
  "email": "admin@nuevaempresa.com",
  "phone": "+52 33 1234 5678",
  "address": "Calle Principal 123",
  "plan": "basic",
  "timezone": "America/Mexico_City"
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "message": "Empresa creada exitosamente",
  "data": {
    "id": 3,
    "name": "Nueva Empresa S.A.",
    "status": "prueba",
    "trial_ends_at": "2026-04-07"
  }
}
```

**Respuesta de error (422):**
```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "email": [
      "El correo electrónico ya está registrado"
    ],
    "tax_id": [
      "El RFC ya existe en el sistema"
    ]
  }
}
```

**Roles con acceso:** Super Admin

---

### GET /api/v1/companies/{id}

Obtener detalles de una empresa específica.

**Headers:**
```http
Authorization: Bearer {token}
Accept: application/json
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Hospital Central",
    "legal_name": "Hospital Central S.A. de C.V.",
    "tax_id": "HCE980123ABC",
    "email": "admin@hospitalcentral.com",
    "phone": "+52 55 1234 5678",
    "address": "Av. Reforma 123, CDMX",
    "plan": "premium",
    "status": "activo",
    "timezone": "America/Mexico_City",
    "subscription_ends_at": "2027-03-08",
    "branches": [
      {
        "id": 1,
        "name": "Edificio Principal",
        "code": "EP"
      }
    ],
    "active_modules": [
      {
        "module_name": "asistencia",
        "is_active": true,
        "activated_at": "2026-03-08"
      }
    ]
  }
}
```

**Roles con acceso:** Super Admin, Director

---

### PUT /api/v1/companies/{id}

Actualizar información de una empresa.

**Headers:**
```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Body:**
```json
{
  "name": "Hospital Central Actualizado",
  "phone": "+52 55 9999 8888",
  "plan": "enterprise",
  "status": "activo"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Empresa actualizada exitosamente",
  "data": {
    "id": 1,
    "name": "Hospital Central Actualizado",
    "updated_at": "2026-03-08T15:30:00.000000Z"
  }
}
```

**Roles con acceso:** Super Admin, Director

---

### DELETE /api/v1/companies/{id}

Eliminar una empresa (soft delete).

**Headers:**
```http
Authorization: Bearer {token}
Accept: application/json
```

**Respuesta exitosa (204):**
```
(Sin contenido)
```

**Respuesta de error (409):**
```json
{
  "success": false,
  "message": "No se puede eliminar la empresa porque tiene registros relacionados"
}
```

**Roles con acceso:** Super Admin

---

## 3. Sucursales

### GET /api/v1/branches

Listar sucursales de la empresa del usuario autenticado.

**Headers:**
```http
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**
- `page` (opcional)
- `per_page` (opcional)
- `is_active` (opcional): `true` o `false`

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "company_id": 1,
      "name": "Edificio Principal",
      "code": "EP",
      "address": "Av. Reforma 123, CDMX",
      "city": "Ciudad de México",
      "state": "CDMX",
      "postal_code": "06600",
      "phone": "+52 55 1234 5678",
      "is_active": true,
      "departments_count": 5,
      "devices_count": 2
    }
  ]
}
```

**Roles con acceso:** Super Admin, Director, Subdirector, RH, Jefe de Área

---

### POST /api/v1/branches

Crear nueva sucursal.

**Headers:**
```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Body:**
```json
{
  "name": "Sucursal Norte",
  "code": "SN",
  "address": "Av. Norte 456",
  "city": "Monterrey",
  "state": "Nuevo León",
  "postal_code": "64000",
  "phone": "+52 81 1234 5678"
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "message": "Sucursal creada exitosamente",
  "data": {
    "id": 4,
    "name": "Sucursal Norte",
    "code": "SN",
    "is_active": true
  }
}
```

**Roles con acceso:** Super Admin, Director

---

### GET /api/v1/branches/{id}

Obtener detalles de una sucursal.

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Edificio Principal",
    "code": "EP",
    "address": "Av. Reforma 123, CDMX",
    "departments": [
      {
        "id": 1,
        "name": "Urgencias",
        "employees_count": 15
      }
    ],
    "devices": [
      {
        "id": 1,
        "name": "Lector Entrada Principal",
        "status": "activo"
      }
    ]
  }
}
```

**Roles con acceso:** Super Admin, Director, Subdirector, RH

---

### PUT /api/v1/branches/{id}

Actualizar información de sucursal.

**Body:**
```json
{
  "name": "Edificio Principal - Actualizado",
  "phone": "+52 55 9999 0000",
  "is_active": true
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Sucursal actualizada exitosamente",
  "data": {
    "id": 1,
    "name": "Edificio Principal - Actualizado"
  }
}
```

**Roles con acceso:** Super Admin, Director

---

### DELETE /api/v1/branches/{id}

Eliminar una sucursal.

**Respuesta exitosa (204):**
```
(Sin contenido)
```

**Respuesta de error (409):**
```json
{
  "success": false,
  "message": "No se puede eliminar la sucursal porque tiene empleados asignados"
}
```

**Roles con acceso:** Super Admin, Director

---

## 4. Departamentos

### GET /api/v1/departments

Listar departamentos.

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Urgencias",
      "code": "URG",
      "branch": {
        "id": 1,
        "name": "Edificio Principal"
      },
      "employees_count": 15,
      "is_active": true
    }
  ]
}
```

**Roles con acceso:** Super Admin, Director, Subdirector, RH, Jefe de Área

---

### POST /api/v1/departments

Crear nuevo departamento.

**Body:**
```json
{
  "name": "Cardiología",
  "code": "CARD",
  "description": "Departamento de cardiología",
  "branch_id": 1
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "message": "Departamento creado exitosamente",
  "data": {
    "id": 8,
    "name": "Cardiología",
    "code": "CARD"
  }
}
```

**Roles con acceso:** Super Admin, Director, RH

---

### GET /api/v1/departments/{id}

Obtener detalles de un departamento.

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Urgencias",
    "code": "URG",
    "description": "Departamento de urgencias médicas",
    "branch": {
      "id": 1,
      "name": "Edificio Principal"
    },
    "employees": [
      {
        "id": 5,
        "full_name": "Carlos Jiménez Torres",
        "employee_code": "EMP1004",
        "position": "Camillero"
      }
    ]
  }
}
```

**Roles con acceso:** Super Admin, Director, Subdirector, RH, Jefe de Área

---

### PUT /api/v1/departments/{id}

Actualizar departamento.

**Body:**
```json
{
  "name": "Urgencias - Turno 24hrs",
  "is_active": true
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Departamento actualizado exitosamente"
}
```

**Roles con acceso:** Super Admin, Director, RH

---

### DELETE /api/v1/departments/{id}

Eliminar departamento.

**Respuesta exitosa (204):**
```
(Sin contenido)
```

**Roles con acceso:** Super Admin, Director

---

## 5. Empleados

### GET /api/v1/employees

Listar empleados según permisos del usuario.

**Query Parameters:**
- `page`, `per_page`
- `status`: `activo`, `inactivo`, `baja`, `suspendido`
- `employee_type`: `base`, `confianza`
- `department_id`: Filtrar por departamento
- `search`: Buscar por nombre o código

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "employee_code": "EMP1000",
      "full_name": "Juan García López",
      "first_name": "Juan",
      "last_name": "García López",
      "email": "juan.garcia@hospital.com",
      "phone": "+52 55 5001 0001",
      "employee_type": "base",
      "status": "activo",
      "position": "Enfermero",
      "hire_date": "2024-03-08",
      "department": {
        "id": 4,
        "name": "Enfermería"
      },
      "shift": {
        "id": 1,
        "name": "Turno Mañana"
      },
      "nfc_card": {
        "id": 1,
        "card_uid": "AB12F893",
        "status": "activa"
      }
    }
  ]
}
```

**Roles con acceso:** Super Admin, Director, RH, Subdirector, Jefe de Área

---

### POST /api/v1/employees

Crear nuevo empleado.

**Body:**
```json
{
  "employee_code": "EMP1010",
  "first_name": "María",
  "last_name": "González Pérez",
  "email": "maria.gonzalez@hospital.com",
  "phone": "+52 55 5011 0011",
  "employee_type": "base",
  "branch_id": 1,
  "department_id": 4,
  "shift_id": 1,
  "position": "Enfermera",
  "hire_date": "2026-03-08"
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "message": "Empleado creado exitosamente",
  "data": {
    "id": 11,
    "employee_code": "EMP1010",
    "full_name": "María González Pérez",
    "status": "activo"
  }
}
```

**Respuesta de error (422):**
```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "employee_code": [
      "El código de empleado ya está registrado"
    ],
    "email": [
      "El correo electrónico ya está en uso"
    ]
  }
}
```

**Roles con acceso:** Super Admin, Director, RH

---

### GET /api/v1/employees/{id}

Obtener detalles completos de un empleado.

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "employee_code": "EMP1000",
    "full_name": "Juan García López",
    "employee_type": "base",
    "status": "activo",
    "hire_date": "2024-03-08",
    "can_request_leave": true,
    "attendance_summary_today": {
      "entry": "2026-03-08T08:15:00Z",
      "exit": "2026-03-08T16:30:00Z",
      "hours_worked": 8.25
    },
    "attendance_summary_month": {
      "days_worked": 20,
      "total_hours": 165.5,
      "tardiness_count": 2
    }
  }
}
```

**Roles con acceso:** Super Admin, Director, RH, Subdirector, Jefe de Área

---

### PUT /api/v1/employees/{id}

Actualizar información de empleado.

**Body:**
```json
{
  "position": "Enfermero Jefe",
  "employee_type": "confianza",
  "department_id": 4,
  "shift_id": 1,
  "status": "activo"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Empleado actualizado exitosamente",
  "data": {
    "id": 1,
    "position": "Enfermero Jefe",
    "employee_type": "confianza"
  }
}
```

**Roles con acceso:** Super Admin, Director, RH

---

### DELETE /api/v1/employees/{id}

Dar de baja a un empleado (soft delete).

**Respuesta exitosa (204):**
```
(Sin contenido)
```

**Roles con acceso:** Super Admin, Director, RH

---

## 6. Tarjetas NFC

### GET /api/v1/nfc-cards

Listar tarjetas NFC.

**Query Parameters:**
- `status`: `activa`, `inactiva`, `bloqueada`, `perdida`
- `assigned`: `true` (asignadas) o `false` (sin asignar)

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "card_uid": "AB12F893",
      "status": "activa",
      "issued_at": "2025-09-08",
      "expires_at": null,
      "employee": {
        "id": 1,
        "full_name": "Juan García López",
        "employee_code": "EMP1000"
      }
    }
  ]
}
```

**Roles con acceso:** Super Admin, Director, RH, Operador

---

### POST /api/v1/nfc-cards

Registrar nueva tarjeta NFC.

**Body:**
```json
{
  "card_uid": "1A2B3C4D",
  "issued_at": "2026-03-08",
  "expires_at": "2027-03-08",
  "notes": "Tarjeta de respaldo"
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "message": "Tarjeta NFC registrada exitosamente",
  "data": {
    "id": 11,
    "card_uid": "1A2B3C4D",
    "status": "activa"
  }
}
```

**Respuesta de error (409):**
```json
{
  "success": false,
  "message": "El UID de la tarjeta ya está registrado en el sistema"
}
```

**Roles con acceso:** Super Admin, Director, RH

---

### GET /api/v1/nfc-cards/{id}

Obtener detalles de una tarjeta.

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "card_uid": "AB12F893",
    "status": "activa",
    "is_available": false,
    "employee": {
      "id": 1,
      "full_name": "Juan García López"
    },
    "usage_stats": {
      "total_scans": 245,
      "last_scan_at": "2026-03-08T08:15:00Z"
    }
  }
}
```

**Roles con acceso:** Super Admin, Director, RH, Operador

---

### PUT /api/v1/nfc-cards/{id}

Actualizar información de tarjeta.

**Body:**
```json
{
  "status": "activa",
  "expires_at": "2028-03-08",
  "notes": "Renovada"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Tarjeta NFC actualizada exitosamente"
}
```

**Roles con acceso:** Super Admin, Director, RH

---

### DELETE /api/v1/nfc-cards/{id}

Eliminar tarjeta (soft delete).

**Respuesta exitosa (204):**
```
(Sin contenido)
```

**Roles con acceso:** Super Admin, Director, RH

---

### POST /api/v1/nfc-cards/{id}/assign

Asignar tarjeta a un empleado.

**Body:**
```json
{
  "employee_id": 5
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Tarjeta asignada exitosamente",
  "data": {
    "card_uid": "AB12F893",
    "employee": {
      "id": 5,
      "full_name": "Carlos Jiménez Torres"
    }
  }
}
```

**Respuesta de error (409):**
```json
{
  "success": false,
  "message": "El empleado ya tiene una tarjeta asignada"
}
```

**Roles con acceso:** Super Admin, Director, RH

---

### POST /api/v1/nfc-cards/{id}/block

Bloquear una tarjeta.

**Body:**
```json
{
  "reason": "Tarjeta extraviada"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Tarjeta bloqueada exitosamente",
  "data": {
    "card_uid": "AB12F893",
    "status": "bloqueada"
  }
}
```

**Roles con acceso:** Super Admin, Director, RH

---

## 7. Dispositivos

### GET /api/v1/devices

Listar dispositivos NFC.

**Query Parameters:**
- `status`: `activo`, `inactivo`, `mantenimiento`
- `branch_id`: Filtrar por sucursal

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "device_code": "DEV001-NFC",
      "name": "Lector Entrada Principal",
      "location": "Entrada principal - Planta baja",
      "status": "activo",
      "is_online": true,
      "ip_address": "192.168.1.101",
      "last_ping_at": "2026-03-08T10:20:00Z",
      "branch": {
        "id": 1,
        "name": "Edificio Principal"
      }
    }
  ]
}
```

**Roles con acceso:** Super Admin, Director, RH, Operador

---

### POST /api/v1/devices

Registrar nuevo dispositivo.

**Body:**
```json
{
  "device_code": "DEV004-NFC",
  "name": "Lector Estacionamiento",
  "location": "Estacionamiento nivel 1",
  "branch_id": 1,
  "ip_address": "192.168.1.104",
  "mac_address": "00:1B:44:11:3A:C1",
  "config": {
    "read_mode": "nfc",
    "sound_enabled": true
  }
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "message": "Dispositivo registrado exitosamente",
  "data": {
    "id": 4,
    "device_code": "DEV004-NFC",
    "status": "activo"
  }
}
```

**Roles con acceso:** Super Admin, Director, Operador

---

### GET /api/v1/devices/{id}

Obtener detalles de un dispositivo.

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "device_code": "DEV001-NFC",
    "name": "Lector Entrada Principal",
    "status": "activo",
    "is_online": true,
    "last_ping_at": "2026-03-08T10:20:00Z",
    "stats": {
      "scans_today": 85,
      "scans_this_month": 1523,
      "last_scan_at": "2026-03-08T10:15:00Z"
    }
  }
}
```

**Roles con acceso:** Super Admin, Director, RH, Operador

---

### PUT /api/v1/devices/{id}

Actualizar dispositivo.

**Body:**
```json
{
  "name": "Lector Entrada Principal - Actualizado",
  "status": "activo",
  "location": "Entrada principal - Planta baja zona A"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Dispositivo actualizado exitosamente"
}
```

**Roles con acceso:** Super Admin, Director, Operador

---

### DELETE /api/v1/devices/{id}

Eliminar dispositivo.

**Respuesta exitosa (204):**
```
(Sin contenido)
```

**Roles con acceso:** Super Admin, Director

---

## 8. Asistencia

### GET /api/v1/attendance

Listar registros de asistencia.

**Query Parameters:**
- `date`: Fecha específica (YYYY-MM-DD)
- `start_date`, `end_date`: Rango de fechas
- `employee_id`: Filtrar por empleado
- `department_id`: Filtrar por departamento
- `type`: `entrada` o `salida`

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "employee": {
        "id": 1,
        "full_name": "Juan García López",
        "employee_code": "EMP1000"
      },
      "type": "entrada",
      "recorded_at": "2026-03-08T08:15:00Z",
      "is_manual": false,
      "device": {
        "id": 1,
        "name": "Lector Entrada Principal"
      },
      "latitude": 19.432608,
      "longitude": -99.133209
    }
  ]
}
```

**Roles con acceso:** Super Admin, Director, RH, Subdirector, Jefe de Área, Operador

---

### GET /api/v1/attendance/{employee_id}

Obtener asistencia de un empleado específico.

**Query Parameters:**
- `month`: Mes (1-12)
- `year`: Año (YYYY)

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "employee": {
      "id": 1,
      "full_name": "Juan García López"
    },
    "summary": {
      "days_worked": 20,
      "total_hours": 165.5,
      "average_entry_time": "08:10:00",
      "tardiness_count": 2,
      "absences": 1
    },
    "records": [
      {
        "date": "2026-03-08",
        "entry": "08:15:00",
        "exit": "16:30:00",
        "hours_worked": 8.25,
        "is_late": true
      }
    ]
  }
}
```

**Roles con acceso:** Super Admin, Director, RH, Subdirector, Jefe de Área

---

### POST /api/v1/attendance/manual

Registrar asistencia manualmente.

**Body:**
```json
{
  "employee_id": 5,
  "type": "entrada",
  "recorded_at": "2026-03-08T08:30:00",
  "notes": "Registro manual por falla en dispositivo"
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "message": "Asistencia registrada exitosamente",
  "data": {
    "id": 150,
    "employee": {
      "id": 5,
      "full_name": "Carlos Jiménez Torres"
    },
    "type": "entrada",
    "recorded_at": "2026-03-08T08:30:00Z",
    "is_manual": true
  }
}
```

**Roles con acceso:** Super Admin, Director, RH

---

### GET /api/v1/attendance/today

Obtener registros de asistencia del día actual.

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "date": "2026-03-08",
    "summary": {
      "total_employees": 10,
      "present": 9,
      "absent": 1,
      "late": 2
    },
    "records": [
      {
        "employee": {
          "id": 1,
          "full_name": "Juan García López"
        },
        "entry_time": "08:15:00",
        "status": "presente",
        "is_late": true
      }
    ]
  }
}
```

**Roles con acceso:** Super Admin, Director, RH, Subdirector, Jefe de Área, Operador

---

## 9. Registro NFC (Endpoint público)

### POST /api/v1/nfc/register

Registrar marcaje desde dispositivo NFC.

**Headers:**
```http
Content-Type: application/json
Accept: application/json
X-Device-Key: {device_secret_key}
```

**Body:**
```json
{
  "device_code": "DEV001-NFC",
  "card_uid": "AB12F893",
  "timestamp": "2026-03-08T10:22:31",
  "latitude": 19.432608,
  "longitude": -99.133209
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "message": "Registro exitoso",
  "data": {
    "employee": {
      "full_name": "Juan García López",
      "employee_code": "EMP1000"
    },
    "type": "entrada",
    "recorded_at": "2026-03-08T10:22:31Z",
    "status": "puntual"
  }
}
```

**Respuesta de error (404):**
```json
{
  "success": false,
  "message": "Tarjeta no encontrada o inactiva"
}
```

**Respuesta de error (403):**
```json
{
  "success": false,
  "message": "Empleado inactivo o suspendido"
}
```

**Roles con acceso:** Público (con autenticación de dispositivo mediante X-Device-Key)

---

## 10. Solicitudes de ausencia

### GET /api/v1/leave-requests

Listar solicitudes de ausencia.

**Query Parameters:**
- `status`: `pendiente`, `aprobado_jefe`, `aprobado_rh`, `rechazado`
- `employee_id`: Filtrar por empleado
- `start_date`, `end_date`: Rango de fechas

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "employee": {
        "id": 1,
        "full_name": "Juan García López"
      },
      "leave_type": "vacaciones",
      "start_date": "2026-03-15",
      "end_date": "2026-03-20",
      "days_requested": 4,
      "status": "pendiente",
      "reason": "Vacaciones familiares",
      "created_at": "2026-03-08T09:00:00Z"
    }
  ]
}
```

**Roles con acceso:** Super Admin, Director, RH, Jefe de Área

---

### POST /api/v1/leave-requests

Crear solicitud de ausencia (solo empleados base).

**Body:**
```json
{
  "leave_type": "vacaciones",
  "start_date": "2026-03-15",
  "end_date": "2026-03-20",
  "reason": "Vacaciones familiares programadas"
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "message": "Solicitud de ausencia creada exitosamente",
  "data": {
    "id": 2,
    "status": "pendiente",
    "days_requested": 4
  }
}
```

**Respuesta de error (403):**
```json
{
  "success": false,
  "message": "Los empleados de confianza no pueden solicitar ausencias"
}
```

**Respuesta de error (409):**
```json
{
  "success": false,
  "message": "Ya existe una solicitud para este rango de fechas"
}
```

**Roles con acceso:** Empleados autenticados (tipo base)

---

### GET /api/v1/leave-requests/{id}

Obtener detalles de una solicitud.

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "employee": {
      "id": 1,
      "full_name": "Juan García López",
      "employee_type": "base"
    },
    "leave_type": "vacaciones",
    "start_date": "2026-03-15",
    "end_date": "2026-03-20",
    "days_requested": 4,
    "status": "aprobado_jefe",
    "reason": "Vacaciones familiares",
    "approved_by_manager": {
      "id": 4,
      "name": "Jefe de Urgencias",
      "approved_at": "2026-03-08T14:30:00Z"
    }
  }
}
```

**Roles con acceso:** Super Admin, Director, RH, Jefe de Área (solo de su departamento)

---

### PUT /api/v1/leave-requests/{id}/approve-chief

Aprobar solicitud como jefe de área.

**Body:**
```json
{
  "notes": "Aprobado"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Solicitud aprobada por jefe de área",
  "data": {
    "id": 1,
    "status": "aprobado_jefe"
  }
}
```

**Roles con acceso:** Jefe de Área

---

### PUT /api/v1/leave-requests/{id}/approve-hr

Aprobar solicitud como recursos humanos.

**Body:**
```json
{
  "notes": "Aprobado y registrado"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Solicitud aprobada por recursos humanos",
  "data": {
    "id": 1,
    "status": "aprobado_rh"
  }
}
```

**Roles con acceso:** RH

---

### PUT /api/v1/leave-requests/{id}/reject

Rechazar solicitud.

**Body:**
```json
{
  "rejection_reason": "Conflicto con calendario de vacaciones del departamento"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Solicitud rechazada",
  "data": {
    "id": 1,
    "status": "rechazado"
  }
}
```

**Roles con acceso:** Jefe de Área, RH

---

## 11. Reportes

### GET /api/v1/reports/attendance/daily

Reporte de asistencia diaria.

**Query Parameters:**
- `date`: Fecha (YYYY-MM-DD) default: hoy
- `department_id` (opcional)

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "date": "2026-03-08",
    "summary": {
      "total_employees": 10,
      "present": 9,
      "absent": 1,
      "late": 2,
      "on_leave": 0
    },
    "details": [
      {
        "employee": {
          "id": 1,
          "full_name": "Juan García López"
        },
        "entry": "08:15:00",
        "exit": "16:30:00",
        "hours_worked": 8.25,
        "status": "puntual"
      }
    ]
  }
}
```

**Roles con acceso:** Super Admin, Director, RH, Subdirector, Jefe de Área

---

### GET /api/v1/reports/attendance/monthly

Reporte de asistencia mensual.

**Query Parameters:**
- `month`: Mes (1-12) default: mes actual
- `year`: Año (YYYY) default: año actual
- `department_id` (opcional)

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "period": "Marzo 2026",
    "summary": {
      "total_employees": 10,
      "average_attendance": 98.5,
      "total_hours_worked": 1680,
      "total_tardiness": 15,
      "total_absences": 3
    },
    "by_employee": [
      {
        "employee": {
          "id": 1,
          "full_name": "Juan García López"
        },
        "days_worked": 20,
        "total_hours": 165.5,
        "tardiness_count": 2,
        "absences": 0
      }
    ]
  }
}
```

**Roles con acceso:** Super Admin, Director, RH, Subdirector, Jefe de Área

---

### GET /api/v1/reports/tardiness

Reporte de retardos.

**Query Parameters:**
- `start_date`, `end_date`: Rango de fechas
- `department_id` (opcional)

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "period": "2026-03-01 a 2026-03-08",
    "total_tardiness": 15,
    "by_employee": [
      {
        "employee": {
          "id": 1,
          "full_name": "Juan García López"
        },
        "tardiness_count": 2,
        "total_minutes_late": 35,
        "records": [
          {
            "date": "2026-03-05",
            "scheduled_entry": "08:00:00",
            "actual_entry": "08:15:00",
            "minutes_late": 15
          }
        ]
      }
    ]
  }
}
```

**Roles con acceso:** Super Admin, Director, RH, Subdirector, Jefe de Área

---

### GET /api/v1/reports/absences

Reporte de ausencias.

**Query Parameters:**
- `start_date`, `end_date`: Rango de fechas
- `department_id` (opcional)

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "period": "2026-03-01 a 2026-03-08",
    "total_absences": 5,
    "by_type": {
      "sin_registro": 3,
      "vacaciones_aprobadas": 2
    },
    "details": [
      {
        "employee": {
          "id": 3,
          "full_name": "Pedro Martínez Hernández"
        },
        "date": "2026-03-06",
        "type": "sin_registro",
        "notes": null
      }
    ]
  }
}
```

**Roles con acceso:** Super Admin, Director, RH, Subdirector, Jefe de Área

---

### GET /api/v1/reports/hours-worked

Reporte de horas trabajadas.

**Query Parameters:**
- `start_date`, `end_date`: Rango de fechas
- `employee_id` (opcional)

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "period": "2026-03-01 a 2026-03-08",
    "total_hours": 1350.5,
    "by_employee": [
      {
        "employee": {
          "id": 1,
          "full_name": "Juan García López"
        },
        "total_hours": 66.5,
        "regular_hours": 64.0,
        "overtime_hours": 2.5,
        "days_worked": 8
      }
    ]
  }
}
```

**Roles con acceso:** Super Admin, Director, RH, Subdirector, Jefe de Área

---

### POST /api/v1/reports/export/pdf

Exportar reporte en formato PDF.

**Body:**
```json
{
  "report_type": "attendance_monthly",
  "month": 3,
  "year": 2026,
  "department_id": 1
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Reporte generado exitosamente",
  "data": {
    "download_url": "/storage/reports/asistencia_marzo_2026.pdf",
    "expires_at": "2026-03-09T10:00:00Z"
  }
}
```

**Roles con acceso:** Super Admin, Director, RH, Subdirector, Jefe de Área

---

### POST /api/v1/reports/export/excel

Exportar reporte en formato Excel.

**Body:**
```json
{
  "report_type": "attendance_monthly",
  "month": 3,
  "year": 2026
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Reporte generado exitosamente",
  "data": {
    "download_url": "/storage/reports/asistencia_marzo_2026.xlsx",
    "expires_at": "2026-03-09T10:00:00Z"
  }
}
```

**Roles con acceso:** Super Admin, Director, RH (requiere módulo premium)

---

### POST /api/v1/reports/export/csv

Exportar reporte en formato CSV.

**Body:**
```json
{
  "report_type": "tardiness",
  "start_date": "2026-03-01",
  "end_date": "2026-03-08"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Reporte generado exitosamente",
  "data": {
    "download_url": "/storage/reports/retardos_marzo_2026.csv",
    "expires_at": "2026-03-09T10:00:00Z"
  }
}
```

**Roles con acceso:** Super Admin, Director, RH (requiere módulo premium)

---

## Manejo de errores comunes

### Error de autenticación

```json
{
  "success": false,
  "message": "No autenticado"
}
```

**Causa:** Token ausente, inválido o expirado.  
**Solución:** Obtener nuevo token mediante `/api/v1/auth/login`.

---

### Error de permisos

```json
{
  "success": false,
  "message": "No tiene permisos para realizar esta acción"
}
```

**Causa:** El usuario autenticado no tiene el rol o permiso requerido.  
**Solución:** Verificar que el rol del usuario tiene el permiso necesario.

---

### Error de validación

```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "email": [
      "El correo electrónico ya está registrado"
    ],
    "card_uid": [
      "El campo UID es obligatorio"
    ]
  }
}
```

**Causa:** Datos de entrada no cumplen las reglas de validación.  
**Solución:** Revisar el objeto `errors` y corregir los campos indicados.

---

### Recurso no encontrado

```json
{
  "success": false,
  "message": "Empleado no encontrado"
}
```

**Causa:** El ID del recurso no existe o fue eliminado.  
**Solución:** Verificar que el ID es correcto y el recurso existe.

---

### Conflicto de integridad

```json
{
  "success": false,
  "message": "No se puede eliminar la empresa porque tiene registros relacionados"
}
```

**Causa:** Intento de eliminar un registro que tiene dependencias.  
**Solución:** Eliminar primero los registros dependientes o usar soft delete.

---

### UID duplicado

```json
{
  "success": false,
  "message": "El UID de la tarjeta ya está registrado en el sistema"
}
```

**Causa:** Intento de registrar una tarjeta con UID que ya existe.  
**Solución:** Verificar que el UID es único o actualizar la tarjeta existente.

---

### Empleado de confianza

```json
{
  "success": false,
  "message": "Los empleados de confianza no pueden solicitar ausencias"
}
```

**Causa:** Empleado tipo "confianza" intentando crear solicitud de ausencia.  
**Solución:** Solo empleados tipo "base" pueden usar este endpoint.

---

### Módulo desactivado

```json
{
  "success": false,
  "message": "El módulo de ausencias no está activado para esta empresa"
}
```

**Causa:** Funcionalidad premium no habilitada para la empresa.  
**Solución:** Contactar administrador para activar el módulo.

---

## Ejemplos de uso con datos reales del seeder

### Ejemplo 1: Login como Director

**Request:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "director@hospital.com",
    "password": "password"
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Inicio de sesión exitoso",
  "data": {
    "user": {
      "id": 2,
      "name": "Director Hospital",
      "email": "director@hospital.com",
      "company_id": 1
    },
    "token": "1|abcd1234efgh5678ijkl"
  }
}
```

---

### Ejemplo 2: Listar empleados

**Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/employees?employee_type=base" \
  -H "Authorization: Bearer 1|abcd1234efgh5678ijkl" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "employee_code": "EMP1000",
      "full_name": "Juan García López",
      "employee_type": "base",
      "status": "activo",
      "department": {
        "name": "Enfermería"
      }
    }
  ]
}
```

---

### Ejemplo 3: Registro NFC desde dispositivo

**Request:**
```bash
curl -X POST http://localhost:8000/api/v1/nfc/register \
  -H "Content-Type: application/json" \
  -H "X-Device-Key: device_secret_key_here" \
  -d '{
    "device_code": "DEV001-NFC",
    "card_uid": "AB12F893",
    "timestamp": "2026-03-08T08:15:00"
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Registro exitoso",
  "data": {
    "employee": {
      "full_name": "Juan García López",
      "employee_code": "EMP1000"
    },
    "type": "entrada",
    "status": "puntual"
  }
}
```

---

## Autoría

**Desarrollado por:** Joshua Getzael Paz Sanchez  
**Asistencia técnica:** Claude (Anthropic)  
**Versión:** 1.0.0  
**Fecha:** Marzo 2026  

---

**Nota:** Esta documentación se actualiza constantemente. Consulte el changelog para ver las últimas modificaciones en la API.
