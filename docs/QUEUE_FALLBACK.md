# SISTEMA DE QUEUE CON FALLBACK AUTOMÁTICO

## 🎯 Objetivo
Garantizar que los emails (2FA, reset password, alertas) SIEMPRE lleguen, incluso si el queue worker se cae.

## 🏗️ Arquitectura

### 1. Queue Worker (Principal)
```bash
php artisan queue:work
```
- Procesa emails en background
- Más rápido (no bloquea requests HTTP)
- Permite reintentos automáticos

### 2. Fallback Automático (Respaldo)
Si el worker está caído, el sistema automáticamente usa envío **síncrono** (inmediato).

```php
// app/Notifications/Auth/TwoFactorCodeNotification.php
public function __construct() {
    $queueHealth = app(QueueHealthService::class);
    if (!$queueHealth->shouldUseQueue()) {
        $this->connection = 'sync'; // ← Fallback automático
    }
}
```

## 📊 Health Check

### Verificación automática cada 5 minutos:
- Cuenta jobs pendientes muy antiguos (>5 min)
- Si hay >10 jobs atascados → queue está muerto
- Activa fallback automático
- Cache el resultado para no sobrecargar DB

### Comandos de monitoreo:

```bash
# Ver estado del queue
php artisan queue:health

# Forzar health check
php artisan queue:auto-restart

# Ver estadísticas
php artisan queue:check-jobs
```

## 🔄 Auto-Restart

### Opción 1: Monitor en PowerShell (Desarrollo)
```powershell
.\monitor-queue.ps1
```
- Verifica cada 60 segundos
- Si detecta queue caído, reinicia automáticamente
- Mata proceso PHP antiguo si existe
- Abre nueva ventana con worker

### Opción 2: Worker con loop infinito (Desarrollo)
```powershell
.\start-queue-worker.ps1
```
- Worker se reinicia automáticamente si crash
- Max 1000 jobs o 1 hora antes de restart preventivo
- Reinicio en 5 seg si error, 2 seg si normal

### Opción 3: Supervisor (Producción Linux)
```ini
[program:cmf-queue-worker]
command=php /var/www/html/artisan queue:work --tries=3 --timeout=90
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/queue-worker.log
```

### Opción 4: Systemd (Producción Linux)
```ini
[Unit]
Description=CMF Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html
ExecStart=/usr/bin/php artisan queue:work --tries=3
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable cmf-queue-worker
sudo systemctl start cmf-queue-worker
```

### Opción 5: NSSM (Producción Windows Server)
```powershell
nssm install CMFQueueWorker "C:\PHP\php.exe" "C:\inetpub\cmf\artisan queue:work --tries=3"
nssm set CMFQueueWorker AppDirectory C:\inetpub\cmf
nssm set CMFQueueWorker AppRestartDelay 5000
nssm start CMFQueueWorker
```

## 🧪 Pruebas

### Test 1: Queue funcionando
```bash
# Terminal 1: Worker corriendo
.\start-queue-worker.ps1

# Terminal 2: Probar 2FA
.\test-2fa-flow.ps1
```
**Resultado esperado:** Email llega en <5 segundos vía queue.

### Test 2: Queue caído (Fallback)
```bash
# NO iniciar worker, solo server
php artisan serve

# Probar 2FA
.\test-2fa-flow.ps1
```
**Resultado esperado:** Email llega igual, pero más lento (10-15 seg) vía sync.

### Test 3: Queue atascado
```bash
# Agregar job falso que nunca termina
php artisan tinker
>>> DB::table('jobs')->insert(['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'created_at' => now()->subMinutes(20)]);

# Verificar health
php artisan queue:health
```
**Resultado esperado:** Detecta queue caído, activa fallback.

## 📈 Monitoreo en Producción

### Health endpoint
```php
// routes/api.php
Route::get('/health/queue', function () {
    $queueHealth = app(\App\Services\QueueHealthService::class);
    return response()->json($queueHealth->getStats());
});
```

### Dashboard simple
```bash
curl http://localhost:8000/api/health/queue
```

**Respuesta:**
```json
{
  "pending_jobs": 3,
  "failed_jobs": 0,
  "oldest_job_age_minutes": 2,
  "is_healthy": true
}
```

### Alertas
Configura monitoreo externo (Uptime Robot, Pingdom, StatusCake) para:
- `GET /api/health/queue` cada 5 minutos
- Si `is_healthy: false` → Alerta por email/SMS

## 🔒 Seguridad

### Encriptación inteligente
```php
// app/Models/User.php
protected function casts(): array {
    $casts = [...];
    
    // Solo encripta si APP_KEY existe
    if (config('app.key')) {
        $casts['two_factor_secret'] = 'encrypted';
        $casts['two_factor_recovery_codes'] = 'encrypted';
    }
    
    return $casts;
}
```

**Por qué:** Evita crashes si `APP_KEY` está vacía (ej: primer deploy, contenedor mal configurado).

## 🚀 Comandos Útiles

```bash
# Ver jobs pendientes
php artisan queue:check-jobs

# Procesar 1 job y salir
php artisan queue:work --once

# Limpiar jobs fallidos
php artisan queue:flush

# Reintentar jobs fallidos
php artisan queue:retry all

# Ver logs de queue
tail -f storage/logs/queue-*.log

# Forzar restart de workers
php artisan queue:restart
```

## 🎭 Escenarios de Falla

| Escenario | Sistema Detecta | Acción Automática | Tiempo Email |
|-----------|----------------|-------------------|--------------|
| Queue OK | ✓ Healthy | Usa queue | ~3 seg |
| Worker caído | ✓ Jobs >5min | Fallback sync | ~12 seg |
| Worker crash | Monitor PS | Auto-restart | ~5 seg |
| DB caída | ✗ Error | Log + sync | Error visible |
| SMTP caído | ✗ Timeout | Reintento 3x | 90 seg max |

## 📝 Logs

### Queue health
```
storage/logs/queue-YYYY-MM-DD.log
```

### Security (2FA, login)
```
storage/logs/security-YYYY-MM-DD.log
```

### Mail
```
storage/logs/mail-YYYY-MM-DD.log
```

## ✅ Checklist Producción

- [ ] Supervisor/Systemd configurado
- [ ] Health endpoint habilitado
- [ ] Monitoreo externo activo
- [ ] Alertas configuradas
- [ ] APP_KEY generada y en `.env`
- [ ] QUEUE_CONNECTION=database
- [ ] Tablas `jobs` y `failed_jobs` migradas
- [ ] Logs rotando (logrotate)
- [ ] Fallback probado

---

**Resultado:** Sistema robusto que SIEMPRE envía emails, con o sin queue worker.
