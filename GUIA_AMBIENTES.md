# 🔒 Guía de Configuración de Ambientes

## 📋 Archivos Creados

1. **`.env`** - Configuración de DESARROLLO (APP_DEBUG=true)
2. **`.env.production`** - Configuración de PRODUCCIÓN (APP_DEBUG=false)
3. **`switch-env.bat`** - Script para cambiar entre ambientes

---

## 🚀 Cómo Usar

### **En DESARROLLO (Local - Tu computadora)**

Ya está configurado por defecto. No necesitas hacer nada.

```bash
# Verificar ambiente actual
php artisan env

# Tu .env actual tiene:
APP_ENV=local
APP_DEBUG=true
SESSION_LIFETIME=120
```

---

### **En PRODUCCIÓN (Servidor real)**

#### **Opción 1: Usando el script (Windows)**

```bash
# Ejecutar el script
switch-env.bat

# Seleccionar opción 2 (PRODUCCION)
# Confirmar con S
```

#### **Opción 2: Manual**

```bash
# 1. Hacer backup del .env actual
copy .env .env.backup

# 2. Copiar configuración de producción
copy .env.production .env

# 3. IMPORTANTE: Editar .env y ajustar:
#    - DB_DATABASE, DB_USERNAME, DB_PASSWORD (tu base de datos real)
#    - APP_URL (tu dominio real: https://tudominio.com)
#    - MAIL_* (configuración de correo real)

# 4. Optimizar Laravel para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## ⚙️ Diferencias Clave Entre Ambientes

| Configuración | DESARROLLO | PRODUCCIÓN |
|---------------|------------|------------|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` ✅ | `false` ❌ |
| `LOG_LEVEL` | `debug` | `error` |
| `SESSION_LIFETIME` | 120 min | 120 min |
| `SESSION_SECURE_COOKIE` | - | `true` (requiere HTTPS) |
| `SESSION_SAME_SITE` | - | `lax` |

---

## 📝 Antes de Subir a Producción

### **Checklist:**

- [ ] **Editar `.env.production`** con tus datos reales:
  ```env
  APP_URL=https://tu-dominio-real.com
  DB_DATABASE=tu_base_datos_produccion
  DB_USERNAME=tu_usuario_real
  DB_PASSWORD=tu_password_seguro
  ```

- [ ] **Verificar que tu servidor tenga HTTPS** (SSL configurado)

- [ ] **Probar la conexión a base de datos** antes de cambiar

- [ ] **Hacer backup de la base de datos** actual

---

## 🔄 Volver a Desarrollo

Si necesitas volver a modo desarrollo:

```bash
# Opción 1: Usar el script
switch-env.bat
# Seleccionar opción 1

# Opción 2: Manual
copy .env.backup .env
php artisan config:clear
php artisan cache:clear
```

---

## ⚠️ IMPORTANTE - Seguridad

1. **NUNCA subir `.env` a Git** (ya está en `.gitignore` ✓)

2. **`.env.production` es una PLANTILLA**
   - Personalízala según tu servidor
   - No compartas contraseñas reales en Git

3. **En producción SIEMPRE:**
   - `APP_DEBUG=false`
   - `APP_ENV=production`
   - Usa HTTPS (obligatorio)

---

## 🆘 Troubleshooting

### Error: "Base de datos no encontrada"
```bash
# Verificar configuración
php artisan config:clear
# Revisar .env y asegurar:
DB_HOST=127.0.0.1
DB_DATABASE=nombre_correcto
```

### Error: "Sesión expirada constantemente"
```bash
# En .env, ajustar:
SESSION_LIFETIME=120
```

### Cambios no se aplican
```bash
# Limpiar todas las cachés
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

## ✅ Resultado del Punto 1

**APP_DEBUG ahora funciona así:**
- 🟢 **Desarrollo:** `APP_DEBUG=true` (ves errores detallados)
- 🔴 **Producción:** `APP_DEBUG=false` (usuarios NO ven errores técnicos)

**Los errores en producción se guardan en:**
```
storage/logs/laravel.log
```

---

¿Necesitas ayuda? Revisa la documentación o consulta con el equipo de desarrollo.
