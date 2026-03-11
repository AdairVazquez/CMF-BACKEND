# Redis con Fallback Local - CMF

## ✅ Configuración completada

Se ha implementado Redis con Docker y un sistema de fallback automático a cache local si Redis no está disponible.

---

## 🐳 Docker Compose

### Archivo: `docker-compose.yml`

Servicios configurados:
- **Redis 7 Alpine** (puerto 6379)
- Persistencia de datos con volumen
- Healthcheck automático
- Red aislada `cmf_network`

### Comandos Docker

```bash
# Iniciar Redis
docker-compose up -d redis

# Ver estado
docker ps

# Ver logs de Redis
docker-compose logs -f redis

# Detener Redis
docker-compose down

# Reiniciar Redis
docker-compose restart redis

# Ver estadísticas de uso
docker stats cmf_redis
```

---

## ⚙️ Configuración Laravel

### Variables de entorno (.env)

```env
CACHE_STORE=redis
CACHE_PREFIX=cmf_

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

QUEUE_CONNECTION=redis
```

### Stores de cache configurados

1. **redis** - Store principal (rápido, en memoria)
2. **file** - Store de fallback (local, persistente)
3. **failover** - Combinación automática de ambos

---

## 🔧 CacheService

Servicio ubicado en `app/Services/CacheService.php`

### Características

- ✅ Fallback automático si Redis no está disponible
- ✅ Sincronización dual (Redis + local)
- ✅ Logs de advertencia cuando Redis falla
- ✅ Métodos: get, put, remember, forget, flush
- ✅ Verificación de salud: `isRedisAvailable()`

### Uso en código

```php
use App\Services\CacheService;

// En un controlador
public function index(CacheService $cache)
{
    // Obtener del cache con fallback automático
    $data = $cache->remember('users.all', 3600, function () {
        return User::all();
    });
    
    return response()->json($data);
}

// Guardar en cache
$cache->put('key', 'value', 3600);

// Obtener del cache
$value = $cache->get('key', 'default');

// Eliminar
$cache->forget('key');

// Verificar si Redis está disponible
if ($cache->isRedisAvailable()) {
    // Redis está online
}
```

---

## 🩺 Comando de Health Check

### Verificar estado del sistema de cache

```bash
php artisan cache:health
```

**Salida:**
```
=== Estado del Sistema de Cache ===

1. Verificando Redis...
  Redis ..................... ✓ Conectado
  Ping ...................... PONG

2. Verificando cache local...
  Cache local (file) ........ ✓ Funcionando

3. Probando CacheService con fallback...
  CacheService .............. ✓ Funcionando correctamente

4. Estadísticas del sistema:
  Store primario ............ redis
  Store: redis .............. online
  Store: file ............... online

Sistema de cache funcionando correctamente con Redis
```

---

## 🧪 Prueba de fallback

### 1. Con Redis funcionando

```bash
php artisan cache:health
# Debe mostrar: Redis ✓ Conectado
```

### 2. Sin Redis (simulando caída)

```bash
# Detener Redis
docker-compose stop redis

# Verificar
php artisan cache:health
# Debe mostrar: Redis ✗ No disponible
# Sistema usará cache local automáticamente
```

### 3. Reiniciar Redis

```bash
# Iniciar Redis de nuevo
docker-compose start redis

# Verificar
php artisan cache:health
# Debe mostrar: Redis ✓ Conectado nuevamente
```

---

## 📊 Prueba en código

### Test manual con Tinker

```bash
php artisan tinker
```

```php
// Probar Redis directamente
Redis::connection()->ping();  // "PONG"

// Probar cache con fallback
$cache = app(App\Services\CacheService::class);

// Guardar
$cache->put('test', 'valor de prueba', 60);

// Obtener
$cache->get('test');  // "valor de prueba"

// Ver estadísticas
$cache->getStats();

// Detener Redis desde otra terminal: docker-compose stop redis

// Intentar de nuevo (debe funcionar con fallback)
$cache->get('test');  // Sigue funcionando con cache local
$cache->put('test2', 'otro valor', 60);  // Se guarda en local
```

---

## 🚀 Integración con Laravel Horizon

Para usar Horizon con Redis (colas):

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

Configurar en `config/horizon.php`:

```php
'defaults' => [
    'supervisor-1' => [
        'connection' => 'redis',
        'queue' => ['default'],
        'balance' => 'auto',
        'maxProcesses' => 10,
        'tries' => 3,
    ],
],
```

---

## 📝 Ventajas de esta implementación

1. **Alta disponibilidad**: Si Redis cae, el sistema sigue funcionando
2. **Sin cambios en el código**: Los desarrolladores usan `CacheService` normalmente
3. **Logs claros**: Sabes cuándo Redis no está disponible
4. **Sincronización dual**: Los datos críticos se guardan en ambos stores
5. **Fácil debugging**: Comando `cache:health` para verificar estado
6. **Docker portable**: Redis se ejecuta en contenedor aislado
7. **Zero downtime**: El sistema nunca se cae por problemas de cache

---

## 🔐 Seguridad (opcional)

Para proteger Redis con contraseña en producción:

### 1. Editar `docker-compose.yml`

```yaml
command: redis-server --appendonly yes --requirepass tu_password_seguro
```

### 2. Actualizar `.env`

```env
REDIS_PASSWORD=tu_password_seguro
```

### 3. Reiniciar contenedor

```bash
docker-compose down
docker-compose up -d redis
```

---

## 📦 Paquetes instalados

- `predis/predis` v3.4.1 - Cliente Redis en PHP puro (sin extensión C requerida)

---

## ⚡ Comandos útiles

```bash
# Ver uso de memoria de Redis
docker exec cmf_redis redis-cli INFO memory

# Limpiar cache de Redis
docker exec cmf_redis redis-cli FLUSHDB

# Monitorear comandos en tiempo real
docker exec cmf_redis redis-cli MONITOR

# Ver todas las claves
docker exec cmf_redis redis-cli KEYS '*'

# Ver tamaño de la base de datos
docker exec cmf_redis redis-cli DBSIZE
```

---

## 🎯 Próximos pasos

1. ✅ **Redis configurado con fallback**
2. 🔲 Implementar Laravel Horizon para colas
3. 🔲 Configurar Laravel Reverb para WebSockets
4. 🔲 Implementar cache en endpoints críticos
5. 🔲 Monitorear performance con Telescope

---

**Desarrollado por:** Joshua Getzael Paz Sanchez  
**Asistencia técnica:** Claude (Anthropic)  
**Versión:** 1.0.0  
**Fecha:** Marzo 2026
