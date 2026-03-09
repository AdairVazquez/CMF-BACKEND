# Flujo 2FA por CORREO - CMF
# El codigo de 6 digitos se envia al email del usuario (no hay app tipo Google Authenticator).

$API = "http://127.0.0.1:8000/api/v1"
$EMAIL = "joshuapaz24@gmail.com"
$PASSWORD = "password"

Write-Host "`n=== FLUJO 2FA POR CORREO - CMF ===" -ForegroundColor Cyan
Write-Host "Usuario: $EMAIL`n" -ForegroundColor Gray

# --- Paso 1: Login ---
Write-Host "[1/7] Login..." -ForegroundColor Yellow
try {
    $login = Invoke-RestMethod -Uri "$API/auth/login" -Method POST -ContentType "application/json" `
        -Body "{`"email`":`"$EMAIL`",`"password`":`"$PASSWORD`",`"device_name`":`"Script 2FA`"}"
} catch {
    Write-Host "Error en login. ¿Servidor corriendo (php artisan serve)?" -ForegroundColor Red
    exit 1
}

if (-not $login.data.token) {
    Write-Host "Login fallido:" $login.message -ForegroundColor Red
    exit 1
}

$token = $login.data.token
Write-Host "  OK. Token obtenido." -ForegroundColor Green

# --- Paso 2: Habilitar 2FA (codigo en respuesta JSON) ---
Write-Host "`n[2/7] Habilitando 2FA..." -ForegroundColor Yellow
try {
    $enable = Invoke-RestMethod -Uri "$API/auth/two-factor/enable" -Method POST `
        -Headers @{Authorization = "Bearer $token"; Accept = "application/json"}
} catch {
    Write-Host "  Error:" $_.Exception.Message -ForegroundColor Red
    exit 1
}

if ($enable.data.code) {
    Write-Host "  Código 2FA (de la API):" $enable.data.code -ForegroundColor Green
    $code = $enable.data.code
} else {
    Write-Host "  Mensaje:" $enable.message -ForegroundColor Gray
    Write-Host "  Introduce el código manualmente.`n" -ForegroundColor Gray
    $code = Read-Host "  Codigo de 6 digitos"
}

# --- Paso 3: Confirmar 2FA con codigo ---
Write-Host "`n[3/7] Confirmar 2FA con codigo..." -ForegroundColor Yellow
try {
    $confirm = Invoke-RestMethod -Uri "$API/auth/two-factor/confirm" -Method POST -ContentType "application/json" `
        -Headers @{Authorization = "Bearer $token"; Accept = "application/json"} `
        -Body "{`"code`":`"$code`"}"
} catch {
    Write-Host "  Error (codigo incorrecto o expirado):" $_.Exception.Message -ForegroundColor Red
    exit 1
}

Write-Host "  2FA activado. GUARDA estos codigos de recuperacion:" -ForegroundColor Green
$confirm.data.recovery_codes | ForEach-Object { Write-Host "    $_" -ForegroundColor White }
Read-Host "`n  Pulsa Enter para continuar"

# --- Paso 4: Logout ---
Write-Host "`n[4/7] Cerrar sesion..." -ForegroundColor Yellow
Invoke-RestMethod -Uri "$API/auth/logout" -Method POST -Headers @{Authorization = "Bearer $token"; Accept = "application/json"} | Out-Null
Write-Host "  OK." -ForegroundColor Green

# --- Paso 5: Login de nuevo (codigo en respuesta JSON) ---
Write-Host "`n[5/7] Login de nuevo..." -ForegroundColor Yellow
$login2 = Invoke-RestMethod -Uri "$API/auth/login" -Method POST -ContentType "application/json" `
    -Body "{`"email`":`"$EMAIL`",`"password`":`"$PASSWORD`"}"

if (-not $login2.data.requires_2fa) {
    Write-Host "  Inesperado: no se solicito 2FA." $login2 -ForegroundColor Red
    exit 1
}

$tempToken = $login2.data.token

if ($login2.data.code_for_testing) {
    Write-Host "  Código 2FA (de la API):" $login2.data.code_for_testing -ForegroundColor Green
    $code2 = $login2.data.code_for_testing
} else {
    Write-Host "  Mensaje:" $login2.message -ForegroundColor Gray
    Write-Host "  Introduce el código manualmente.`n" -ForegroundColor Gray
    $code2 = Read-Host "  Codigo de 6 digitos"
}

# --- Paso 6: Verificar con codigo ---
Write-Host "`n[6/7] Verificar con codigo..." -ForegroundColor Yellow
try {
    $verify = Invoke-RestMethod -Uri "$API/auth/two-factor/verify" -Method POST -ContentType "application/json" `
        -Body "{`"token`":`"$tempToken`",`"code`":`"$code2`"}"
} catch {
    Write-Host "  Error:" $_.Exception.Message -ForegroundColor Red
    Write-Host "  Puedes usar un codigo de recuperacion: POST /auth/two-factor/recovery" -ForegroundColor Gray
    exit 1
}

$finalToken = $verify.data.token
Write-Host "  OK. Autenticacion completada." -ForegroundColor Green

# --- Paso 7: GET /auth/me ---
Write-Host "`n[7/7] GET /auth/me (comprobar sesion)..." -ForegroundColor Yellow
$me = Invoke-RestMethod -Uri "$API/auth/me" -Method GET -Headers @{Authorization = "Bearer $finalToken"; Accept = "application/json"}
Write-Host "  Usuario:" $me.data.user.name "| Email:" $me.data.user.email -ForegroundColor Green
$roles = ($me.data.user.roles | ForEach-Object { $_.name }) -join ", "
Write-Host "  Roles:" $roles -ForegroundColor Gray

Write-Host "`n=== Flujo 2FA por correo completado ===" -ForegroundColor Cyan
Write-Host "Logs: storage/logs/security-*.log`n" -ForegroundColor Gray
