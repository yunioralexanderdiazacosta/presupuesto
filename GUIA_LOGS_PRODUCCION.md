# 📊 Guía Rápida: Ver Logs en Producción

## 🚀 Inicio Rápido

### Opción 1: Script Automático (Recomendado)
```bash
# En servidor Linux/Mac
bash watch-logs.sh

# En servidor Windows
watch-logs.bat
```

### Opción 2: Comandos Manuales

#### Ver últimas líneas del log
```bash
# Linux/Mac
tail -n 100 storage/logs/laravel.log

# Windows PowerShell
Get-Content storage/logs/laravel.log -Tail 100
```

#### Monitorear en tiempo real
```bash
# Linux/Mac
tail -f storage/logs/laravel.log

# Windows PowerShell
Get-Content storage/logs/laravel.log -Wait
```

#### Filtrar solo líneas importantes
```bash
# Linux/Mac
tail -f storage/logs/laravel.log | grep "═══"

# Windows PowerShell
Get-Content storage/logs/laravel.log -Wait | Where-Object {$_ -match "═══"}
```

## 🔍 Qué Buscar en los Logs

### 1. Flujo de Login Normal
Deberías ver esta secuencia después del login:

```
[timestamp] ═══ SELECT BUDGET CONTROLLER ═══
  - user_id: 1
  - current_session_season_id: null
  - has_session_season_id: false

[timestamp] ═══ SAVE SEASON CONTROLLER START ═══
  - request_season_id: 3

[timestamp] ═══ SAVE SEASON CONTROLLER - SESSION SAVED ═══
  - session_season_id: 3
  - session_has_season_id: true

[timestamp] ═══ HOME CONTROLLER ═══
  - session_season_id: 3
  - has_session_season_id: true
```

✅ **Esto es CORRECTO**

### 2. Problema: Sesión No Se Guarda
Si ves esto:

```
[timestamp] ═══ SAVE SEASON CONTROLLER - SESSION SAVED ═══
  - session_season_id: 3
  - session_has_season_id: true

[timestamp] ═══ HOME CONTROLLER ═══
  - session_season_id: null  ⚠️ CAMBIÓ A NULL
  - has_session_season_id: false
```

❌ **PROBLEMA**: La sesión se guardó pero se perdió en el siguiente request
**Causa**: Problema con driver de sesión o tabla `sessions` no existe

### 3. Problema: Loop de Redirección
Si ves esto repitiéndose:

```
[timestamp] checkSelectedBudget: REDIRECTING to select.budget
[timestamp] checkSelectedBudget: REDIRECTING to select.budget
[timestamp] checkSelectedBudget: REDIRECTING to select.budget
```

❌ **PROBLEMA**: Loop infinito de redirecciones
**Causa**: Middleware se ejecuta en ruta que debería estar excluida

### 4. Problema: Permission Denied en Purchase Orders
```
[timestamp] DEBUG UpdatePurchaseOrderStatus
  - purchase_order_season_id: 3
  - session_season_id: null  ⚠️ O diferente

[timestamp] PERMISSION DENIED UpdatePurchaseOrderStatus
  - reason: "season_id mismatch"
```

❌ **PROBLEMA**: Usuario no tiene `season_id` en sesión o cambió de temporada
**Causa**: Sesión expirada o usuario seleccionó otra temporada

## 🛠️ Soluciones Según el Caso

### Caso 1: Sesión se pierde entre requests
```bash
# Verificar que tabla sessions existe
php artisan tinker
> DB::table('sessions')->count();

# Si da error, crear tabla
php artisan session:table
php artisan migrate
php artisan config:cache
```

### Caso 2: Verificar configuración de sesión
Revisar `.env`:
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=.gestionagricola.cl
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

Después de cambiar `.env`:
```bash
php artisan config:clear
php artisan config:cache
```

### Caso 3: Limpiar cachés y sesiones viejas
```bash
php artisan config:clear
php artisan cache:clear
php artisan session:flush
php artisan optimize:clear
```

### Caso 4: Verificar permisos de storage
```bash
# Linux/Mac
chmod -R 775 storage
chown -R www-data:www-data storage

# Verificar que se puedan escribir archivos
touch storage/logs/test.log
```

## 📋 Checklist de Debugging

Cuando el usuario reporta problema, pide:

1. ✅ **Copiar bloque completo** de los logs (desde `═══ SELECT BUDGET` hasta `═══ HOME CONTROLLER`)
2. ✅ **Verificar** que `session_has_season_id` cambia de `false` a `true` en SAVE SEASON
3. ✅ **Verificar** que `session_season_id` se mantiene en HOME CONTROLLER
4. ✅ **Revisar** si hay warnings o errors entre los bloques `═══`

## 📁 Ubicación de Logs

- **Local**: `c:\MAMP\htdocs\presupuesto\storage\logs\laravel.log`
- **Producción**: `/ruta/servidor/storage/logs/laravel.log`

## 🗑️ Limpiar Logs Viejos

```bash
# Vaciar el log completamente (hacer backup primero)
cp storage/logs/laravel.log storage/logs/laravel-backup-$(date +%Y%m%d).log
> storage/logs/laravel.log

# O borrar logs viejos (mantener solo hoy)
echo "" > storage/logs/laravel.log
```

## 🎯 Ejemplo de Análisis Completo

### Usuario reporta: "No puedo cambiar estado de Purchase Order"

**Paso 1**: Ver el log
```bash
tail -f storage/logs/laravel.log | grep "DEBUG UpdatePurchaseOrderStatus" -A 10
```

**Paso 2**: Analizar valores
```
DEBUG UpdatePurchaseOrderStatus
  purchase_order_season_id: 3
  session_season_id: null  ⚠️
```

**Paso 3**: Verificar por qué session es null
```bash
tail -f storage/logs/laravel.log | grep "HOME CONTROLLER" -A 5
```

Si en HOME CONTROLLER también es null → Sesión no se guardó o se perdió

**Paso 4**: Verificar SAVE SEASON
```bash
tail -f storage/logs/laravel.log | grep "SAVE SEASON" -A 10
```

Si `session_has_season_id: true` aquí → La sesión se guardó correctamente

**Paso 5**: Conclusión
Si se guardó en SAVE SEASON pero está null en HOME → Driver de sesión no persiste

**Solución**: Verificar tabla sessions y configuración SESSION_DRIVER

## ⚠️ Importante: Remover Logging Temporal

Una vez resuelto el problema, remover las líneas de `\Log::info` de:
- `SaveSeasonController.php`
- `checkSelectedBudget.php`
- `SelectBudgetController.php`
- `HomeController.php`
- `UpdatePurchaseOrderStatusController.php`

Para evitar llenar el log innecesariamente en producción.
