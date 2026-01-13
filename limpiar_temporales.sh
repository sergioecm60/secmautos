#!/bin/bash
echo "Limpiando archivos temporales del proyecto..."

# Eliminar carpetas temporales de PhpStorm
rm -rf tmpclaude-*

# Eliminar archivos temporales
rm -f test_api.php
rm -f diagnostico.php
rm -f check_vehiculos.php
rm -f analizar_excel.py
rm -f analisis_excel.json
rm -f temp_analisis_excel.py
rm -f temp_analisis_excel2.py
rm -f temp_analisis_excel3.py

# Eliminar carpeta combustible (obsoleta)
rm -rf combustible

echo "Limpieza completada!"
echo ""
echo "Estructura actual:"
ls -d */ 2>/dev/null | grep -v "^\\."
echo ""
echo "Archivos PHP principales:"
ls -1 *.php 2>/dev/null | head -10
