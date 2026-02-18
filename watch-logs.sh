#!/bin/bash
# Script para ver logs de Laravel en tiempo real
# Ejecutar: bash watch-logs.sh

echo "═══════════════════════════════════════════════"
echo "Monitoreando logs de Laravel en tiempo real..."
echo "═══════════════════════════════════════════════"
echo ""
echo "Presiona Ctrl+C para detener"
echo ""
echo "Buscando líneas importantes:"
echo "  🔍 SESSION FLOW (SELECT BUDGET, SAVE SEASON, HOME)"
echo "  🔍 checkSelectedBudget middleware"
echo "  🔍 Purchase Order operations"
echo ""
echo "═══════════════════════════════════════════════"
echo ""

# Ver las últimas 50 líneas primero
echo "📋 ÚLTIMAS 50 LÍNEAS DEL LOG:"
echo "─────────────────────────────────────────────────"
tail -n 50 storage/logs/laravel.log
echo ""
echo "═══════════════════════════════════════════════"
echo "📡 MONITOREANDO EN TIEMPO REAL..."
echo "═══════════════════════════════════════════════"
echo ""

# Monitorear en tiempo real con colores
tail -f storage/logs/laravel.log | grep --line-buffered -E "═══|checkSelectedBudget|SAVE SEASON|SELECT BUDGET|HOME CONTROLLER|DEBUG UpdatePurchaseOrderStatus|PERMISSION DENIED"
