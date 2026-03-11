# CHECKLIST DE SEGURIDAD PARA PRODUCCIÓN

## ✅ Configuración APP

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false` (NUNCA `true` en producción)
- [ ] `APP_KEY` generada y única por servidor
- [ ] `APP_URL` con HTTPS

## ✅ Base de Datos

- [ ] Usuario DB con permisos mínimos (NO root)
- [ ] Contraseña fuerte (16+ caracteres, alfanumérico + símbolos)
- [ ] DB solo accesible desde localhost (o VPC privada)
- [ ] Backups automáticos diarios
- [ ] Encriptación en reposo activada

## ✅ Cache y Sesiones

- [ ] Redis con contraseña fuerte
- [ ] Redis bind a 127.0.0.1 (no 0.0.0.0)
- [ ] `SESSION_ENCRYPT=true`
- [ ] `SESSION_DRIVER=database` o `redis`

## ✅ Email

- [ ] Usar App Password (no contraseña real)
- [ ] MAIL_ENCRYPTION=tls o ssl
- [ ] Rate limiting en forgot-password (5/min)
- [ ] SPF, DKIM, DMARC configurados

## ✅ Logs y Errores

- [ ] `LOG_LEVEL=error` (no debug)
- [ ] `LOG_STACK=daily` con rotación
- [ ] Logs fuera de public/ (ej: /storage/logs/)
- [ ] No exponer stack traces al cliente
- [ ] Monitoreo de logs críticos (Sentry, Rollbar)

## ✅ HTTPS y Certificados

- [ ] Certificado SSL válido (Let's Encrypt gratis)
- [ ] HSTS activado (Strict-Transport-Security)
- [ ] Redirect HTTP → HTTPS
- [ ] TLS 1.2+ (deshabilitar TLS 1.0/1.1)

## ✅ Servidor Web

### Nginx
```nginx
# Ocultar versión
server_tokens off;

# Headers de seguridad
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' https:" always;

# Bloquear acceso a archivos sensibles
location ~ /\. {
    deny all;
}

location ~ \.(env|log|sql)$ {
    deny all;
}
```

### Apache
```apache
# .htaccess
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>

# Bloquear archivos
<FilesMatch "\.(env|log|sql)$">
    Require all denied
</FilesMatch>
```

## ✅ Archivos y Permisos

- [ ] `storage/` y `bootstrap/cache/` escribibles por web server
- [ ] Resto de archivos read-only para web server
- [ ] `.env` con permisos 600 (solo owner)
- [ ] `/public` como document root (NUNCA raíz del proyecto)
- [ ] `.git/` fuera de public o bloqueado

```bash
# Permisos correctos
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chmod 600 /var/www/html/.env
```

## ✅ Firewall

- [ ] UFW/iptables activado
- [ ] Solo puertos 80, 443, 22 abiertos
- [ ] Puerto 22 (SSH) con key-based auth (no password)
- [ ] Fail2Ban instalado y configurado
- [ ] Rate limiting en Nginx/Apache

```bash
# UFW básico
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

## ✅ Composer y Dependencias

- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] No instalar dev dependencies en producción
- [ ] Auditoría de paquetes (`composer audit`)
- [ ] Actualizar dependencias críticas regularmente

## ✅ Laravel Específico

- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan optimize`
- [ ] `php artisan storage:link`

```bash
# Script de deploy
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
php artisan queue:restart
php artisan up
```

## ✅ Queue Worker

- [ ] Supervisor o Systemd configurado
- [ ] Auto-restart habilitado
- [ ] Logs rotando (logrotate)
- [ ] `queue:restart` en cada deploy

```ini
# /etc/supervisor/conf.d/cmf-queue.conf
[program:cmf-queue-worker]
command=php /var/www/html/artisan queue:work --tries=3 --timeout=90
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/queue-worker.log
```

## ✅ Monitoreo

- [ ] Uptime monitoring (UptimeRobot, Pingdom)
- [ ] Log monitoring (Papertrail, Loggly)
- [ ] Error tracking (Sentry, Rollbar)
- [ ] Performance monitoring (New Relic, Blackfire)
- [ ] Health endpoint: `/api/v1/system/health`

## ✅ Backups

- [ ] Base de datos: backup diario
- [ ] Archivos: backup semanal
- [ ] Logs: rotación y compresión
- [ ] Backups en servidor diferente
- [ ] Restore testado al menos 1x/mes

```bash
# Backup DB automático
0 2 * * * /usr/bin/mysqldump -u user -p'password' database > /backups/db-$(date +\%Y\%m\%d).sql && gzip /backups/db-$(date +\%Y\%m\%d).sql
```

## ✅ Autenticación y Sesiones

- [ ] Sanctum tokens con expiración
- [ ] 2FA obligatorio para admins
- [ ] Bloqueo de cuenta tras 5 intentos
- [ ] Logout en todos los dispositivos implementado
- [ ] Renovación de tokens cada X días

## ✅ Rate Limiting

- [ ] Login: 5 intentos / minuto
- [ ] 2FA verify: 5 intentos / minuto
- [ ] Forgot password: 5 intentos / hora
- [ ] API general: 60 req / minuto
- [ ] NFC: 30 req / minuto

## ✅ CORS

```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_origins' => ['https://tu-frontend.com'], // NO '*'
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'max_age' => 86400,
];
```

## ✅ Headers de Seguridad

Ya implementados en `SecurityHeaders` middleware:
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: strict-origin-when-cross-origin

## ✅ Validación de Inputs

- [ ] Todos los endpoints con FormRequests
- [ ] Validación de tipos con DTOs
- [ ] Sanitización de HTML inputs
- [ ] No confiar NUNCA en datos del cliente

## ✅ SQL Injection

- [ ] TypeORM/Eloquent (nunca SQL raw concatenado)
- [ ] Prepared statements si usas DB::raw()
- [ ] Nunca interpolar variables directamente en queries

## ✅ XSS Prevention

- [ ] Blade escapa por defecto (`{{ }}`)
- [ ] Usar `{!! !!}` SOLO para HTML confiable
- [ ] Content-Security-Policy header

## ✅ CSRF Protection

- [ ] Sanctum SPA mode activado
- [ ] Tokens en cookies httpOnly
- [ ] CSRF token en forms web

## ✅ Inyección de Comandos

- [ ] Nunca usar `exec()`, `shell_exec()`, `system()` con input del usuario
- [ ] Si necesario, validar con whitelist estricta
- [ ] Escapar con `escapeshellarg()` y `escapeshellcmd()`

## ✅ File Upload (Futuro)

- [ ] Validar tipo MIME real (no solo extensión)
- [ ] Límite de tamaño (ej: 10MB)
- [ ] Guardar fuera de public/
- [ ] Renombrar archivos (no mantener nombre original)
- [ ] Escanear con antivirus (ClamAV)

## ✅ Compliance

- [ ] GDPR: Derecho al olvido implementado
- [ ] Logs de acceso y cambios (audit trail)
- [ ] Política de privacidad visible
- [ ] Consentimiento de cookies
- [ ] Retención de datos limitada

## ✅ Documentación

- [ ] README con instrucciones de deploy
- [ ] .env.example sin datos sensibles
- [ ] Runbook de incidentes
- [ ] Contacto de emergencia documentado

## ✅ Testing

- [ ] Tests de seguridad (inyección, XSS, CSRF)
- [ ] Tests de autenticación y 2FA
- [ ] Tests de rate limiting
- [ ] Tests de permisos y roles

---

## 🚨 NUNCA HACER EN PRODUCCIÓN

❌ `APP_DEBUG=true`
❌ Exponer `.env` en public/
❌ Root login SSH con password
❌ DB user con privilegios ALL
❌ Secrets en código (usar env vars)
❌ Error traces al usuario
❌ Usar `*` en CORS allowed_origins
❌ SSL autofirmado sin proxy
❌ Queue worker sin supervisor
❌ Deploy sin backup previo
❌ Usar rama `dev` en producción
❌ Composer install con `--dev`
❌ Cache config en desarrollo

---

## ✅ LISTO PARA PRODUCCIÓN

Cuando todos los items estén marcados ✅:

```bash
# Último check
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Verificar
php artisan queue:health
php artisan system:health

# Deploy
git push production main
```

**Estado:** 🚀 PRODUCTION READY
