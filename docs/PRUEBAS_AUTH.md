# Pruebas de Autenticación - CMF Backend

## Archivos creados

### 1. Core de autenticación
- ✅ `app/Traits/ApiResponse.php` - Trait para respuestas estandarizadas
- ✅ `app/Services/AuthService.php` - Lógica de negocio de autenticación
- ✅ `app/Http/Controllers/Api/V1/AuthController.php` - Controlador de autenticación
- ✅ `app/Http/Requests/Auth/LoginRequest.php` - Validación de login

### 2. Middleware
- ✅ `app/Http/Middleware/TenantScope.php` - Scope multi-tenant
- ✅ `app/Http/Middleware/CheckPermission.php` - Verificación de permisos

### 3. Configuración
- ✅ `routes/api.php` - Rutas de la API
- ✅ `bootstrap/app.php` - Configuración de excepciones y middleware
- ✅ `config/sanctum.php` - Configuración de Laravel Sanctum

---

## Configuración inicial

### 1. Ejecutar migraciones de Sanctum

```bash
php artisan migrate
```

### 2. Limpiar caché

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 3. Iniciar servidor

```bash
php artisan serve
```

El servidor estará en `http://localhost:8000`

---

## Pruebas con cURL

### 1. Login exitoso (Director)

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\": \"director@hospital.com\", \"password\": \"password\"}"
```

**Respuesta esperada:**
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
      "role": "director",
      "role_name": "Director",
      "permissions": [
        "employees.view",
        "employees.create",
        "attendance.view_all",
        "..."
      ],
      "company": {
        "id": 1,
        "name": "Hospital Central",
        "status": "Activo",
        "plan": "premium",
        "modules": [
          "asistencia",
          "reportes",
          "ausencias",
          "dispositivos"
        ]
      }
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz",
    "token_type": "Bearer"
  }
}
```

### 2. Login con credenciales incorrectas

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\": \"director@hospital.com\", \"password\": \"wrong\"}"
```

**Respuesta esperada:**
```json
{
  "success": false,
  "message": "Credenciales incorrectas",
  "errors": {
    "email": [
      "Credenciales incorrectas"
    ]
  }
}
```

### 3. Login con validación fallida

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\": \"invalidemail\", \"password\": \"123\"}"
```

**Respuesta esperada:**
```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "email": [
      "El formato del correo electrónico no es válido"
    ],
    "password": [
      "La contraseña debe tener al menos 8 caracteres"
    ]
  }
}
```

### 4. Obtener información del usuario autenticado

```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TOKEN_OBTENIDO_EN_LOGIN}"
```

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "user": {
      "id": 2,
      "name": "Director Hospital",
      "email": "director@hospital.com",
      "company_id": 1,
      "is_super_admin": false,
      "role": "director",
      "role_name": "Director",
      "permissions": ["..."],
      "company": {
        "id": 1,
        "name": "Hospital Central",
        "status": "Activo",
        "plan": "premium",
        "modules": ["asistencia", "reportes", "ausencias", "dispositivos"]
      }
    }
  }
}
```

### 5. Refrescar token

```bash
curl -X POST http://localhost:8000/api/v1/auth/refresh \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TOKEN_ACTUAL}"
```

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "Token refrescado exitosamente",
  "data": {
    "user": { "..." },
    "token": "2|nuevo_token_aqui",
    "token_type": "Bearer"
  }
}
```

### 6. Cerrar sesión

```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TOKEN}"
```

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "Sesión cerrada correctamente",
  "data": null
}
```

### 7. Acceso sin token (401)

```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Accept: application/json"
```

**Respuesta esperada:**
```json
{
  "success": false,
  "message": "No autenticado"
}
```

---

## Pruebas con Postman

### Configuración inicial

1. **Crear nueva colección:** "CMF - API Tests"
2. **Configurar variables de entorno:**
   - `base_url`: `http://localhost:8000/api/v1`
   - `token`: (se llenará automáticamente después del login)

### Request 1: Login

**Configuración:**
- **Método:** POST
- **URL:** `{{base_url}}/auth/login`
- **Headers:**
  ```
  Content-Type: application/json
  Accept: application/json
  ```
- **Body (raw JSON):**
  ```json
  {
    "email": "director@hospital.com",
    "password": "password"
  }
  ```

**Script de prueba (Tests tab):**
```javascript
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set("token", jsonData.data.token);
    pm.test("Login exitoso", function () {
        pm.expect(jsonData.success).to.eql(true);
        pm.expect(jsonData.data.token).to.exist;
    });
}
```

### Request 2: Get User Info

**Configuración:**
- **Método:** GET
- **URL:** `{{base_url}}/auth/me`
- **Headers:**
  ```
  Accept: application/json
  ```
- **Authorization:**
  - Type: Bearer Token
  - Token: `{{token}}`

**Script de prueba:**
```javascript
pm.test("Usuario autenticado", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.success).to.eql(true);
    pm.expect(jsonData.data.user.email).to.exist;
});
```

### Request 3: Refresh Token

**Configuración:**
- **Método:** POST
- **URL:** `{{base_url}}/auth/refresh`
- **Headers:**
  ```
  Accept: application/json
  ```
- **Authorization:**
  - Type: Bearer Token
  - Token: `{{token}}`

**Script de prueba:**
```javascript
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set("token", jsonData.data.token);
    pm.test("Token refrescado", function () {
        pm.expect(jsonData.success).to.eql(true);
    });
}
```

### Request 4: Logout

**Configuración:**
- **Método:** POST
- **URL:** `{{base_url}}/auth/logout`
- **Headers:**
  ```
  Accept: application/json
  ```
- **Authorization:**
  - Type: Bearer Token
  - Token: `{{token}}`

**Script de prueba:**
```javascript
pm.test("Logout exitoso", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.success).to.eql(true);
    pm.expect(jsonData.message).to.include("cerrada");
});
```

---

## Usuarios disponibles para pruebas

| Email | Password | Rol | Empresa |
|-------|----------|-----|---------|
| super@saas.com | password | Super Admin | (ninguna) |
| director@hospital.com | password | Director | Hospital Central |
| rh@hospital.com | password | Recursos Humanos | Hospital Central |
| jefe@hospital.com | password | Jefe de Área | Hospital Central |
| operador@hospital.com | password | Operador | Hospital Central |

---

## Validaciones implementadas

### 1. Login Request
- ✅ Email requerido y formato válido
- ✅ Password requerido (mínimo 8 caracteres)
- ✅ Verificación de credenciales
- ✅ Usuario activo
- ✅ Empresa activa (excepto super admin)

### 2. Tenant Scope
- ✅ Super admin ve todas las empresas
- ✅ Usuarios regulares solo ven su empresa
- ✅ company_id inyectado en request

### 3. Permisos
- ✅ Verificación por slug de permiso
- ✅ Super admin tiene todos los permisos
- ✅ Respuesta 403 si sin permisos

---

## Manejo de errores

| Código | Mensaje | Caso |
|--------|---------|------|
| 401 | No autenticado | Token inválido o ausente |
| 403 | Sin permisos para realizar esta acción | Usuario sin permiso requerido |
| 404 | Registro no encontrado | Modelo no existe |
| 422 | Error de validación | Datos inválidos |
| 429 | Demasiadas solicitudes | Rate limit excedido |
| 500 | Error interno del servidor | Error no controlado |

---

## Rate Limiting

**Configurado en `bootstrap/app.php`:**

- Login: 5 intentos por minuto por IP (pendiente configurar)
- API general: 60 peticiones por minuto por usuario autenticado
- NFC register: 30 peticiones por minuto por device_id (pendiente)

Para configurar rate limiting personalizado, editar `app/Providers/RouteServiceProvider.php` o usar throttle en rutas específicas.

---

## Próximos pasos

1. ✅ Autenticación implementada
2. 🔲 CRUD de Empresas con middleware de permisos
3. 🔲 CRUD de Empleados
4. 🔲 Endpoint de registro NFC
5. 🔲 Sistema de reportes
6. 🔲 WebSockets con Laravel Reverb

---

## Troubleshooting

### Error: "No autenticado" en todas las peticiones

**Solución:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Error: "Class not found"

**Solución:**
```bash
composer dump-autoload
```

### Error: CORS

**Solución:**
Instalar Laravel CORS:
```bash
php artisan config:publish cors
```

Editar `config/cors.php` para permitir tu dominio frontend.

### Error: Token no se guarda en Postman

**Solución:**
Verificar que el script en la pestaña "Tests" esté guardando el token:
```javascript
pm.environment.set("token", jsonData.data.token);
```

---

**Desarrollado por:** Joshua Getzael Paz Sanchez  
**Asistencia técnica:** Claude (Anthropic)  
**Versión:** 1.0.0  
**Fecha:** Marzo 2026
