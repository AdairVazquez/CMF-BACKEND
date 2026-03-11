# ✅ Sistema de Fallback Automático - Configuración Final

## El Problema Resuelto

**Antes:** Había que cambiar manualmente el .env cuando Redis caía.  
**Ahora:** El sistema detecta automáticamente si Redis está disponible y usa el fallback sin tocar el .env.

---

## Cómo Funciona

### Variables de Configuración (Primary + Fallback)

```env
# En .env - NO tocar nunca, el sistema decide automáticamente
CACHE_STORE_PRIMARY=redis
CACHE_STORE_FALLBACK=file

QUEUE_CONNECTION_PRIMARY=redis
QUEUE_CONNECTION_FALLBACK=database

REDIS_AUTO_DETECT=true
```

### Lógica Automática

```
1. Sistema intenta usar PRIMARY (redis)
2. Si Redis responde → Usa Redis ✓
3. Si Redis NO responde → Usa FALLBACK automáticamente ✓
4. Cada 5 minutos reintenta conectar a Redis
5. Si Redis vuelve → Reconecta automáticamente ✓
```

**SIN INTERVENCIÓN MANUAL, SIN CAMBIAR .ENV**

---

## Escenarios Probados

### ✅ Escenario 1: Redis Disponible (HEALTHY)

```bash
php artisan cmf:health
```

**Resultado:**
```
Estado general: SALUDABLE
Cache Redis: EN LÍNEA
Colas: EN LÍNEA
```

**Configuración usada:**
- Cache: `redis` ← PRIMARY
- Queue: `redis` ← PRIMARY

### ✅ Escenario 2: Redis Caído (DEGRADED pero funcional)

```bash
docker-compose stop redis
php artisan cmf:health
```

**Resultado:**
```
Estado general: DEGRADADO (sistema funcional)
Cache Redis: FALLO (timeout)
  Cache Fallback: ACTIVO (usando archivos)
Colas: DEGRADADO (usando base de datos)
```

**Configuración usada:**
- Cache: `file` ← FALLBACK automático
- Queue: `database` ← FALLBACK automático

**El sistema sigue funcionando:**
- ✅ API responde
- ✅ Autenticación funciona
- ✅ Datos se guardan
- ⚠️ Más lento pero operativo

### ✅ Escenario 3: Redis Se Recupera

```bash
docker-compose start redis
# Esperar 5 minutos o reiniciar servidor
php artisan cmf:health
```

**Resultado:**
```
Estado general: SALUDABLE
Cache Redis: EN LÍNEA
Colas: EN LÍNEA
```

**El sistema volvió a usar Redis automáticamente.**

---

## Variables de Entorno Explicadas

### Cache

```env
# PRIMARY: Intenta usar este primero
CACHE_STORE_PRIMARY=redis

# FALLBACK: Usa este si PRIMARY falla
CACHE_STORE_FALLBACK=file
```

**Opciones válidas:**
- `redis` - Rápido, requiere Redis
- `file` - Lento pero siempre funciona
- `database` - Medio, requiere tabla cache
- `array` - Solo en memoria (un request)

### Queue

```env
# PRIMARY: Intenta usar este primero
QUEUE_CONNECTION_PRIMARY=redis

# FALLBACK: Usa este si PRIMARY falla
QUEUE_CONNECTION_FALLBACK=database
```

**Opciones válidas:**
- `redis` - Rápido, requiere Redis
- `database` - Funciona siempre, requiere tabla jobs
- `sync` - Sin cola, procesa inmediato

### Redis

```env
# AUTO_DETECT: Sistema detecta automáticamente
REDIS_AUTO_DETECT=true

# Si pones false, NUNCA intentará usar Redis
REDIS_AUTO_DETECT=false
```

---

## Configuración por Entorno

### Desarrollo Local (XAMPP sin Redis)

**Archivo:** `.env` o `.env.local`

```env
CACHE_STORE_PRIMARY=file
CACHE_STORE_FALLBACK=file
QUEUE_CONNECTION_PRIMARY=database
QUEUE_CONNECTION_FALLBACK=database
REDIS_AUTO_DETECT=false
```

**Resultado:** Nunca intenta usar Redis, usa archivos/database desde el inicio.

### VPS con Redis (Producción)

**Archivo:** `.env.vps`

```env
CACHE_STORE_PRIMARY=redis
CACHE_STORE_FALLBACK=file
QUEUE_CONNECTION_PRIMARY=redis
QUEUE_CONNECTION_FALLBACK=database
REDIS_AUTO_DETECT=true
```

**Resultado:** Usa Redis, si cae usa fallback automáticamente.

### Hosting Compartido (sin Redis)

**Archivo:** `.env.cpanel`

```env
CACHE_STORE_PRIMARY=file
CACHE_STORE_FALLBACK=file
QUEUE_CONNECTION_PRIMARY=database
QUEUE_CONNECTION_FALLBACK=database
REDIS_AUTO_DETECT=false
```

**Resultado:** Igual que desarrollo local, nunca intenta Redis.

---

## Pruebas Completas

### Prueba 1: Sistema con Redis

```bash
# Asegúrate que Redis esté corriendo
docker-compose up -d redis

# Verifica estado
php artisan cmf:health
# Debe mostrar: "SALUDABLE" con Cache y Colas EN LÍNEA

# Prueba autenticación
.\test-auth.ps1
# Todos los tests deben pasar
```

### Prueba 2: Sistema sin Redis (fallback)

```bash
# Detén Redis
docker-compose stop redis

# Verifica estado
php artisan cmf:health
# Debe mostrar: "DEGRADADO (sistema funcional)"

# Prueba autenticación (debe seguir funcionando)
.\test-auth.ps1
# Todos los tests deben pasar con fallback
```

### Prueba 3: Resiliencia completa

```bash
# Script automatizado que prueba todo
.\test-resilience.ps1

# Debe mostrar:
# - Sistema funciona CON Redis: OK
# - Sistema funciona SIN Redis: OK (fallback)
# - Health check detecta caída: OK
# - Recuperación automática: OK
```

---

## Ventajas de Esta Implementación

### ✅ 1. Sin Intervención Manual
- No hay que cambiar .env cuando Redis cae
- No hay que reiniciar servidor
- Fallback y recuperación automáticos

### ✅ 2. Configuración Clara
- Variables separadas: PRIMARY y FALLBACK
- Semántica obvia: "intenta PRIMARY, si falla usa FALLBACK"
- Fácil de entender y mantener

### ✅ 3. Adaptable a Cualquier Entorno
- XAMPP sin Redis → Funciona
- VPS con Redis → Funciona (óptimo)
- Hosting compartido → Funciona
- Redis intermitente → Funciona (se adapta)

### ✅ 4. Logs Informativos
```
[INFO] Redis no disponible, usando fallback automático
[INFO] Redis recuperado
```

### ✅ 5. Monitoreo en Tiempo Real
```bash
# Ver si Redis está online
php artisan cmf:health

# Ver logs de cambios
tail -f storage/logs/laravel.log | grep -i redis
```

---

## Troubleshooting

### Redis No Se Detecta

**Síntoma:** Sistema siempre usa fallback aunque Redis esté corriendo

**Solución:**
```bash
# 1. Verifica que Redis responda
docker exec cmf_redis redis-cli ping
# Debe retornar: PONG

# 2. Verifica variables en .env
REDIS_AUTO_DETECT=true
CACHE_STORE_PRIMARY=redis

# 3. Limpia configuración
php artisan config:clear

# 4. Verifica de nuevo
php artisan cmf:health
```

### Sistema Siempre Usa Redis Aunque Esté Caído

**Síntoma:** Errores 500 cuando Redis no está disponible

**Solución:**
```bash
# Verifica que el fallback esté configurado
CACHE_STORE_FALLBACK=file
QUEUE_CONNECTION_FALLBACK=database

# Asegúrate que SmartCacheService esté en uso
# NO uses Cache::store('redis') directamente
```

### Quiero Forzar Uso de Archivos (sin Redis)

**Solución:**
```env
# En .env
REDIS_AUTO_DETECT=false
CACHE_STORE_PRIMARY=file
QUEUE_CONNECTION_PRIMARY=database
```

---

## Comandos Útiles

```bash
# Diagnóstico completo
php artisan cmf:health

# Ver estado de Redis
docker-compose ps

# Detener Redis (simular caída)
docker-compose stop redis

# Iniciar Redis
docker-compose start redis

# Ver logs de Redis
docker-compose logs -f redis

# Ver logs de fallback
tail -f storage/logs/cache.log

# Probar autenticación
.\test-auth.ps1

# Probar resiliencia completa
.\test-resilience.ps1
```

---

## Resumen

### Antes
```
Redis cae → Error 500 → Cambiar .env → Reiniciar → Funciona
```

### Ahora
```
Redis cae → Sistema detecta → Usa fallback automático → Funciona
Redis vuelve → Sistema detecta → Reconecta automático → Funciona mejor
```

**TODO AUTOMÁTICO, SIN TOCAR EL .ENV**

---

**Estado:** ✅ Sistema completamente resiliente y autodetección funcionando  
**Configuración:** Primary/Fallback automático implementado  
**Siguiente paso:** CRUD de Companies con cache inteligente

---

**Desarrollado por:** Joshua Getzael Paz Sanchez  
**Asistencia técnica:** Claude (Anthropic)  
**Versión:** 1.0.0  
**Fecha:** Marzo 2026
