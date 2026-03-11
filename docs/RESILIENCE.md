# Resiliencia del Sistema CMF

## Arquitectura Robusta Multi-Entorno

El sistema CMF está diseñado para funcionar en **cualquier entorno**, desde desarrollo local sin Redis hasta hosting compartido básico.

---

## Escenarios Soportados

### ✅ Escenario 1: Desarrollo Local (XAMPP Windows)
- MySQL ✓
- Redis ✗ (no disponible)
- **Solución:** Cache y colas usan archivos/database automáticamente

### ✅ Escenario 2: VPS (Ubuntu/Contabo)
- MySQL ✓
- Redis ✓ (Docker)
- **Solución:** Usa Redis para máximo rendimiento

### ✅ Escenario 3: Hosting Compartido (cPanel)
- MySQL ✓
- Redis ✗ (no permitido)
- SSH ✗ (no disponible)
- **Solución:** Cache y colas usan archivos/database, comandos via cron

---

## Sistema de Fallback Automático

### Cache

**Orden de prioridad:**
1. **Redis** (si disponible) - Rápido, en memoria
2. **File** (fallback) - Local, persistente
3. **Array** (emergencia) - Solo en memoria para request actual

**Comportamiento:**
```php
// El sistema detecta Redis automáticamente
$cache = app(SmartCacheService::class);

// Si Redis está online, usa Redis + sincroniza con archivos
$cache->put('key', 'value', 3600);

// Si Redis cae, usa archivos transparentemente
// SIN cambios en el código, SIN downtime
```

**Logs automáticos:**
```
[2026-03-08 22:00:00] INFO: Redis no disponible, usando cache local
[2026-03-08 22:05:00] INFO: Redis recuperado
```

### Colas

**Orden de prioridad:**
1. **Redis** (si disponible) - Procesamiento rápido
2. **Database** (fallback) - Tabla `jobs` en MySQL

**Comportamiento:**
```php
$queue = app(SmartQueueService::class);

// Despacha al driver disponible automáticamente
$queue->dispatch(new SendEmailJob($user));

// Si Redis cae, usa tabla jobs sin perder trabajos
```

### Sesiones

**Configuración según entorno:**
- **Con Redis:** `SESSION_DRIVER=redis`
- **Sin Redis:** `SESSION_DRIVER=file`

---

## Monitoreo del Sistema

### 1. Health Check Endpoint

**GET** `/api/v1/system/health`

**Respuesta cuando todo está bien:**
```json
{
  "status": "healthy",
  "timestamp": "2026-03-08T22:00:00-06:00",
  "environment": "production",
  "version": "1.0.0",
  "services": {
    "database": {
      "status": "online"
    },
    "cache": {
      "status": "online"
    },
    "queue": {
      "status": "online"
    },
    "storage": {
      "status": "online"
    }
  }
}
```

**Respuesta cuando Redis cae (DEGRADED):**
```json
{
  "status": "degraded",
  "services": {
    "database": {
      "status": "online"
    },
    "cache": {
      "status": "degraded",
      "fallback_active": true,
      "message": "Redis no disponible, usando cache local"
    },
    "queue": {
      "status": "degraded",
      "message": "Colas usando base de datos"
    },
    "storage": {
      "status": "online"
    }
  }
}
```

**Estados posibles:**
- `healthy` → Todo funciona correctamente (HTTP 200)
- `degraded` → Redis caído pero sistema funcional con fallback (HTTP 200)
- `critical` → MySQL o storage caídos, sistema no funcional (HTTP 503)

### 2. Comando de Diagnóstico

```bash
php artisan cmf:health
```

**Output ejemplo:**
```
=================================================================
CMF - Diagnóstico del Sistema
Fecha: 08/03/2026 22:00:00
Entorno: production
Versión: 1.0.0
=================================================================
Base de datos MySQL     : EN LÍNEA    (2ms)
Cache Redis             : FALLO       (timeout)
  Cache Fallback        : ACTIVO      (usando archivos)
Colas                   : DEGRADADO   (usando base de datos)
Almacenamiento          : ESCRIBIBLE  (1024 MB libres)
Logs                    : ESCRIBIBLE
=================================================================
Estado general          : DEGRADADO   (sistema funcional)
=================================================================
Recomendación: Verificar conexión con Redis
  Comando: docker-compose ps
  Comando: docker-compose logs redis
```

---

## Configuración por Entorno

### Desarrollo Local (XAMPP)

**Archivo:** `.env.local`

```env
CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file
REDIS_ENABLED=false
APP_DEBUG=true
```

**Iniciar:**
```bash
# Copiar configuración
copy .env.local .env

# Instalar dependencias
composer install

# Migrar base de datos
php artisan migrate --seed

# Iniciar servidor
php artisan serve
```

### VPS (Ubuntu con Redis)

**Archivo:** `.env.vps`

```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_ENABLED=true
REDIS_PASSWORD=password_seguro
APP_DEBUG=false
FORCE_HTTPS=true
```

**Despliegue:**
```bash
# 1. Subir código al servidor
git pull origin main

# 2. Instalar dependencias
composer install --optimize-autoloader --no-dev

# 3. Configurar entorno
cp .env.vps .env
php artisan key:generate

# 4. Migrar base de datos
php artisan migrate --force

# 5. Iniciar Redis
docker-compose up -d redis

# 6. Optimizar Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Configurar supervisor para queue:work
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Hosting Compartido (cPanel)

**Archivo:** `.env.cpanel`

```env
CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file
REDIS_ENABLED=false
APP_DEBUG=false
FORCE_HTTPS=true
CACHE_WARMING_ENABLED=false
```

**Despliegue:**
```bash
# 1. Subir archivos via FTP/cPanel File Manager

# 2. Conectar via cPanel Terminal o crear archivo PHP:
<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Migrar
$kernel->call('migrate', ['--force' => true]);

// Limpiar caches
$kernel->call('config:clear');
$kernel->call('route:clear');
$kernel->call('view:clear');

echo "Despliegue completado\n";

# 3. Configurar Cron Jobs en cPanel:
# */5 * * * * cd /home/usuario/public_html && php artisan schedule:run >> /dev/null 2>&1
```

---

## Comandos de Mantenimiento

### Verificar Estado del Sistema

```bash
# Diagnóstico completo
php artisan cmf:health

# Health check vía API
curl http://localhost:8000/api/v1/system/health
```

### Gestión de Cache

```bash
# Limpiar todo el cache
php artisan cache:clear

# Limpiar por tag
php artisan tinker
>>> app(App\Services\SmartCacheService::class)->flush('company:1');

# Precarga de cache (warming)
php artisan tinker
>>> app(App\Services\SmartCacheService::class)->warmUp();
```

### Gestión de Colas

```bash
# Ver trabajos pendientes
php artisan queue:monitor

# Limpiar trabajos fallidos antiguos
php artisan tinker
>>> app(App\Services\SmartQueueService::class)->cleanOldFailedJobs(7);

# Procesar colas manualmente
php artisan queue:work --once
```

### Gestión de Redis

```bash
# Ver estado
docker-compose ps

# Ver logs
docker-compose logs -f redis

# Reiniciar
docker-compose restart redis

# Detener
docker-compose down

# Iniciar
docker-compose up -d redis

# Ver estadísticas
docker exec cmf_redis redis-cli INFO
```

### Gestión de Logs

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Ver logs de NFC
tail -f storage/logs/nfc.log

# Ver logs de seguridad
tail -f storage/logs/security.log

# Limpiar logs antiguos (más de 30 días)
find storage/logs -name "*.log" -type f -mtime +30 -delete
```

---

## Recuperación ante Fallos

### Escenario: Redis se cae

**Síntomas:**
- Health check muestra "degraded"
- Logs muestran "Redis no disponible"

**Impacto:**
- ✅ Sistema sigue funcionando
- ✅ API responde normalmente
- ⚠️ Cache usa archivos (más lento)
- ⚠️ Colas usan database (más lento)

**Solución:**
```bash
# 1. Verificar Docker
docker-compose ps

# 2. Ver logs de Redis
docker-compose logs redis

# 3. Reiniciar Redis
docker-compose restart redis

# 4. Verificar recuperación
php artisan cmf:health
```

**Resultado:** Sistema vuelve a estado "healthy" automáticamente.

### Escenario: MySQL se cae

**Síntomas:**
- Health check muestra "critical" (HTTP 503)
- API retorna 500 en consultas
- Logs muestran "Error de base de datos"

**Impacto:**
- ❌ Sistema NO funciona
- ❌ API no puede responder

**Solución:**
```bash
# 1. Verificar MySQL
sudo systemctl status mysql
# o en XAMPP: verificar panel de control

# 2. Reiniciar MySQL
sudo systemctl restart mysql
# o en XAMPP: Stop y Start MySQL

# 3. Verificar conexión
php artisan tinker
>>> DB::connection()->getPdo();

# 4. Verificar recuperación
php artisan cmf:health
```

### Escenario: Storage lleno

**Síntomas:**
- Health check muestra warning o critical
- Logs no se escriben
- Cache de archivos falla

**Solución:**
```bash
# 1. Verificar espacio
df -h

# 2. Limpiar logs antiguos
find storage/logs -name "*.log" -type f -mtime +30 -delete

# 3. Limpiar cache de archivos
php artisan cache:clear

# 4. Limpiar vistas compiladas
php artisan view:clear

# 5. Verificar recuperación
php artisan cmf:health
```

---

## Mejores Prácticas

### ✅ DO (Hacer)

1. **Monitorear el health check regularmente**
   ```bash
   # Agregar a cron cada 5 minutos
   */5 * * * * curl http://tu-dominio.com/api/v1/system/health
   ```

2. **Rotar logs automáticamente**
   - Logs daily configurados con retención de 7-90 días
   - Limpiar logs antiguos vía cron

3. **Usar SmartCacheService en lugar de Cache facade**
   ```php
   // ✅ Correcto
   $cache = app(SmartCacheService::class);
   $cache->remember('key', 3600, fn() => getData());

   // ❌ Evitar
   Cache::remember('key', 3600, fn() => getData());
   ```

4. **Verificar estado antes de deploys**
   ```bash
   php artisan cmf:health
   ```

5. **Backup regular de base de datos**
   ```bash
   php artisan backup:run # con spatie/laravel-backup
   ```

### ❌ DON'T (No hacer)

1. **No asumir que Redis siempre está disponible**
2. **No hardcodear drivers de cache/queue**
3. **No exponer detalles técnicos en producción** (`HEALTH_EXPOSE_METRICS=false`)
4. **No ignorar estados "degraded"** (investigar causa)
5. **No desactivar logs de seguridad**

---

## Alertas Recomendadas

### Configurar Alertas por Email/Slack

1. **Estado critical**
   - MySQL caído
   - Storage lleno
   - Acción: Notificar inmediatamente

2. **Estado degraded por más de 1 hora**
   - Redis caído prolongado
   - Acción: Investigar y corregir

3. **Trabajos fallidos > 10**
   - Colas con problemas
   - Acción: Revisar logs de queue

4. **Espacio en disco < 500 MB**
   - Storage bajo
   - Acción: Limpiar o expandir

---

## Soporte

Para problemas específicos:

1. **Revisar logs:** `storage/logs/laravel.log`
2. **Ejecutar diagnóstico:** `php artisan cmf:health`
3. **Verificar configuración:** `.env`
4. **Consultar documentación:** `README.md`, `API_DOCUMENTATION.md`

---

**Desarrollado por:** Joshua Getzael Paz Sanchez  
**Asistencia técnica:** Claude (Anthropic)  
**Versión:** 1.0.0  
**Fecha:** Marzo 2026
