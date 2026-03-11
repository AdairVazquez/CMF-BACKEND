# Script de prueba del sistema de autenticación
# CMF - Control y Monitoreo de Fuerza Laboral

$API_URL = "http://127.0.0.1:8000/api/v1"

Write-Host "=== PRUEBAS DEL SISTEMA DE AUTENTICACIÓN ===" -ForegroundColor Cyan
Write-Host ""

# ===========================
# 1. LOGIN BÁSICO
# ===========================
Write-Host "1. Probando login básico..." -ForegroundColor Yellow
try {
    $loginResponse = Invoke-RestMethod -Uri "$API_URL/auth/login" -Method POST -ContentType "application/json" -Body '{"email":"director@hospital.com","password":"password","device_name":"PowerShell Test"}'
    
    Write-Host "✓ Login exitoso" -ForegroundColor Green
    Write-Host "  Token: $($loginResponse.data.token.Substring(0,20))..." -ForegroundColor Gray
    Write-Host "  Usuario: $($loginResponse.data.user.name)" -ForegroundColor Gray
    $token = $loginResponse.data.token
}
catch {
    Write-Host "✗ Error en login" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit
}
Write-Host ""

# ===========================
# 2. OBTENER DATOS DEL USUARIO
# ===========================
Write-Host "2. Obteniendo datos del usuario autenticado..." -ForegroundColor Yellow
try {
    $meResponse = Invoke-RestMethod -Uri "$API_URL/auth/me" -Method GET -Headers @{Authorization = "Bearer $token"; Accept = "application/json"}
    
    Write-Host "✓ Datos obtenidos" -ForegroundColor Green
    Write-Host "  Usuario: $($meResponse.data.user.name)" -ForegroundColor Gray
    Write-Host "  Email: $($meResponse.data.user.email)" -ForegroundColor Gray
    Write-Host "  Empresa ID: $($meResponse.data.user.company_id)" -ForegroundColor Gray
    Write-Host "  Roles: $($meResponse.data.user.roles.Count)" -ForegroundColor Gray
}
catch {
    Write-Host "✗ Error al obtener datos" -ForegroundColor Red
}
Write-Host ""

# ===========================
# 3. HABILITAR 2FA
# ===========================
Write-Host "3. Habilitando 2FA..." -ForegroundColor Yellow
try {
    $enable2faResponse = Invoke-RestMethod -Uri "$API_URL/auth/two-factor/enable" -Method POST -Headers @{Authorization = "Bearer $token"; Accept = "application/json"}
    
    Write-Host "✓ 2FA habilitado" -ForegroundColor Green
    Write-Host "  Secret: $($enable2faResponse.data.secret)" -ForegroundColor Gray
    Write-Host "  QR URL: $($enable2faResponse.data.qr_code_url.Substring(0,50))..." -ForegroundColor Gray
}
catch {
    Write-Host "✗ Error al habilitar 2FA" -ForegroundColor Red
}
Write-Host ""

# ===========================
# 4. REFRESH TOKEN
# ===========================
Write-Host "4. Refrescando datos..." -ForegroundColor Yellow
try {
    $refreshResponse = Invoke-RestMethod -Uri "$API_URL/auth/refresh" -Method POST -Headers @{Authorization = "Bearer $token"; Accept = "application/json"}
    
    Write-Host "✓ Datos refrescados" -ForegroundColor Green
}
catch {
    Write-Host "✗ Error al refrescar" -ForegroundColor Red
}
Write-Host ""

# ===========================
# 5. LOGOUT
# ===========================
Write-Host "5. Cerrando sesión..." -ForegroundColor Yellow
try {
    $logoutResponse = Invoke-RestMethod -Uri "$API_URL/auth/logout" -Method POST -Headers @{Authorization = "Bearer $token"; Accept = "application/json"}
    
    Write-Host "✓ Sesión cerrada" -ForegroundColor Green
    Write-Host "  Mensaje: $($logoutResponse.message)" -ForegroundColor Gray
}
catch {
    Write-Host "✗ Error al cerrar sesión" -ForegroundColor Red
}
Write-Host ""

# ===========================
# 6. INTENTOS FALLIDOS
# ===========================
Write-Host "6. Probando intentos fallidos (3 intentos)..." -ForegroundColor Yellow
$failedCount = 0
for ($i = 1; $i -le 3; $i++) {
    try {
        Invoke-RestMethod -Uri "$API_URL/auth/login" -Method POST -ContentType "application/json" -Body '{"email":"operador@hospital.com","password":"wrongpassword"}' -ErrorAction Stop | Out-Null
    }
    catch {
        $failedCount++
        Write-Host "  Intento $i fallido (esperado)" -ForegroundColor Gray
    }
}
Write-Host "✓ $failedCount intentos fallidos registrados" -ForegroundColor Green
Write-Host ""

# ===========================
# 7. FORGOT PASSWORD
# ===========================
Write-Host "7. Solicitando recuperación de contraseña..." -ForegroundColor Yellow
try {
    $forgotResponse = Invoke-RestMethod -Uri "$API_URL/auth/forgot-password" -Method POST -ContentType "application/json" -Body '{"email":"director@hospital.com"}'
    
    Write-Host "✓ Solicitud enviada" -ForegroundColor Green
    Write-Host "  Mensaje: $($forgotResponse.message)" -ForegroundColor Gray
}
catch {
    Write-Host "✗ Error al solicitar recuperación" -ForegroundColor Red
}
Write-Host ""

# ===========================
# 8. ACCESO SIN TOKEN (debe fallar)
# ===========================
Write-Host "8. Intentando acceso sin token (debe fallar)..." -ForegroundColor Yellow
try {
    Invoke-RestMethod -Uri "$API_URL/auth/me" -Method GET -Headers @{Accept = "application/json"} -ErrorAction Stop | Out-Null
    Write-Host "✗ ERROR: Permitió acceso sin token" -ForegroundColor Red
}
catch {
    Write-Host "✓ Acceso denegado correctamente (401)" -ForegroundColor Green
}
Write-Host ""

# ===========================
# 9. HEALTH CHECK
# ===========================
Write-Host "9. Verificando salud del sistema..." -ForegroundColor Yellow
try {
    $healthResponse = Invoke-RestMethod -Uri "$API_URL/system/health" -Method GET
    
    Write-Host "✓ Sistema saludable" -ForegroundColor Green
    Write-Host "  Status: $($healthResponse.status)" -ForegroundColor Gray
    Write-Host "  Database: $($healthResponse.services.database)" -ForegroundColor Gray
    Write-Host "  Cache: $($healthResponse.services.cache)" -ForegroundColor Gray
}
catch {
    Write-Host "✗ Error en health check" -ForegroundColor Red
}
Write-Host ""

Write-Host "=== PRUEBAS COMPLETADAS ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Revisa los logs en: storage/logs/security-$(Get-Date -Format 'yyyy-MM-dd').log" -ForegroundColor Yellow
