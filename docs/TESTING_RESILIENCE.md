# GUÍA DE TESTING DE RESILIENCIA 2FA

## 🎯 Objetivo
Probar que el sistema 2FA funciona en **TODOS** los escenarios, incluso cuando la cola se cae.

## 📋 Scripts Disponibles

### 1. Test Completo (Todos los Escenarios)
```powershell
.\test-2fa-resilience.ps1
```

**Prueba:**
- ✅ Escenario 1: Con queue worker funcionando (rápido, 3-5 seg)
- ✅ Escenario 2: Sin queue worker (fallback sync, 10-15 seg)
- ✅ Escenario 3: Queue se cae a mitad (recuperación automática)

**Duración:** ~10-15 minutos (requiere interacción)

### 2. Test Rápido (Solo Fallback)
```powershell
.\test-fallback-quick.ps1
```

**Prueba:**
- ✅ Solo el escenario de fallback sin queue worker

**Duración:** ~2-3 minutos

### 3. Test Normal (Flujo Completo)
```powershell
.\test-2fa-flow.ps1
```

**Prueba:**
- ✅ Flujo 2FA normal con queue worker

**Duración:** ~3-4 minutos

## 🚀 Cómo Ejecutar Tests

### Test Completo de Resiliencia

1. **Asegúrate de tener el servidor corriendo:**
   ```powershell
   php artisan serve
   ```

2. **Ejecuta el test (en OTRA terminal):**
   ```powershell
   .\test-2fa-resilience.ps1
   ```

3. **El script hará:**
   - Iniciará/detendrá queue workers automáticamente
   - Te pedirá confirmar si los emails llegaron
   - Ingresarás códigos manualmente
   - Al final mostrará un resumen

### Test Rápido de Fallback

1. **Detén cualquier queue worker que esté corriendo**
   ```powershell
   Get-Process php | Where-Object {$_.CommandLine -like "*queue:work*"} | Stop-Process
   ```

2. **Ejecuta el test:**
   ```powershell
   .\test-fallback-quick.ps1
   ```

3. **Verifica que el email LLEGUE sin queue worker**

## ✅ Resultados Esperados

### Escenario 1: Con Queue Worker
```
✓ Email llega en 3-5 segundos
✓ 2FA se activa correctamente
✓ Re-login requiere 2FA
✓ Segundo email llega rápido
```

### Escenario 2: Sin Queue Worker (Fallback)
```
✓ Email llega en 10-15 segundos (más lento, normal)
✓ 2FA se activa correctamente
✓ Sistema detecta queue caído
✓ Usa envío síncrono automáticamente
```

### Escenario 3: Queue Se Cae a Mitad
```
✓ Primer email llega con queue (rápido)
✓ Queue worker se detiene
✓ Sistema detecta falla
✓ Segundo email llega vía fallback (sync)
✓ Recuperación automática sin intervención
```

## 🔍 Verificación Manual

### 1. Verificar Estado de Queue
```powershell
php artisan queue:health
```

**Salida esperada (healthy):**
```
+---------------------------+-------------+
| Métrica                   | Valor       |
+---------------------------+-------------+
| Jobs pendientes           | 0           |
| Jobs fallidos             | 0           |
| Job más antiguo (minutos) | 0           |
| Estado                    | ✓ Saludable |
+---------------------------+-------------+
```

**Salida esperada (caído):**
```
⚠ Queue worker parece estar detenido o atascado.
```

### 2. Verificar Procesos Queue
```powershell
Get-Process php | Where-Object {$_.CommandLine -like "*queue:work*"}
```

**Si hay queue worker:**
```
Handles  NPM(K)    PM(K)      WS(K)     CPU(s)     Id  SI ProcessName
-------  ------    -----      -----     ------     --  -- -----------
    123      12     34567      45678      0.50   1234   1 php
```

**Si NO hay (esperado para test fallback):**
```
(vacío)
```

### 3. Ver Jobs en Cola
```powershell
php artisan queue:check-jobs
```

### 4. Ver Logs en Tiempo Real

**Security logs:**
```powershell
Get-Content storage/logs/security-*.log -Tail 20 -Wait
```

**Mail logs:**
```powershell
Get-Content storage/logs/mail-*.log -Tail 20 -Wait
```

**Queue logs:**
```powershell
Get-Content storage/logs/queue-*.log -Tail 20 -Wait
```

## 🐛 Troubleshooting

### Email NO Llega con Fallback

1. **Verifica que queue NO esté corriendo:**
   ```powershell
   php artisan queue:health
   ```
   Debe mostrar error.

2. **Revisa logs de mail:**
   ```powershell
   cat storage/logs/mail-*.log
   ```

3. **Verifica .env:**
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=tu-email@gmail.com
   MAIL_PASSWORD="tu-app-password"
   ```

4. **Prueba envío manual:**
   ```powershell
   php artisan mail:test
   ```

### Queue Worker No Se Detiene

```powershell
# Forzar detención
Get-Process php | Where-Object {$_.CommandLine -like "*queue:work*"} | Stop-Process -Force

# Verificar
php artisan queue:health
```

### Queue Worker No Se Inicia

```powershell
# Verificar que las tablas existan
php artisan migrate

# Iniciar manualmente
.\start-queue-worker.ps1
```

### Email Llega Lento Incluso con Queue

- Verifica tu conexión a internet
- Revisa spam en Gmail
- Gmail puede tener delays (normal hasta 30 seg)
- Usa otro provider SMTP si persiste

## 📊 Métricas de Rendimiento

| Escenario | Tiempo Esperado | Acceptable |
|-----------|----------------|------------|
| Con queue worker | 3-5 segundos | < 10 seg |
| Sin queue (fallback) | 10-15 segundos | < 30 seg |
| Queue se cae | 10-20 segundos | < 30 seg |

## 🎬 Ejemplo de Ejecución Completa

```powershell
PS> .\test-2fa-resilience.ps1

========================================
 TEST DE RESILIENCIA 2FA
========================================

Email: joshuapaz24@gmail.com
Este test probará el sistema en múltiples escenarios.

✓ Servidor Laravel corriendo

========================================
 PREPARANDO ESCENARIO 1
========================================

ℹ Iniciando queue worker en background...
✓ Queue worker funcionando

========================================
 ESCENARIO: 1️⃣  CON QUEUE WORKER (Normal)
========================================

ℹ [1/5] Login inicial...
✓ Token obtenido: 1|abc123...
ℹ [2/5] Habilitando 2FA...
✓ Revisa tu correo e ingresa el código de 6 dígitos para activar 2FA
ℹ [3/5] Esperando email con código...
    Email debe llegar a: joshuapaz24@gmail.com

    ¿Llegó el email? (s/n): s
✓ Email llegó en 4 segundos
ℹ [4/5] Ingresa el código de 6 dígitos del email...
    Código: 123456
✓ 2FA activado correctamente

    Códigos de recuperación:
      XXXX-XXXX-XXXX
      YYYY-YYYY-YYYY
      ...

ℹ [5/5] Probando login con 2FA...
✓ Sistema solicitó 2FA correctamente
ℹ Esperando segundo email con código de login...
    ¿Llegó el segundo email? (s/n): s
✓ Segundo email llegó
    Código de login: 654321
✓ Login con 2FA completado

[... Escenario 2 y 3 ...]

========================================
 RESUMEN DE TESTS
========================================

Escenario                          Estado   TiempoEmail Error
---------                          ------   ----------- -----
1️⃣  CON QUEUE WORKER             ✓ ÉXITO  4s          -
2️⃣  SIN QUEUE WORKER (Fallback)  ✓ ÉXITO  12s         -
3️⃣  QUEUE SE CAE A MITAD          ✓ ÉXITO  N/A         -

========================================
 ✓ TODOS LOS TESTS PASARON (3/3)
========================================
```

## 🎯 Checklist Final

Antes de considerar el sistema production-ready, verifica:

- [ ] ✅ Test 1 (con queue) pasa
- [ ] ✅ Test 2 (sin queue/fallback) pasa
- [ ] ✅ Test 3 (queue se cae) pasa
- [ ] ✅ Emails llegan en <30 segundos en todos los casos
- [ ] ✅ Logs no muestran errores críticos
- [ ] ✅ Health check detecta queue caído
- [ ] ✅ Sistema recupera automáticamente cuando queue vuelve
- [ ] ✅ No hay exposición de códigos en respuestas API
- [ ] ✅ Encriptación funciona (campos 2FA en DB)

## 📝 Logs a Revisar

Después de cada test:

```powershell
# Ver últimos 50 eventos de seguridad
cat storage/logs/security-*.log | Select-String "2FA" -Context 0,1 | Select-Object -Last 50

# Ver envíos de mail
cat storage/logs/mail-*.log | Select-String "enviado" -Context 0,1

# Ver detección de queue caído
cat storage/logs/queue-*.log | Select-String "caído\|fallback" -Context 0,1
```

---

**Estado:** Sistema probado en todos los escenarios 🚀
