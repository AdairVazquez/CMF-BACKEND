# Script de prueba rapida para autenticacion
# Ejecutar en PowerShell

Write-Host "=== CMF - Pruebas de Autenticacion ===" -ForegroundColor Cyan
Write-Host ""

$baseUrl = "http://localhost:8000/api/v1"

# Test 1: Login exitoso
Write-Host "1. Probando login con credenciales correctas..." -ForegroundColor Yellow
$loginBody = @{
    email = "director@hospital.com"
    password = "password"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/auth/login" `
        -Method Post `
        -ContentType "application/json" `
        -Body $loginBody

    if ($response.success) {
        Write-Host "   [OK] Login exitoso" -ForegroundColor Green
        Write-Host "   Usuario: $($response.data.user.name)" -ForegroundColor White
        Write-Host "   Rol: $($response.data.user.role_name)" -ForegroundColor White
        Write-Host "   Empresa: $($response.data.user.company.name)" -ForegroundColor White
        
        $token = $response.data.token
        Write-Host "   Token obtenido: $($token.Substring(0, 20))..." -ForegroundColor Gray
    }
} catch {
    Write-Host "   [ERROR] Error en login" -ForegroundColor Red
    Write-Host "   $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 2: Obtener info del usuario
if ($token) {
    Write-Host "2. Probando endpoint /auth/me..." -ForegroundColor Yellow
    
    try {
        $headers = @{
            "Authorization" = "Bearer $token"
            "Accept" = "application/json"
        }
        
        $response = Invoke-RestMethod -Uri "$baseUrl/auth/me" `
            -Method Get `
            -Headers $headers
        
        if ($response.success) {
            Write-Host "   [OK] Usuario autenticado correctamente" -ForegroundColor Green
            Write-Host "   Email: $($response.data.user.email)" -ForegroundColor White
            Write-Host "   Permisos: $($response.data.user.permissions.Count) permisos cargados" -ForegroundColor White
        }
    } catch {
        Write-Host "   [ERROR] Error al obtener usuario" -ForegroundColor Red
    }
}

Write-Host ""

# Test 3: Login con credenciales incorrectas
Write-Host "3. Probando login con credenciales incorrectas..." -ForegroundColor Yellow
$wrongLoginBody = @{
    email = "director@hospital.com"
    password = "wrongpassword"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/auth/login" `
        -Method Post `
        -ContentType "application/json" `
        -Body $wrongLoginBody
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    if ($statusCode -eq 401) {
        Write-Host "   [OK] Rechazo correctamente (401 Unauthorized)" -ForegroundColor Green
    } else {
        Write-Host "   [ERROR] Codigo de error inesperado: $statusCode" -ForegroundColor Red
    }
}

Write-Host ""

# Test 4: Acceso sin token
Write-Host "4. Probando acceso sin token..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/auth/me" `
        -Method Get `
        -Headers @{"Accept" = "application/json"}
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    if ($statusCode -eq 401) {
        Write-Host "   [OK] Rechazo correctamente (401 Unauthorized)" -ForegroundColor Green
    } else {
        Write-Host "   [ERROR] Codigo de error inesperado: $statusCode" -ForegroundColor Red
    }
}

Write-Host ""

# Test 5: Logout
if ($token) {
    Write-Host "5. Probando logout..." -ForegroundColor Yellow
    
    try {
        $headers = @{
            "Authorization" = "Bearer $token"
            "Accept" = "application/json"
        }
        
        $response = Invoke-RestMethod -Uri "$baseUrl/auth/logout" `
            -Method Post `
            -Headers $headers
        
        if ($response.success) {
            Write-Host "   [OK] Logout exitoso" -ForegroundColor Green
            Write-Host "   Mensaje: $($response.message)" -ForegroundColor White
        }
    } catch {
        Write-Host "   [ERROR] Error en logout" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "=== Pruebas completadas ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Para mas pruebas detalladas, consulta PRUEBAS_AUTH.md" -ForegroundColor Gray
