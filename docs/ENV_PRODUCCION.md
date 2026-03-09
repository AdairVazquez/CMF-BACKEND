# 🔒 PRODUCCIÓN CHECKLIST - ANTES DE DEPLOY

## ✅ Variables Críticas a Cambiar

```env
# Cambiar a producción
APP_ENV=production
APP_DEBUG=false  # ← CRÍTICO: NUNCA true en prod
APP_URL=https://tu-dominio.com

# Generar nueva key para prod
APP_KEY=  # ← php artisan key:generate

# Seguridad
SESSION_ENCRYPT=true  # ← Ya está

# Base de datos (crear usuario con permisos limitados)
DB_DATABASE=cmf_production
DB_USERNAME=cmf_user  # ← NO usar root
DB_PASSWORD=TuPasswordSegura123!@#

# Redis (poner contraseña)
REDIS_PASSWORD=OtraPasswordSegura456$%^

# Queue
QUEUE_CONNECTION=database  # ← Ya está

# Logs
LOG_CHANNEL=stack
LOG_STACK=daily  # ← Rotación automática
LOG_LEVEL=error  # ← Solo errores

# Mail (ya tienes App Password, OK)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=autojoshfaq@gmail.com
MAIL_PASSWORD="hzmu mtjs elcu hgez"  # ← App Password (OK)
MAIL_ENCRYPTION=tls
```

## ⚠️ DIFERENCIAS DESARROLLO vs PRODUCCIÓN

| Variable | Desarrollo | Producción |
|----------|-----------|-----------|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` ← CRÍTICO |
| `APP_URL` | `http://localhost:8000` | `https://dominio.com` |
| `APP_LOCALE` | `es` | `es` ✓ |
| `SESSION_ENCRYPT` | `true` | `true` ✓ |
| `QUEUE_CONNECTION` | `database` | `database` ✓ |
| `LOG_LEVEL` | `debug` | `error` |
| `LOG_STACK` | `single` | `daily` |
| `DB_USERNAME` | `root` | `cmf_user` (limitado) |
| `DB_PASSWORD` | vacío | fuerte |
| `REDIS_PASSWORD` | vacío | fuerte |

## 🚀 Script de Producción

Crea un archivo `.env.production` en tu servidor:

```bash
# En el servidor VPS
cd /var/www/html
cp .env.example .env.production
nano .env.production
```

Copia esto:

```env
APP_NAME="CMF - Control Laboral"
APP_ENV=production
APP_KEY=  # ← Generar con: php artisan key:generate
APP_DEBUG=false
APP_URL=https://tu-dominio.com

APP_LOCALE=es
APP_FALLBACK_LOCALE=es

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cmf_production
DB_USERNAME=cmf_user
DB_PASSWORD=TuPasswordMuyFuerte123!@#

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true

QUEUE_CONNECTION=database

CACHE_STORE_PRIMARY=redis
CACHE_STORE_FALLBACK=file
CACHE_PREFIX=cmf_prod_

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=RedisPasswordSegura456$%^
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=autojoshfaq@gmail.com
MAIL_PASSWORD="hzmu mtjs elcu hgez"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=autojoshfaq@gmail.com
MAIL_FROM_NAME="CMF - Control Laboral"
```

## 🔐 Seguridad del Archivo

```bash
# Permisos correctos (solo owner puede leer)
chmod 600 .env

# Verificar que NO esté en git
cat .gitignore | grep .env
# Debe mostrar: /.env

# Nunca commitear
git status  # .env NO debe aparecer
```

## 📝 RESUMEN

**Tu `.env` ACTUAL (desarrollo) está BIEN para local:**
✅ `APP_KEY` generada
✅ `SESSION_ENCRYPT=true` 
✅ `QUEUE_CONNECTION=database`
✅ Mail configurado con App Password
✅ Locale en español

**ANTES de ir a producción, CAMBIA:**
⚠️ `APP_ENV=production`
⚠️ `APP_DEBUG=false` ← MUY IMPORTANTE
⚠️ `LOG_LEVEL=error`
⚠️ `DB_PASSWORD` (crear usuario DB no-root)
⚠️ `REDIS_PASSWORD` (poner contraseña)
⚠️ Nueva `APP_KEY` (generar en servidor)

---

**Estado actual:** ✅ ÓPTIMO PARA DESARROLLO
**Para producción:** Usar `.env.production` con cambios arriba
