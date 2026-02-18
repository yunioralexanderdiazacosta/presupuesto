# Debugging de Sesión en Producción

## Problema
Error "No tiene permisos para modificar esta orden" SOLO en producción al cambiar estado de Purchase Order.

## Cambios Realizados

### 1. Logging Temporal Agregado
- **Archivo**: `UpdatePurchaseOrderStatusController.php`
- **Qué hace**: Registra en el log todos los valores de session('season_id'), team_id, etc.
- **IMPORTANTE**: Remover este logging después de debuggear

### 2. Bug Corregido en Middleware
- **Archivo**: `checkSelectedBudget.php`
- **Bug**: `$request->session()->has('season_id') == null` (incorrecto)
- **Fix**: `!$request->session()->has('season_id')` (correcto)
- **Por qué**: `has()` devuelve boolean, comparar con `== null` causa comportamiento inesperado

## Comandos a Ejecutar en Producción

### PASO 1: Subir cambios a producción
```bash
# En local, commitear y pushear
git add .
git commit -m "Debug: Add logging for session issues + fix middleware bug"
git push origin main

# En servidor de producción, hacer pull
cd /ruta/al/proyecto
git pull origin main
```

### PASO 2: Limpiar TODOS los cachés
```bash
# En servidor de producción
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### PASO 3: Verificar tabla sessions existe
```bash
php artisan tinker
```
Luego ejecutar dentro de tinker:
```php
DB::table('sessions')->count();
// Si da error "Table doesn't exist", ejecutar:
// exit
// php artisan session:table
// php artisan migrate
```

### PASO 4: Verificar configuración de sesión
Revisar `.env` en producción tiene:
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120   # Aumentar de 12 a 120 minutos
SESSION_DOMAIN=gestionagricola.cl
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

### PASO 5: Recrear caché de configuración
```bash
php artisan config:cache
php artisan route:cache
php artisan optimize
```

## Testing

### 1. Probar el flujo completo
1. Cerrar sesión completamente
2. Iniciar sesión nuevamente
3. Seleccionar temporada en el modal inicial
4. Ir a Purchase Orders
5. Intentar cambiar estado de una orden a "pending"

### 2. Revisar los logs
```bash
# Ver últimas líneas del log en tiempo real
tail -f storage/logs/laravel.log

# O copiar todo el log para revisarlo
cat storage/logs/laravel.log > debug_session.log
```

Buscar líneas que digan:
- `DEBUG UpdatePurchaseOrderStatus` - Verás todos los valores
- `PERMISSION DENIED UpdatePurchaseOrderStatus` - Si falla, verás por qué

### 3. Análisis de los logs
Si ves en el log:
- `session_season_id: null` → La sesión NO se está guardando o expiró
- `session_has_season_id: false` → La sesión no tiene la clave
- `purchase_order_season_id: 1` pero `session_season_id: 2` → Usuario cambió de temporada después de crear la orden

## Posibles Causas y Soluciones

### Causa 1: Tabla sessions no existe
**Síntoma**: `session_season_id` siempre es null
**Solución**:
```bash
php artisan session:table
php artisan migrate
php artisan config:cache
```

### Causa 2: Sesión expira muy rápido (12 minutos)
**Síntoma**: Funciona por poco tiempo luego falla
**Solución**: En `.env` cambiar `SESSION_LIFETIME=120`

### Causa 3: Cookies de sesión bloqueadas
**Síntoma**: La sesión se pierde entre requests
**Solución**: Verificar en `.env`:
```env
SESSION_DOMAIN=gestionagricola.cl
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

### Causa 4: Usuarios en diferentes temporadas
**Síntoma**: `purchase_order_season_id` ≠ `session_season_id`
**Solución**: El usuario debe seleccionar la misma temporada en que creó la orden

### Causa 5: Caché de configuración desactualizado
**Síntoma**: Comportamiento errático
**Solución**: Ejecutar PASO 2 y PASO 5

## Una Vez Resuelto

### Remover el logging temporal
En `UpdatePurchaseOrderStatusController.php`, eliminar las líneas de `\Log::info` y `\Log::error` que agregamos.

## Checklist de Verificación

- [ ] Código subido a producción
- [ ] Cachés limpiados (config, cache, route, view, optimize)
- [ ] Verificado que tabla `sessions` existe
- [ ] `.env` actualizado con `SESSION_LIFETIME=120`
- [ ] `.env` tiene `SESSION_DOMAIN` correcto
- [ ] Caché de configuración recreado
- [ ] Usuario cerró sesión y volvió a iniciar
- [ ] Usuario seleccionó temporada correcta
- [ ] Probado cambio de estado funciona
- [ ] Logs revisados para confirmar valores correctos
- [ ] Logging temporal removido del código

## Notas Adicionales

- **SESSION_LIFETIME**: 12 minutos es MUY corto. 120 minutos (2 horas) es más razonable
- **Driver database**: Asegura mejor persistencia en producción multi-servidor
- **Middleware bug**: El bug `== null` podría estar causando que usuarios sean redirigidos inesperadamente
- **First fix**: Corregir el middleware ya es un avance importante

## Contacto para Debugging

Si después de todo esto sigue fallando, copiar:
1. El bloque completo de `DEBUG UpdatePurchaseOrderStatus` del log
2. El bloque de `PERMISSION DENIED UpdatePurchaseOrderStatus` si aparece
3. El resultado de `php artisan tinker` ejecutando `session()->all()`

Esto dará información exacta de qué está pasando.
