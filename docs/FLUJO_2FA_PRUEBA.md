# Prueba del flujo 2FA por correo

El segundo factor es un **código de 6 dígitos enviado al correo** del usuario. No se usa Google Authenticator ni ninguna app.

**Requisitos:**
- Servidor Laravel en marcha: `php artisan serve`
- Correo configurado (ya probado con `php artisan mail:test`)

**URL base:** `http://127.0.0.1:8000/api/v1`

---

## Resumen del flujo

```
1. Login (email + password)           → Token de sesión
2. Habilitar 2FA                      → Te envían un código al correo
3. Confirmar 2FA con ese código       → 2FA activado + códigos de recuperación (guardar)
4. Logout
5. Login de nuevo                     → Te envían otro código al correo + token temporal (UUID)
6. Verificar con el código del correo → Token final de sesión
   O usar código de recuperación si no tienes acceso al correo
7. (Opcional) Desactivar 2FA          → Con contraseña
```

---

## Paso 1: Login inicial

```powershell
$login = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/login" `
  -Method POST -ContentType "application/json" `
  -Body '{"email":"joshuapaz24@gmail.com","password":"password","device_name":"Prueba 2FA"}'

$token = $login.data.token
```

---

## Paso 2: Habilitar 2FA

Se envía un código de 6 dígitos a tu correo (válido 5 minutos).

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/two-factor/enable" `
  -Method POST -Headers @{Authorization="Bearer $token"; Accept="application/json"}
```

**Respuesta:** `"Revisa tu correo e ingresa el código de 6 dígitos para activar 2FA"`.  
Abre el correo y copia el código.

---

## Paso 3: Confirmar 2FA

Introduce el código que llegó al correo.

```powershell
$code = Read-Host "Código de 6 dígitos del correo"
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/two-factor/confirm" `
  -Method POST -ContentType "application/json" `
  -Headers @{Authorization="Bearer $token"; Accept="application/json"} `
  -Body "{`"code`":`"$code`"}"
```

Guarda los **8 códigos de recuperación** que devuelve (formato `XXXX-XXXX-XXXX`). Sirven si no tienes acceso al correo.

---

## Paso 4: Logout

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/logout" `
  -Method POST -Headers @{Authorization="Bearer $token"; Accept="application/json"}
```

---

## Paso 5: Login de nuevo

Al hacer login, se envía **otro código** a tu correo y la API devuelve `requires_2fa` y un token temporal.

```powershell
$login2 = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/login" `
  -Method POST -ContentType "application/json" `
  -Body '{"email":"joshuapaz24@gmail.com","password":"password"}'

$tempToken = $login2.data.token
```

Revisa el correo; el código vale **10 minutos**.

---

## Paso 6: Verificar con el código del correo

```powershell
$code2 = Read-Host "Código de 6 dígitos del correo"
$verify = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/two-factor/verify" `
  -Method POST -ContentType "application/json" `
  -Body "{`"token`":`"$tempToken`",`"code`":`"$code2`"}"

$finalToken = $verify.data.token
```

Con `$finalToken` ya puedes llamar a `GET /auth/me`, etc.

### Si no tienes el correo: usar código de recuperación

```powershell
$recovery = Read-Host "Código de recuperación (XXXX-XXXX-XXXX)"
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/two-factor/recovery" `
  -Method POST -ContentType "application/json" `
  -Body "{`"token`":`"$tempToken`",`"recovery_code`":`"$recovery`"}"
```

---

## Paso 7 (opcional): Desactivar 2FA

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/two-factor/disable" `
  -Method POST -ContentType "application/json" `
  -Headers @{Authorization="Bearer $finalToken"; Accept="application/json"} `
  -Body '{"password":"password"}'
```

---

## Script automático

En la raíz del proyecto:

```powershell
.\test-2fa-flow.ps1
```

Te pide los dos códigos (al activar 2FA y al hacer el segundo login); el resto es automático. Revisa el correo cuando el script lo indique.

---

## Errores frecuentes

| Mensaje | Causa |
|--------|--------|
| Código incorrecto o expirado | Código equivocado o pasaron más de 5/10 min. Pide uno nuevo (enable o login otra vez). |
| Token expirado o inválido | El token temporal (UUID) pasó de 10 min. Vuelve a hacer login. |
| Código de recuperación inválido | Ya usado, mal escrito o formato incorrecto (`XXXX-XXXX-XXXX`). |

Logs: `storage/logs/security-*.log` y `storage/logs/mail-*.log`.
