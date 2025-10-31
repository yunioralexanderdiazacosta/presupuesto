# 🔑 Configuración de Ambientes - Resumen

## 📊 Diferencias Clave Entre Desarrollo y Producción

| Configuración | 🟢 DESARROLLO (.env) | 🔴 PRODUCCIÓN (.env.production) |
|---------------|---------------------|--------------------------------|
| **APP_ENV** | `local` | `production` |
| **APP_DEBUG** | `true` ✅ Muestra errores | `false` ❌ Oculta errores |
| **APP_URL** | `http://localhost:8000` | `https://gestionagricola.cl` |
| **LOG_LEVEL** | `debug` (todo) | `error` (solo errores) |
| **DB_DATABASE** | `presupuesto` | `gestio21_presupuesto` |
| **DB_USERNAME** | `root` | `gestio21_presupuesto` |
| **DB_PASSWORD** | `root` | `SMCangry.24` |
| **SESSION_SECURE_COOKIE** | - | `true` (requiere HTTPS) |
| **SESSION_SAME_SITE** | - | `lax` |

---

## ⚠️ ALERTA DE SEGURIDAD - PRODUCCIÓN ACTUAL

### 🔴 **CRÍTICO - Tu servidor en producción tiene:**

```env
APP_ENV=local          ❌ INCORRECTO
APP_DEBUG=true         ❌ MUY PELIGROSO
LOG_LEVEL=debug        ❌ INCORRECTO
```

**Esto significa que actualmente en gestionagricola.cl:**
- ❌ Los usuarios VEN errores técnicos completos
- ❌ Se exponen rutas de archivos del servidor
- ❌ Se muestran queries SQL
- ❌ Se revelan datos sensibles en caso de error

### ✅ **SOLUCIÓN - Debe tener:**

```env
APP_ENV=production     ✅ CORRECTO
APP_DEBUG=false        ✅ SEGURO
LOG_LEVEL=error        ✅ CORRECTO
```

---

## 🚀 Cómo Arreglarlo AHORA

### **Opción 1: Desde tu servidor (SSH/Terminal)**

```bash
# 1. Conectarte al servidor vía SSH
ssh usuario@gestionagricola.cl

# 2. Navegar a la carpeta de la aplicación
cd /home/gestio21/public_html  # O la ruta donde esté

# 3. Editar .env
nano .env

# 4. Cambiar estas 3 líneas:
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error

# 5. Guardar (Ctrl+O, Enter, Ctrl+X)

# 6. Limpiar caché
php artisan config:clear
php artisan cache:clear
php artisan optimize

# 7. Verificar
php artisan env
# Debe decir: "The application environment is [production]"
```

### **Opción 2: Desde cPanel (Más fácil)**

1. **Ir a cPanel** → File Manager
2. **Navegar** a la carpeta de tu aplicación
3. **Editar** el archivo `.env`
4. **Cambiar:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   LOG_LEVEL=error
   ```
5. **Guardar**
6. **Limpiar caché:**
   - cPanel → Terminal
   - `cd /ruta/aplicacion`
   - `php artisan config:clear`

### **Opción 3: Usar tu archivo .env.production preparado**

```bash
# En el servidor:
cp .env.production .env
php artisan config:cache
php artisan optimize
```

---

## ✅ Verificación Post-Cambio

### **1. Visitar un error intencionado**

Ir a: `https://gestionagricola.cl/ruta-que-no-existe`

**ANTES (Peligroso):**
```
NotFoundHttpException in RouteCollection.php line 161:
/var/www/html/vendor/laravel/framework/src/...
```

**DESPUÉS (Seguro):**
```
404 | Página no encontrada
(Sin detalles técnicos)
```

### **2. Verificar logs**

```bash
# En el servidor:
tail -20 storage/logs/laravel.log
# Debe tener los errores, pero NO se muestran al público
```

---

## 📋 Checklist de Seguridad

- [❌] **APP_DEBUG=false en producción**
- [❌] **APP_ENV=production**
- [❌] **LOG_LEVEL=error**
- [✅] **Credenciales de BD correctas**
- [✅] **SESSION_LIFETIME=120**
- [⚠️] **SESSION_SECURE_COOKIE=true** (necesita HTTPS activo)
- [⚠️] **SESSION_SAME_SITE=lax**

---

## 🎯 Próximo Paso

Una vez que corrijas estos 3 valores en producción:
```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
```

Podremos continuar con:
- ✅ **Punto 2:** Rate Limiting (anti fuerza bruta)
- ✅ **Punto 3:** Headers de seguridad
- ✅ **Punto 4:** CORS configuración

---

## 📞 Si Necesitas Ayuda

**Para cambiar en producción ahora:**
1. Ve a cPanel de tu hosting
2. File Manager → navega a la carpeta
3. Edita `.env`
4. Cambia las 3 líneas mencionadas
5. Guarda

**O avísame y te guío paso a paso.** 🚀

---

✅ **IMPORTANTE:** Haz este cambio lo antes posible, es una vulnerabilidad de seguridad activa.
