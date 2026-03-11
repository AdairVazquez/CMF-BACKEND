# TEST DE RESILIENCIA 2FA - TODOS LOS ESCENARIOS
# Este script prueba el sistema 2FA con y sin queue worker

param(
    [string]$Email = "joshuapaz24@gmail.com",
    [string]$Password = "password"
)

$BaseUrl = "http://127.0.0.1:8000/api/v1"
$TestResults = @()

function Write-TestHeader($Text) {
    Write-Host "`n========================================" -ForegroundColor Cyan
    Write-Host " $Text" -ForegroundColor Cyan
    Write-Host "========================================`n" -ForegroundColor Cyan
}

function Write-Success($Text) {
    Write-Host "✓ $Text" -ForegroundColor Green
}

function Write-Fail($Text) {
    Write-Host "✗ $Text" -ForegroundColor Red
}

function Write-Info($Text) {
    Write-Host "ℹ $Text" -ForegroundColor Yellow
}

function Get-QueueWorkerProcess {
    return Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object {
        $_.CommandLine -like "*queue:work*"
    }
}

function Stop-QueueWorker {
    $workers = Get-QueueWorkerProcess
    if ($workers) {
        Write-Info "Deteniendo queue worker (PID: $($workers.Id))..."
        Stop-Process -Id $workers.Id -Force
        Start-Sleep -Seconds 2
        return $true
    }
    return $false
}

function Start-QueueWorkerBackground {
    Write-Info "Iniciando queue worker en background..."
    $job = Start-Job -ScriptBlock {
        Set-Location $using:PWD
        php artisan queue:work --tries=3 --timeout=90
    }
    Start-Sleep -Seconds 3
    return $job
}

function Check-QueueHealth {
    $result = php artisan queue:health 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Success "Queue worker funcionando"
        return $true
    } else {
        Write-Fail "Queue worker caído"
        return $false
    }
}

function Reset-User($Email) {
    Write-Info "Reseteando 2FA del usuario..."
    php artisan user:reset-2fa $Email | Out-Null
    Start-Sleep -Seconds 1
}

function Test-2FAFlow($ScenarioName, $ExpectEmail = $true) {
    Write-TestHeader "ESCENARIO: $ScenarioName"
    
    $result = @{
        Scenario = $ScenarioName
        Success = $false
        EmailTime = 0
        Error = $null
    }
    
    try {
        # 1. Login
        Write-Info "[1/5] Login inicial..."
        $loginBody = @{
            email = $Email
            password = $Password
        } | ConvertTo-Json
        
        $loginResponse = Invoke-RestMethod -Uri "$BaseUrl/auth/login" `
            -Method POST `
            -Body $loginBody `
            -ContentType "application/json" `
            -ErrorAction Stop
        
        $token = $loginResponse.data.token
        Write-Success "Token obtenido: $($token.Substring(0,20))..."
        
        # 2. Habilitar 2FA
        Write-Info "[2/5] Habilitando 2FA..."
        $startTime = Get-Date
        
        $enable2FAResponse = Invoke-RestMethod -Uri "$BaseUrl/auth/two-factor/enable" `
            -Method POST `
            -Headers @{ Authorization = "Bearer $token" } `
            -ErrorAction Stop
        
        Write-Success $enable2FAResponse.message
        
        # 3. Esperar email
        Write-Info "[3/5] Esperando email con código..."
        Write-Host "    Tienes 60 segundos para revisar tu correo." -ForegroundColor Yellow
        Write-Host "    Email debe llegar a: $Email" -ForegroundColor Yellow
        
        $emailArrived = Read-Host "`n    ¿Llegó el email? (s/n)"
        $emailTime = (Get-Date) - $startTime
        
        if ($emailArrived -eq "s") {
            Write-Success "Email llegó en $([math]::Round($emailTime.TotalSeconds)) segundos"
            $result.EmailTime = [math]::Round($emailTime.TotalSeconds)
            
            # 4. Confirmar 2FA
            Write-Info "[4/5] Ingresa el código de 6 dígitos del email..."
            $code = Read-Host "    Código"
            
            $confirmBody = @{ code = $code } | ConvertTo-Json
            
            $confirmResponse = Invoke-RestMethod -Uri "$BaseUrl/auth/two-factor/confirm" `
                -Method POST `
                -Headers @{ Authorization = "Bearer $token" } `
                -Body $confirmBody `
                -ContentType "application/json" `
                -ErrorAction Stop
            
            Write-Success "2FA activado correctamente"
            Write-Host "`n    Códigos de recuperación:" -ForegroundColor Cyan
            foreach ($recoveryCode in $confirmResponse.data.recovery_codes) {
                Write-Host "      $recoveryCode" -ForegroundColor White
            }
            
            # 5. Logout y re-login con 2FA
            Write-Info "`n[5/5] Probando login con 2FA..."
            
            Invoke-RestMethod -Uri "$BaseUrl/auth/logout" `
                -Method POST `
                -Headers @{ Authorization = "Bearer $token" } `
                -ErrorAction Stop | Out-Null
            
            Start-Sleep -Seconds 1
            
            $reLoginResponse = Invoke-RestMethod -Uri "$BaseUrl/auth/login" `
                -Method POST `
                -Body $loginBody `
                -ContentType "application/json" `
                -ErrorAction Stop
            
            if ($reLoginResponse.data.requires_2fa) {
                Write-Success "Sistema solicitó 2FA correctamente"
                
                Write-Info "Esperando segundo email con código de login..."
                $emailArrived2 = Read-Host "    ¿Llegó el segundo email? (s/n)"
                
                if ($emailArrived2 -eq "s") {
                    Write-Success "Segundo email llegó"
                    
                    $code2 = Read-Host "    Código de login"
                    
                    $verify2FABody = @{
                        token = $reLoginResponse.data.token
                        code = $code2
                    } | ConvertTo-Json
                    
                    $verifyResponse = Invoke-RestMethod -Uri "$BaseUrl/auth/two-factor/verify" `
                        -Method POST `
                        -Body $verify2FABody `
                        -ContentType "application/json" `
                        -ErrorAction Stop
                    
                    Write-Success "Login con 2FA completado"
                    $result.Success = $true
                } else {
                    Write-Fail "Segundo email NO llegó"
                    $result.Error = "Email de login no llegó"
                }
            } else {
                Write-Fail "Sistema NO solicitó 2FA"
                $result.Error = "2FA no requerido en login"
            }
            
        } else {
            Write-Fail "Email NO llegó en tiempo esperado"
            $result.Error = "Email no llegó"
        }
        
    } catch {
        Write-Fail "Error: $($_.Exception.Message)"
        $result.Error = $_.Exception.Message
    }
    
    return $result
}

# ==========================================
# INICIO DEL TEST
# ==========================================

Write-TestHeader "TEST DE RESILIENCIA 2FA"
Write-Host "Email: $Email" -ForegroundColor White
Write-Host "Este test probará el sistema en múltiples escenarios.`n"

# Verificar que el servidor esté corriendo
try {
    $healthCheck = Invoke-RestMethod -Uri "$BaseUrl/../system/health" -ErrorAction Stop
    Write-Success "Servidor Laravel corriendo"
} catch {
    Write-Fail "Servidor NO está corriendo. Ejecuta: php artisan serve"
    exit 1
}

# ==========================================
# ESCENARIO 1: CON QUEUE WORKER FUNCIONANDO
# ==========================================

Write-TestHeader "PREPARANDO ESCENARIO 1"
Stop-QueueWorker | Out-Null
$queueJob = Start-QueueWorkerBackground
Start-Sleep -Seconds 2
Reset-User $Email

if (Check-QueueHealth) {
    $result1 = Test-2FAFlow "1️⃣  CON QUEUE WORKER (Normal)"
    $TestResults += $result1
} else {
    Write-Fail "No se pudo iniciar queue worker para Escenario 1"
}

# Limpiar
if ($queueJob) { 
    Stop-Job -Job $queueJob -ErrorAction SilentlyContinue
    Remove-Job -Job $queueJob -Force -ErrorAction SilentlyContinue
}
Stop-QueueWorker | Out-Null

# ==========================================
# ESCENARIO 2: SIN QUEUE WORKER (FALLBACK)
# ==========================================

Write-TestHeader "PREPARANDO ESCENARIO 2"
Write-Info "Asegurando que NO haya queue worker..."
Stop-QueueWorker | Out-Null
Start-Sleep -Seconds 2
Reset-User $Email

if (-not (Check-QueueHealth)) {
    Write-Success "Queue worker detenido correctamente"
    $result2 = Test-2FAFlow "2️⃣  SIN QUEUE WORKER (Fallback Sync)"
    $TestResults += $result2
} else {
    Write-Fail "Queue worker sigue corriendo, detenlo manualmente"
}

# ==========================================
# ESCENARIO 3: QUEUE SE CAE A MITAD
# ==========================================

Write-TestHeader "PREPARANDO ESCENARIO 3"
$queueJob = Start-QueueWorkerBackground
Start-Sleep -Seconds 2
Reset-User $Email

if (Check-QueueHealth) {
    Write-Info "Queue worker iniciado. Se detendrá después del primer email..."
    
    Write-TestHeader "ESCENARIO: 3️⃣  QUEUE SE CAE A MITAD"
    
    # Login y habilitar 2FA
    Write-Info "[1/3] Login y habilitar 2FA..."
    $loginBody = @{
        email = $Email
        password = $Password
    } | ConvertTo-Json
    
    $loginResponse = Invoke-RestMethod -Uri "$BaseUrl/auth/login" -Method POST -Body $loginBody -ContentType "application/json"
    $token = $loginResponse.data.token
    
    Invoke-RestMethod -Uri "$BaseUrl/auth/two-factor/enable" -Method POST -Headers @{ Authorization = "Bearer $token" } | Out-Null
    
    Write-Info "[2/3] Esperando primer email..."
    $emailArrived = Read-Host "    ¿Llegó el email? (s/n)"
    
    if ($emailArrived -eq "s") {
        Write-Success "Primer email llegó con queue funcionando"
        
        $code = Read-Host "    Código"
        $confirmBody = @{ code = $code } | ConvertTo-Json
        Invoke-RestMethod -Uri "$BaseUrl/auth/two-factor/confirm" -Method POST -Headers @{ Authorization = "Bearer $token" } -Body $confirmBody -ContentType "application/json" | Out-Null
        
        Write-Success "2FA activado"
        
        # AHORA MATAR EL QUEUE WORKER
        Write-Info "`n[3/3] DETENIENDO QUEUE WORKER ahora..."
        if ($queueJob) { 
            Stop-Job -Job $queueJob -ErrorAction SilentlyContinue
            Remove-Job -Job $queueJob -Force -ErrorAction SilentlyContinue
        }
        Stop-QueueWorker | Out-Null
        Start-Sleep -Seconds 3
        
        Write-Success "Queue worker detenido. Sistema debe usar fallback ahora."
        
        # Logout y re-login
        Invoke-RestMethod -Uri "$BaseUrl/auth/logout" -Method POST -Headers @{ Authorization = "Bearer $token" } | Out-Null
        
        $reLoginResponse = Invoke-RestMethod -Uri "$BaseUrl/auth/login" -Method POST -Body $loginBody -ContentType "application/json"
        
        Write-Info "Esperando segundo email (debe llegar vía SYNC fallback)..."
        $emailArrived2 = Read-Host "    ¿Llegó el segundo email? (s/n)"
        
        if ($emailArrived2 -eq "s") {
            Write-Success "Email llegó vía FALLBACK automático ✓"
            $result3 = @{
                Scenario = "3️⃣  QUEUE SE CAE A MITAD"
                Success = $true
                EmailTime = 0
                Error = $null
            }
        } else {
            Write-Fail "Email NO llegó después de queue caído"
            $result3 = @{
                Scenario = "3️⃣  QUEUE SE CAE A MITAD"
                Success = $false
                EmailTime = 0
                Error = "Fallback no funcionó"
            }
        }
        $TestResults += $result3
    }
}
}

# ==========================================
# RESUMEN FINAL
# ==========================================

Write-TestHeader "RESUMEN DE TESTS"

$table = $TestResults | ForEach-Object {
    $timeStr = if ($_.EmailTime -gt 0) { "{0}s" -f $_.EmailTime } else { "N/A" }
    $errorStr = if ($_.Error) { $_.Error } else { "-" }
    $statusStr = if ($_.Success) { "✓ ÉXITO" } else { "✗ FALLÓ" }
    
    [PSCustomObject]@{
        Escenario = $_.Scenario
        Estado = $statusStr
        TiempoEmail = $timeStr
        Error = $errorStr
    }
}

$table | Format-Table -AutoSize

$successCount = ($TestResults | Where-Object { $_.Success }).Count
$totalCount = $TestResults.Count

Write-Host "`n========================================" -ForegroundColor Cyan
if ($successCount -eq $totalCount) {
    Write-Host " ✓ TODOS LOS TESTS PASARON ($successCount/$totalCount)" -ForegroundColor Green
} else {
    Write-Host " ⚠ $successCount/$totalCount tests pasaron" -ForegroundColor Yellow
}
Write-Host "========================================`n" -ForegroundColor Cyan

Write-Info "Logs de seguridad: storage/logs/security-*.log"
Write-Info "Logs de queue: storage/logs/queue-*.log"
Write-Info "Logs de mail: storage/logs/mail-*.log"
