@echo off
echo Eliminando archivos temporales del proyecto...

REM Eliminar carpetas temporales de PhpStorm
for /d %%d in (tmpclaude-*) do @rmdir /s /q "%%d" 2>nul

REM Eliminar archivos temporales
del /q test_api.php 2>nul
del /q diagnostico.php 2>nul
del /q check_vehiculos.php 2>nul
del /q analizar_excel.py 2>nul
del /q analisis_excel.json 2>nul
del /q temp_analisis_excel.py 2>nul
del /q temp_analisis_excel2.py 2>nul
del /q temp_analisis_excel3.py 2>nul

REM Eliminar carpeta combustible (obsoleta)
if exist combustible (
    rmdir /s /q combustible 2>nul
)

echo Limpieza completada!
echo.
echo Estructura actual:
dir /b /a:d
echo.
echo Archivos PHP principales:
dir /b *.php

pause
