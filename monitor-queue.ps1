# MONITOREO Y AUTO-RESTART DEL QUEUE WORKER
# Ejecuta: .\monitor-queue.ps1

Write-Host "=== MONITOR DE QUEUE WORKER ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Verificando estado cada 60 segundos..." -ForegroundColor Yellow
Write-Host "Presiona Ctrl+C para detener el monitor." -ForegroundColor Yellow
Write-Host ""

while ($true) {
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Write-Host "[$timestamp] Verificando..." -ForegroundColor Gray
    
    php artisan queue:health
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host ""
        Write-Host "⚠ ALERTA: Queue worker caído. Intentando reiniciar..." -ForegroundColor Red
        
        # Verificar si hay un worker corriendo
        $workerProcess = Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object {
            $_.CommandLine -like "*queue:work*"
        }
        
        if ($workerProcess) {
            Write-Host "Deteniendo worker existente (PID: $($workerProcess.Id))..." -ForegroundColor Yellow
            Stop-Process -Id $workerProcess.Id -Force
            Start-Sleep -Seconds 2
        }
        
        Write-Host "Iniciando nuevo worker..." -ForegroundColor Green
        Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd $PWD; .\start-queue-worker.ps1"
        
        Write-Host "✓ Worker reiniciado." -ForegroundColor Green
    }
    
    Write-Host ""
    Start-Sleep -Seconds 60
}
