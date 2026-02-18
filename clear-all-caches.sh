#!/bin/bash
# Script de limpieza de cachés para debugging en producción
# Ejecutar: bash clear-all-caches.sh

echo "======================================"
echo "Limpiando TODOS los cachés..."
echo "======================================"

echo ""
echo "[1/5] Limpiando config cache..."
php artisan config:clear

echo "[2/5] Limpiando application cache..."
php artisan cache:clear

echo "[3/5] Limpiando route cache..."
php artisan route:clear

echo "[4/5] Limpiando view cache..."
php artisan view:clear

echo "[5/5] Limpiando optimize cache..."
php artisan optimize:clear

echo ""
echo "======================================"
echo "Verificando tabla sessions..."
echo "======================================"
php artisan tinker --execute="echo 'Sessions count: ' . DB::table('sessions')->count();"

echo ""
echo "======================================"
echo "Recreando cachés de configuración..."
echo "======================================"

echo "[1/3] Config cache..."
php artisan config:cache

echo "[2/3] Route cache..."
php artisan route:cache

echo "[3/3] Optimize..."
php artisan optimize

echo ""
echo "======================================"
echo "✅ COMPLETADO"
echo "======================================"
echo ""
echo "Ahora:"
echo "1. Cierra sesión en el navegador"
echo "2. Inicia sesión nuevamente"
echo "3. Selecciona la temporada"
echo "4. Prueba cambiar estado de Purchase Order"
echo "5. Revisa los logs: tail -f storage/logs/laravel.log"
echo ""
