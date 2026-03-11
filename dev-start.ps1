# Script de inicio para desarrollo
# Ejecutar: .\dev-start.ps1

Write-Host "=== CMF - Iniciando entorno de desarrollo ===" -ForegroundColor Cyan
Write-Host ""

# 1. Verificar Docker
Write-Host "1. Verificando Docker Desktop..." -ForegroundColor Yellow
try {
    docker --version | Out-Null
    Write-Host "   [OK] Docker disponible" -ForegroundColor Green
} catch {
    Write-Host "   [ERROR] Docker no encontrado. Instala Docker Desktop" -ForegroundColor Red
    exit 1
}

Write-Host ""

# 2. Iniciar Redis
Write-Host "2. Iniciando Redis..." -ForegroundColor Yellow
docker-compose up -d redis
Start-Sleep -Seconds 3

$redisStatus = docker ps --filter "name=cmf_redis" --format "{{.Status}}"
if ($redisStatus -like "*Up*") {
    Write-Host "   [OK] Redis corriendo" -ForegroundColor Green
} else {
    Write-Host "   [WARN] Redis no inicio correctamente" -ForegroundColor Yellow
}

Write-Host ""

# 3. Verificar conexión a base de datos
Write-Host "3. Verificando base de datos..." -ForegroundColor Yellow
try {
    php artisan db:show --database=mysql 2>&1 | Out-Null
    Write-Host "   [OK] MySQL conectado" -ForegroundColor Green
} catch {
    Write-Host "   [WARN] MySQL no disponible" -ForegroundColor Yellow
}

Write-Host ""

# 4. Limpiar cache
Write-Host "4. Limpiando cache..." -ForegroundColor Yellow
php artisan config:clear | Out-Null
php artisan route:clear | Out-Null
Write-Host "   [OK] Cache limpiado" -ForegroundColor Green

Write-Host ""

# 5. Verificar estado de cache
Write-Host "5. Verificando sistema de cache..." -ForegroundColor Yellow
php artisan cache:health

Write-Host ""

# 6. Iniciar servidor
Write-Host "=== Iniciando servidor Laravel ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Servidor: http://localhost:8000" -ForegroundColor Green
Write-Host "API Base: http://localhost:8000/api/v1" -ForegroundColor Green
Write-Host ""
Write-Host "Presiona Ctrl+C para detener" -ForegroundColor Gray
Write-Host ""

php artisan serve
