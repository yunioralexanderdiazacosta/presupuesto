@echo off
REM ====================================================================
REM Script para cambiar entre ambientes DESARROLLO y PRODUCCION
REM ====================================================================

echo.
echo ========================================
echo   CAMBIO DE AMBIENTE
echo ========================================
echo.
echo 1. DESARROLLO (local)
echo 2. PRODUCCION
echo.

set /p choice="Selecciona el ambiente (1 o 2): "

if "%choice%"=="1" (
    echo.
    echo Copiando configuracion de DESARROLLO...
    copy /Y .env.example .env.backup 2>nul
    echo.
    echo APP_DEBUG=true
    echo APP_ENV=local
    echo.
    echo ✓ Ambiente configurado: DESARROLLO
    echo.
    echo Ejecutando comandos de optimizacion...
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    echo.
    echo ✓ LISTO! Tu aplicacion esta en modo DESARROLLO
) else if "%choice%"=="2" (
    echo.
    echo ⚠️  ATENCION: Cambiando a PRODUCCION
    echo.
    set /p confirm="¿Estas seguro? Esta accion deshabilitara DEBUG (S/N): "
    
    if /i "%confirm%"=="S" (
        echo.
        echo Copiando configuracion de PRODUCCION...
        copy /Y .env .env.backup
        copy /Y .env.production .env
        echo.
        echo APP_DEBUG=false
        echo APP_ENV=production
        echo.
        echo ✓ Ambiente configurado: PRODUCCION
        echo.
        echo Ejecutando comandos de optimizacion...
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan optimize
        echo.
        echo ✓ LISTO! Tu aplicacion esta en modo PRODUCCION
        echo.
        echo IMPORTANTE:
        echo - Se creo backup en .env.backup
        echo - Verifica la conexion a base de datos
        echo - Asegurate de tener HTTPS configurado
    ) else (
        echo.
        echo Operacion cancelada.
    )
) else (
    echo.
    echo ❌ Opcion invalida
)

echo.
pause
