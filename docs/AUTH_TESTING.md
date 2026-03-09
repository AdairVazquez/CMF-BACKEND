# Testing del Sistema de Autenticación Enterprise

Este documento contiene todos los comandos curl para probar el sistema de autenticación completo.

## Variables de entorno

```bash
# URL base de la API
API_URL="http://localhost:8000/api/v1"

# Credenciales de prueba
EMAIL_DIRECTOR="director@hospital.com"
EMAIL_RH="rh@hospital.com"
PASSWORD="password"
```

---

## 1. LOGIN BÁSICO (Sin 2FA)

### Login exitoso
```bash
curl -X POST "${API_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "director@hospital.com",
    "password": "password",
    "device_name": "Postman Chrome"
  }'
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "message": "Inicio de sesión exitoso",
  "data": {
    "token": "1|abc123...",
    "user": {
      "id": 2,
      "name": "Juan Director",
      "email": "director@hospital.com",
      "company_id": 1,
      "roles": [...]
    }
  }
}
```

### Login con credenciales incorrectas
```bash
curl -X POST "${API_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "director@hospital.com",
    "password": "wrong_password"
  }'
```

**Respuesta esperada (401):**
```json
{
  "success": false,
  "message": "Credenciales incorrectas"
}
```

### Intentos fallidos → Bloqueo de cuenta
```bash
# Repetir 5 veces con password incorrecta
for i in {1..5}; do
  curl -X POST "${API_URL}/auth/login" \
    -H "Content-Type: application/json" \
    -d '{
      "email": "director@hospital.com",
      "password": "wrong"
    }'
  echo "\nIntento $i"
done
```

**Respuesta después del 5to intento (423):**
```json
{
  "success": false,
  "message": "Cuenta bloqueada por 15 minutos debido a intentos fallidos."
}
```

---

## 2. OBTENER DATOS DEL USUARIO AUTENTICADO

```bash
# Primero obtener el token del login
TOKEN="1|abc123..."

curl -X GET "${API_URL}/auth/me" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "message": "Usuario autenticado",
  "data": {
    "user": {
      "id": 2,
      "name": "Juan Director",
      "email": "director@hospital.com",
      "company": {...},
      "roles": [...]
    }
  }
}
```

---

## 3. AUTENTICACIÓN DE DOS FACTORES (2FA)

### 3.1 Habilitar 2FA (generar QR)
```bash
curl -X POST "${API_URL}/auth/two-factor/enable" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "message": "Escanea el código QR con tu app de autenticación",
  "data": {
    "secret": "JBSWY3DPEHPK3PXP",
    "qr_code_url": "otpauth://totp/CMF%20SaaS:director@hospital.com?secret=JBSWY3DPEHPK3PXP&issuer=CMF%20SaaS"
  }
}
```

**Acción:** Escanear el QR con Google Authenticator o Authy.

### 3.2 Confirmar 2FA (activar)
```bash
# Obtener código de 6 dígitos de la app
CODE_2FA="123456"

curl -X POST "${API_URL}/auth/two-factor/confirm" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json" \
  -d '{
    "code": "'${CODE_2FA}'"
  }'
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "message": "Autenticación de dos factores activada. Guarda estos códigos de recuperación.",
  "data": {
    "recovery_codes": [
      "ABCD-EFGH-IJKL",
      "MNOP-QRST-UVWX",
      "..."
    ]
  }
}
```

**IMPORTANTE:** Guardar los códigos de recuperación en un lugar seguro.

### 3.3 Login con 2FA activado
```bash
curl -X POST "${API_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "director@hospital.com",
    "password": "password"
  }'
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "message": "Ingresa el código de autenticación de dos factores",
  "data": {
    "requires_2fa": true,
    "token": "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
  }
}
```

### 3.4 Verificar código 2FA
```bash
# Guardar el token temporal
TEMP_TOKEN="a1b2c3d4-e5f6-7890-abcd-ef1234567890"
CODE_2FA="123456"

curl -X POST "${API_URL}/auth/two-factor/verify" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "'${TEMP_TOKEN}'",
    "code": "'${CODE_2FA}'"
  }'
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "message": "Autenticación completada",
  "data": {
    "token": "2|xyz789...",
    "user": {...}
  }
}
```

### 3.5 Usar código de recuperación
```bash
# Si no tienes acceso a la app de 2FA
RECOVERY_CODE="ABCD-EFGH-IJKL"

curl -X POST "${API_URL}/auth/two-factor/recovery" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "'${TEMP_TOKEN}'",
    "recovery_code": "'${RECOVERY_CODE}'"
  }'
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "message": "Autenticación completada con código de recuperación",
  "data": {
    "token": "3|abc...",
    "user": {...},
    "remaining_codes": 7
  }
}
```

### 3.6 Desactivar 2FA
```bash
curl -X POST "${API_URL}/auth/two-factor/disable" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "password": "password"
  }'
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "message": "Autenticación de dos factores desactivada",
  "data": null
}
```

---

## 4. RECUPERACIÓN DE CONTRASEÑA

### 4.1 Solicitar código de recuperación
```bash
curl -X POST "${API_URL}/auth/forgot-password" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "director@hospital.com"
  }'
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "message": "Si el email existe, recibirás un código de recuperación.",
  "data": null
}
```

**Nota:** El código de 6 dígitos se envía por email. En desarrollo, revisar el log o usar Mailtrap.

### 4.2 Restablecer contraseña con código
```bash
# Obtener el código del email
RESET_CODE="123456"

curl -X POST "${API_URL}/auth/reset-password" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "director@hospital.com",
    "code": "'${RESET_CODE}'",
    "password": "NewPassword123!",
    "password_confirmation": "NewPassword123!"
  }'
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "message": "Contraseña actualizada correctamente",
  "data": null
}
```

**Nota:** Todas las sesiones activas se cierran automáticamente.

---

## 5. GESTIÓN DE SESIONES

### 5.1 Cerrar sesión actual
```bash
curl -X POST "${API_URL}/auth/logout" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "message": "Sesión cerrada correctamente",
  "data": null
}
```

### 5.2 Cerrar todas las sesiones
```bash
curl -X POST "${API_URL}/auth/logout-all" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "password": "password"
  }'
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "message": "Se cerraron 3 sesiones activas",
  "data": null
}
```

### 5.3 Refrescar datos del usuario
```bash
curl -X POST "${API_URL}/auth/refresh" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

**Respuesta esperada (200):**
```json
{
  "success": true,
  "message": "Datos actualizados",
  "data": {
    "user": {...}
  }
}
```

---

## 6. PRUEBAS DE SEGURIDAD

### 6.1 Acceso sin token (debe fallar)
```bash
curl -X GET "${API_URL}/auth/me" \
  -H "Accept: application/json"
```

**Respuesta esperada (401):**
```json
{
  "success": false,
  "message": "No autenticado. Inicia sesión para continuar."
}
```

### 6.2 Token inválido (debe fallar)
```bash
curl -X GET "${API_URL}/auth/me" \
  -H "Authorization: Bearer invalid_token_123" \
  -H "Accept: application/json"
```

**Respuesta esperada (401):**
```json
{
  "success": false,
  "message": "No autenticado. Inicia sesión para continuar."
}
```

### 6.3 Rate limiting (demasiadas solicitudes)
```bash
# Enviar más de 5 requests en 1 minuto
for i in {1..7}; do
  curl -X POST "${API_URL}/auth/login" \
    -H "Content-Type: application/json" \
    -d '{
      "email": "test@test.com",
      "password": "test"
    }'
  echo "\nRequest $i"
done
```

**Respuesta después del límite (429):**
```json
{
  "success": false,
  "message": "Demasiadas solicitudes. Intenta de nuevo en 60 segundos."
}
```

---

## 7. VERIFICACIÓN DE LOGS

### Ver logs de seguridad
```bash
tail -f storage/logs/security.log
```

### Eventos que se registran:
- ✅ Login exitoso
- ❌ Login fallido
- 🔒 Cuenta bloqueada
- 🔐 2FA verificado
- 🛡️ 2FA activado/desactivado
- 🔑 Recovery code usado
- 🔄 Password cambiado
- 👋 Logout

---

## 8. ESCENARIOS DE PRUEBA COMPLETOS

### Escenario 1: Usuario nuevo activa 2FA
```bash
# 1. Login inicial
TOKEN=$(curl -s -X POST "${API_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"rh@hospital.com","password":"password"}' \
  | jq -r '.data.token')

# 2. Habilitar 2FA
curl -X POST "${API_URL}/auth/two-factor/enable" \
  -H "Authorization: Bearer ${TOKEN}"

# 3. Confirmar con código (obtener de la app)
curl -X POST "${API_URL}/auth/two-factor/confirm" \
  -H "Authorization: Bearer ${TOKEN}" \
  -d '{"code":"123456"}'

# 4. Logout
curl -X POST "${API_URL}/auth/logout" \
  -H "Authorization: Bearer ${TOKEN}"

# 5. Login con 2FA
curl -X POST "${API_URL}/auth/login" \
  -d '{"email":"rh@hospital.com","password":"password"}'
```

### Escenario 2: Usuario olvida contraseña
```bash
# 1. Solicitar código
curl -X POST "${API_URL}/auth/forgot-password" \
  -d '{"email":"jefe@hospital.com"}'

# 2. Revisar email y obtener código

# 3. Restablecer contraseña
curl -X POST "${API_URL}/auth/reset-password" \
  -d '{
    "email":"jefe@hospital.com",
    "code":"123456",
    "password":"NewPass123!",
    "password_confirmation":"NewPass123!"
  }'

# 4. Login con nueva contraseña
curl -X POST "${API_URL}/auth/login" \
  -d '{"email":"jefe@hospital.com","password":"NewPass123!"}'
```

---

## 9. VALIDACIÓN DE ERRORES

### Email inválido
```bash
curl -X POST "${API_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"not-an-email","password":"test"}'
```

**Respuesta (422):**
```json
{
  "success": false,
  "message": "Los datos proporcionados no son válidos.",
  "errors": {
    "email": ["El email debe ser válido"]
  }
}
```

### Contraseña débil en reset
```bash
curl -X POST "${API_URL}/auth/reset-password" \
  -d '{
    "email":"test@test.com",
    "code":"123456",
    "password":"weak",
    "password_confirmation":"weak"
  }'
```

**Respuesta (422):**
```json
{
  "success": false,
  "message": "Los datos proporcionados no son válidos.",
  "errors": {
    "password": [
      "La contraseña debe tener al menos 8 caracteres",
      "La contraseña debe contener mayúsculas y minúsculas",
      "La contraseña debe contener números",
      "La contraseña debe contener caracteres especiales"
    ]
  }
}
```

---

## 10. HEALTH CHECK

```bash
curl -X GET "${API_URL}/system/health"
```

**Respuesta esperada (200):**
```json
{
  "status": "healthy",
  "timestamp": "2026-03-09 04:30:15",
  "services": {
    "database": "up",
    "cache": "up",
    "queue": "up"
  }
}
```

---

## Notas finales

1. **Seguridad implementada:**
   - ✅ Bloqueo de cuenta tras 5 intentos fallidos (15 min)
   - ✅ Rate limiting por endpoint
   - ✅ 2FA con TOTP (Google Authenticator)
   - ✅ Códigos de recuperación encriptados
   - ✅ Tokens de reset con expiración (15 min)
   - ✅ Nunca revela si un email existe
   - ✅ Logs de auditoría completos
   - ✅ Headers de seguridad HTTP

2. **Tokens:**
   - Token de autenticación: Laravel Sanctum
   - Token temporal 2FA: UUID (10 min en cache)
   - Token reset password: Hash en DB (15 min)

3. **Notificaciones por email:**
   - Login desde nuevo dispositivo/IP
   - Código de recuperación de contraseña
   - 2FA activado
   - Cuenta bloqueada

4. **Para producción:**
   - Configurar MAIL_* en .env
   - Usar Redis para cache y queue
   - Configurar rate limits según carga
   - Habilitar HTTPS
   - Revisar logs regularmente
