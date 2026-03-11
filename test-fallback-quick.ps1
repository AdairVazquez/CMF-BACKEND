# TEST RAPIDO DE FALLBACK
# Prueba solo el fallback sin queue worker

param(
    [string]$Email = "joshuapaz24@gmail.com",
    [string]$Password = "password"
)

$BaseUrl = "http://127.0.0.1:8000/api/v1"

Write-Host ""
Write-Host "=== TEST RAPIDO: FALLBACK SIN QUEUE ===" -ForegroundColor Cyan
Write-Host ""

# Verificar que NO haya queue worker corriendo
$workers = Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object {
    $_.CommandLine -like "*queue:work*"
}

if ($workers) {
    Write-Host "ALERTA: Queue worker esta corriendo (PID: $($workers.Id))" -ForegroundColor Yellow
    Write-Host "Deteniendo..." -ForegroundColor Yellow
    Stop-Process -Id $workers.Id -Force
    Start-Sleep -Seconds 2
}

# Verificar health
Write-Host "[1/6] Verificando estado de cola..." -ForegroundColor Gray
$healthResult = php artisan queue:health 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "OK: Queue worker NO esta corriendo (esperado para test)" -ForegroundColor Green
} else {
    Write-Host "ERROR: Queue worker ESTA corriendo. Detenlo manualmente." -ForegroundColor Red
    exit 1
}

# Reset user
Write-Host "[2/6] Reseteando usuario..." -ForegroundColor Gray
php artisan user:reset-2fa $Email | Out-Null

# Login
Write-Host "[3/6] Login..." -ForegroundColor Gray
$loginBody = @{
    email = $Email
    password = $Password
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "$BaseUrl/auth/login" -Method POST -Body $loginBody -ContentType "application/json"
    
    $token = $loginResponse.data.token
    Write-Host "OK: Token obtenido" -ForegroundColor Green
    
    # Enable 2FA
    Write-Host "[4/6] Habilitando 2FA (sin queue)..." -ForegroundColor Gray
    $startTime = Get-Date
    
    $enable2FAResponse = Invoke-RestMethod -Uri "$BaseUrl/auth/two-factor/enable" -Method POST -Headers @{ Authorization = "Bearer $token" }
    
    $elapsedTime = (Get-Date) - $startTime
    
    Write-Host "OK: Solicitud completada en $([math]::Round($elapsedTime.TotalSeconds, 2)) segundos" -ForegroundColor Green
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Yellow
    Write-Host " REVISA TU EMAIL AHORA" -ForegroundColor Yellow
    Write-Host "========================================" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Email: $Email" -ForegroundColor White
    Write-Host "Debe llegar en 10-20 segundos (via SYNC)" -ForegroundColor White
    Write-Host ""
    
    # Esperar confirmacion
    Write-Host "[5/6] Llego el email? (s/n): " -ForegroundColor Gray -NoNewline
    $arrived = Read-Host
    
    if ($arrived -eq "s") {
        Write-Host "OK: EMAIL LLEGO SIN QUEUE WORKER" -ForegroundColor Green
        Write-Host "OK: FALLBACK AUTOMATICO FUNCIONO" -ForegroundColor Green
        
        # Confirmar 2FA
        Write-Host ""
        Write-Host "[6/6] Codigo de 6 digitos: " -ForegroundColor Gray -NoNewline
        $code = Read-Host
        
        $confirmBody = @{ code = $code } | ConvertTo-Json
        
        $confirmResponse = Invoke-RestMethod -Uri "$BaseUrl/auth/two-factor/confirm" -Method POST -Headers @{ Authorization = "Bearer $token" } -Body $confirmBody -ContentType "application/json"
        
        Write-Host "OK: 2FA activado correctamente" -ForegroundColor Green
        Write-Host ""
        Write-Host "========================================" -ForegroundColor Green
        Write-Host " TEST EXITOSO" -ForegroundColor Green
        Write-Host "========================================" -ForegroundColor Green
        Write-Host ""
        Write-Host "Codigos de recuperacion:" -ForegroundColor Cyan
        foreach ($recoveryCode in $confirmResponse.data.recovery_codes) {
            Write-Host "  $recoveryCode" -ForegroundColor White
        }
        
    } else {
        Write-Host "ERROR: Email NO llego" -ForegroundColor Red
        Write-Host ""
        Write-Host "Verifica:" -ForegroundColor Yellow
        Write-Host "  1. Logs: storage/logs/mail-*.log" -ForegroundColor White
        Write-Host "  2. Gmail App Password correcto en .env" -ForegroundColor White
        Write-Host "  3. Carpeta de spam" -ForegroundColor White
    }
    
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
