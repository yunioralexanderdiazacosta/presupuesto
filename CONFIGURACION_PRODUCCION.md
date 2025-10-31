# 🚀 Configuración de Producción - gestionagricola.cl

## ⚙️ Credenciales a Configurar

Antes de subir a producción en `gestionagricola.cl`, debes editar `.env.production` y completar:

### 1️⃣ **Base de Datos**

```env
DB_HOST=localhost
DB_DATABASE=gestionagricola_db
DB_USERNAME=gestionagricola_user
DB_PASSWORD=CAMBIAR_PASSWORD_SEGURO_AQUI  ⬅️ COMPLETAR CON PASSWORD REAL
```

**Cómo obtener estas credenciales:**
- Ingresa a tu panel de hosting (cPanel, Plesk, etc.)
- Ve a "MySQL Databases" o "Bases de Datos"
- Ahí encontrarás el nombre de la BD, usuario y puedes crear/ver el password

---

### 2️⃣ **Configuración de Correo**

```env
MAIL_HOST=smtp.gestionagricola.cl
MAIL_USERNAME=noreply@gestionagricola.cl
MAIL_PASSWORD=CAMBIAR_PASSWORD_EMAIL_AQUI  ⬅️ COMPLETAR CON PASSWORD REAL
```

**Opciones comunes:**

#### **Opción A: Usar correo del hosting**
```env
MAIL_HOST=smtp.gestionagricola.cl  # o mail.gestionagricola.cl
MAIL_PORT=587
MAIL_USERNAME=noreply@gestionagricola.cl
MAIL_PASSWORD=tu_password_de_email
MAIL_ENCRYPTION=tls
```

#### **Opción B: Usar Gmail (para pruebas)**
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=contraseña-de-aplicacion  # No la password normal, ver: https://myaccount.google.com/apppasswords
MAIL_ENCRYPTION=tls
```

#### **Opción C: Usar Mailtrap/SendGrid (recomendado)**
```env
# SendGrid (gratis hasta 100 correos/día)
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=TU_API_KEY_SENDGRID
MAIL_ENCRYPTION=tls
```

---

## 🔑 APP_KEY

**IMPORTANTE:** Ya tienes configurado:
```env
APP_KEY=base64:V1hWitKJIZJhgbpXwdG3NQJbtBs1Gz4KKRA1S10/9zk=
```

✅ **NO LO CAMBIES** - Está bien así. Es el mismo para desarrollo y producción.

---

## 📋 Checklist Antes de Deployment

- [ ] **Completar credenciales de Base de Datos**
  ```bash
  # Probar conexión:
  mysql -h localhost -u gestionagricola_user -p gestionagricola_db
  ```

- [ ] **Completar credenciales de Email**

- [ ] **Verificar que el dominio tenga SSL (HTTPS)**
  - Ir a: https://gestionagricola.cl
  - Debe mostrar candado 🔒
  - Si no tiene SSL, activarlo en cPanel → SSL/TLS

- [ ] **Verificar permisos de carpetas en servidor**
  ```bash
  chmod -R 755 storage bootstrap/cache
  chmod -R 644 .env.production
  ```

- [ ] **Hacer backup de base de datos actual**

---

## 🚀 Pasos para Subir a Producción

### **1. Preparar localmente**
```bash
# Asegurar que todo funcione en local
php artisan config:clear
php artisan cache:clear
npm run build
```

### **2. Subir archivos al servidor**
- Subir TODOS los archivos vía FTP/SSH
- **EXCEPTO:** `node_modules`, `.env`, `.git`

### **3. En el servidor, configurar .env**
```bash
# Copiar configuración de producción
cp .env.production .env

# Editar con credenciales REALES
nano .env
# o usar el editor de archivos de cPanel
```

### **4. Configurar permisos**
```bash
chmod -R 755 storage bootstrap/cache
chmod 644 .env
```

### **5. Instalar dependencias**
```bash
# Composer
composer install --optimize-autoloader --no-dev

# Si usas Node/NPM en servidor:
npm install --production
npm run build
```

### **6. Migrar base de datos**
```bash
php artisan migrate --force
php artisan db:seed --force  # Solo si necesitas seeders
```

### **7. Optimizar Laravel**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### **8. Configurar .htaccess**
Asegurar que el archivo `public/.htaccess` tenga:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## 🔍 Verificación Post-Deploy

1. **Visitar el sitio**
   - https://gestionagricola.cl
   - Debe cargar sin errores

2. **Login funcional**
   - Probar iniciar sesión
   - Crear usuario de prueba

3. **Revisar logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Verificar que NO se vean errores técnicos**
   - Si hay error, debe mostrar página genérica
   - NO debe mostrar stack trace

---

## 🆘 Troubleshooting

### Error 500 - Internal Server Error
```bash
# Ver logs
tail -50 storage/logs/laravel.log

# Verificar permisos
chmod -R 755 storage bootstrap/cache

# Limpiar caché
php artisan config:clear
php artisan cache:clear
```

### No se conecta a la base de datos
```bash
# Verificar credenciales en .env
cat .env | grep DB_

# Probar conexión manual
mysql -h localhost -u gestionagricola_user -p gestionagricola_db
```

### Sesiones no funcionan
```bash
# Verificar tabla sessions existe
php artisan migrate

# Limpiar sesiones
php artisan session:table
php artisan migrate
```

---

## 📞 Contacto con Hosting

Si tienes problemas, contacta a tu proveedor de hosting con:

1. **Nombre del dominio:** gestionagricola.cl
2. **Tecnología:** Laravel 10 + PHP 8.1+
3. **Requisitos:**
   - PHP >= 8.1
   - MySQL/MariaDB
   - Composer
   - Extensiones: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

---

✅ **Todo listo para producción con gestionagricola.cl**
