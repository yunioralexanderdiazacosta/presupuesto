# 🚀 Guía de Despliegue a Producción

## 📋 Pasos para Desplegar

### 1️⃣ Preparar el Servidor

```bash
# Clonar repositorio
git clone [tu-repositorio-url]
cd presupuesto

# Copiar archivo de configuración
cp .env.example .env
```

### 2️⃣ Configurar Variables de Entorno (.env)

Edita el archivo `.env` y actualiza estas variables **OBLIGATORIAS**:

```env
# Entorno
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Generar nueva key (ver comando más abajo)
APP_KEY=

# Base de datos de producción
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_bd_produccion
DB_USERNAME=usuario_produccion
DB_PASSWORD=contraseña_segura

# API de Mindee OCR (¡IMPORTANTE!)
MINDEE_API_KEY=tu_api_key_de_mindee_produccion

# API del Clima (opcional)
WEATHER_API_KEY=tu_weather_api_key
```

### 3️⃣ Ejecutar Comandos de Instalación

```bash
# Instalar dependencias PHP
composer install --no-dev --optimize-autoloader

# Generar clave de aplicación
php artisan key:generate

# Instalar dependencias JavaScript
npm install

# Compilar assets para producción
npm run build

# Ejecutar migraciones
php artisan migrate --force

# Cachear configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimizar autoload
composer dump-autoload -o

# Configurar permisos
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 4️⃣ Configurar Servidor Web (Nginx/Apache)

**Document Root:** `/ruta/a/presupuesto/public`

**Ejemplo Nginx:**
```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /var/www/presupuesto/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 5️⃣ Verificar Instalación

```bash
# Verificar versión de PHP (requiere >= 8.1)
php -v

# Verificar extensiones requeridas
php -m | grep -E '(pdo|mbstring|tokenizer|xml|ctype|json|bcmath|fileinfo)'

# Verificar permisos
ls -la storage/
ls -la bootstrap/cache/

# Probar conexión a base de datos
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## 🔐 Configuración de Mindee API

### Obtener API Key

1. Ve a [Mindee Platform](https://platform.mindee.com/)
2. Inicia sesión o crea cuenta
3. Ve a **API Keys** en el menú
4. Copia tu API Key de producción
5. Pégala en `.env`:
   ```
   MINDEE_API_KEY=md_tu_api_key_aqui
   ```

### Verificar Configuración

```bash
# Verificar que la API Key está configurada
php artisan tinker
>>> config('services.mindee.api_key');
```

---

## ⚙️ Mantenimiento

### Actualizar Aplicación

```bash
# Descargar cambios
git pull origin main

# Actualizar dependencias
composer install --no-dev
npm install
npm run build

# Migrar base de datos
php artisan migrate --force

# Limpiar y recachear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Logs y Debugging

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Limpiar logs antiguos
> storage/logs/laravel.log
```

---

## 🚨 Troubleshooting

### Error: "API Key no configurada"
✅ Verifica que `MINDEE_API_KEY` esté en `.env`
✅ Ejecuta `php artisan config:cache`

### Error: "No se puede escribir en storage"
✅ Ejecuta `chmod -R 775 storage`
✅ Ejecuta `chown -R www-data:www-data storage`

### Error: "Base de datos no conecta"
✅ Verifica credenciales en `.env`
✅ Verifica que MySQL esté corriendo
✅ Verifica firewall/puerto 3306

### OCR no funciona
✅ Verifica que el servidor tenga acceso a internet
✅ Verifica que la API Key tenga créditos
✅ Revisa logs: `storage/logs/laravel.log`

---

## 📊 Requisitos del Servidor

- **PHP:** >= 8.1
- **Composer:** >= 2.0
- **Node.js:** >= 18.x
- **MySQL:** >= 8.0
- **Extensiones PHP:**
  - BCMath
  - Ctype
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML
  - GD o Imagick

---

## ✅ Checklist Final

- [ ] `.env` configurado con todas las variables
- [ ] `APP_KEY` generada
- [ ] `MINDEE_API_KEY` configurada
- [ ] Base de datos creada y migrada
- [ ] Permisos de `storage/` configurados
- [ ] Assets compilados (`npm run build`)
- [ ] Caché optimizado
- [ ] Servidor web configurado
- [ ] HTTPS configurado (Let's Encrypt recomendado)
- [ ] Backup automático configurado
