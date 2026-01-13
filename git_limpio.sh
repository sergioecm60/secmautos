#!/bin/bash
# Script para hacer un push limpio a Git

echo "=== Limpiando archivos temporales y preparando para Git ==="

# 1. Limpiar archivos temporales
echo "1. Eliminando archivos temporales..."
rm -f test_api.php
rm -f diagnostico.php
rm -f check_vehiculos.php
rm -f analizar_excel.py
rm -f analisis_excel.json
rm -f temp_analisis_excel.py
rm -f temp_analisis_excel2.py
rm -f temp_analisis_excel3.py

# 2. Eliminar carpetas temporales de PhpStorm
echo "2. Eliminando carpetas temporales..."
rm -rf tmpclaude-*

# 3. Verificar que .env no esté en el repositorio
echo "3. Verificando archivos sensibles..."
if [ -f .env ]; then
    echo "   ✅ .env existe (asegúrate de que esté en .gitignore)"
fi

# 4. Mostrar estado de Git
echo "4. Estado actual de Git:"
git status

echo ""
echo "=== Archivos preparados para commit ==="
git status --short | grep -E "^A|^M" || echo "Ningún archivo modificado"

echo ""
echo "=== Siguientes pasos ==="
echo "1. Revisar los cambios:"
echo "   git status"
echo ""
echo "2. Agregar archivos al staging:"
echo "   git add ."
echo ""
echo "3. Crear commit:"
echo "   git commit -m 'refactor: mover API combustible y limpiar archivos temporales'"
echo ""
echo "4. Push al repositorio:"
echo "   git push origin main"
echo ""
echo "   (o tu branch actual)"
