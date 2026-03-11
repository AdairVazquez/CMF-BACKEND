# Script para iniciar el worker con auto-restart en caso de error
# Ejecuta: .\start-queue-worker.ps1

Write-Host "=== QUEUE WORKER CON AUTO-RESTART ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Worker se reiniciará automáticamente si falla." -ForegroundColor Yellow
Write-Host "Para detenerlo: presiona Ctrl+C" -ForegroundColor Yellow
Write-Host ""

while ($true) {
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Write-Host "[$timestamp] Iniciando worker..." -ForegroundColor Green
    
    php artisan queue:work --tries=3 --timeout=90 --max-jobs=1000 --max-time=3600
    
    $exitCode = $LASTEXITCODE
    
    if ($exitCode -ne 0) {
        Write-Host ""
        Write-Host "⚠ Worker detenido con código $exitCode. Reiniciando en 5 segundos..." -ForegroundColor Yellow
        Start-Sleep -Seconds 5
    } else {
        Write-Host ""
        Write-Host "Worker detenido normalmente. Reiniciando en 2 segundos..." -ForegroundColor Gray
        Start-Sleep -Seconds 2
    }
}
