@echo off
REM Script para ver logs de Laravel en tiempo real
REM Ejecutar: watch-logs.bat

echo ===============================================
echo Monitoreando logs de Laravel en tiempo real...
echo ===============================================
echo.
echo Presiona Ctrl+C para detener
echo.
echo Buscando lineas importantes:
echo   - SESSION FLOW (SELECT BUDGET, SAVE SEASON, HOME)
echo   - checkSelectedBudget middleware
echo   - Purchase Order operations
echo.
echo ===============================================
echo.

echo ULTIMAS 100 LINEAS DEL LOG:
echo -----------------------------------------------
powershell -Command "Get-Content storage/logs/laravel.log -Tail 100"
echo.
echo ===============================================
echo MONITOREANDO EN TIEMPO REAL...
echo ===============================================
echo.

REM Monitorear en tiempo real
powershell -Command "Get-Content storage/logs/laravel.log -Wait -Tail 0 | Where-Object {$_ -match '═══|checkSelectedBudget|SAVE SEASON|SELECT BUDGET|HOME CONTROLLER|DEBUG UpdatePurchaseOrderStatus|PERMISSION DENIED'}"
