#!/bin/bash
# File: security_audit.sh
# Descripción: Script de auditoría de seguridad
# Fecha: 2026-01-09

echo "========================================="
echo "  🛡️ AUDITORÍA DE SEGURIDAD"
echo "========================================="
echo ""

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Contador de problemas
issues=0

# 1. Verificar permisos de archivos
echo -e "${YELLOW}[1] Verificando permisos de archivos...${NC}"

files_to_check=(
    "config/database.php:600"
    ".env:600"
    "sessions:755"
    "logs:700"
    "uploads:755"
    "api/*.php:644"
    "modules/*.html:644"
    "assets/js/*.js:644"
)

for item in "${files_to_check[@]}"; do
    IFS=':' read -r file expected_perm <<< "$item"
    if [ -f "$file" ] || [ -d "$file" ]; then
        actual_perm=$(stat -c "%a" "$file" | tail -c 3)
        if [ "$actual_perm" != "$expected_perm" ]; then
            echo -e "  ${RED}✗ $file tiene permisos $actual_perm (esperado: $expected_perm)${NC}"
            ((issues++))
        else
            echo -e "  ${GREEN}✓ $file tiene permisos correctos ($actual_perm)${NC}"
        fi
    fi
done

echo ""

# 2. Verificar archivos .env
echo -e "${YELLOW}[2] Buscando archivos .env...${NC}"
if find . -name ".env" -o -name "*.env" 2>/dev/null | grep -q .env; then
    echo -e "  ${RED}✗ Archivo .env encontrado (debería estar excluido del git)${NC}"
    ((issues++))
else
    echo -e "  ${GREEN}✓ No hay archivos .env accesibles${NC}"
fi

echo ""

# 3. Verificar archivos sensibles en webroot
echo -e "${YELLOW}[3] Verificando archivos sensibles en webroot...${NC}"
sensitive_files=(
    ".git"
    ".env"
    "config.php"
    "database.php"
    "*.log"
)

for pattern in "${sensitive_files[@]}"; do
    if find public/ . -name "$pattern" 2>/dev/null | grep -q "$pattern"; then
        echo -e "  ${RED}✗ Archivo sensible encontrado en webroot: $pattern${NC}"
        ((issues++))
    fi
done

echo ""

# 4. Verificar headers de seguridad
echo -e "${YELLOW}[4] Verificando headers de seguridad...${NC}"
curl -I -s http://localhost/api/vehiculos.php 2>/dev/null | grep -E "(X-Frame-Options|X-XSS-Protection|Content-Security-Policy|Strict-Transport-Security)" > /dev/null
if [ $? -eq 0 ]; then
    echo -e "  ${GREEN}✓ Headers de seguridad presentes${NC}"
else
    echo -e "  ${RED}✗ Faltan headers de seguridad${NC}"
    ((issues++))
fi

echo ""

# 5. Verificar configuración PHP
echo -e "${YELLOW}[5] Verificando configuración PHP...${NC}"
php_inis=(
    "display_errors:Off"
    "expose_php:Off"
    "allow_url_fopen:Off"
    "allow_url_include:Off"
)

for setting in "${php_inis[@]}"; do
    IFS=':' read -r key expected <<< "$setting"
    actual=$(php -i | grep "^$key" | head -n 1 | cut -d ' ' -f 3)
    if [ "$actual" != "$expected" ]; then
        echo -e "  ${RED}✗ $key = $actual (debería ser $expected)${NC}"
        ((issues++))
    else
        echo -e "  ${GREEN}✓ $key = $actual${NC}"
    fi
done

echo ""

# 6. Verificar versiones
echo -e "${YELLOW}[6] Verificando versiones...${NC}"

PHP_VERSION=$(php -v | head -n 1 | cut -d ' ' -f 2)
MYSQL_VERSION=$(mysql --version | head -n 1 | cut -d ' ' -f 5 | cut -d ',' -f 1)

echo -e "  PHP: $PHP_VERSION"
echo -e "  MySQL: $MYSQL_VERSION"

# Check por versiones vulnerables
if [[ $(echo "$PHP_VERSION" | cut -d '.' -f1) -lt 7 ]]; then
    echo -e "  ${RED}✗ Versión de PHP antigua y potencialmente vulnerable${NC}"
    ((issues++))
fi

echo ""

# 7. Verificar configuración de base de datos
echo -e "${YELLOW}[7] Verificando configuración de base de datos...${NC}"
if grep -q "root@" config/database.php 2>/dev/null; then
    echo -e "  ${RED}✗ Se está usando usuario root en producción${NC}"
    ((issues++))
else
    echo -e "  ${GREEN}✓ No se usa usuario root${NC}"
fi

if grep -q "password=" config/database.php 2>/dev/null; then
    echo -e "  ${RED}✗ Contraseña en texto plano detectada${NC}"
    ((issues++))
fi

echo ""

# 8. Verificar rate limiting
echo -e "${YELLOW}[8] Verificando rate limiting...${NC}"
if mysql -u root -psecmautos -e "USE secmautos; SHOW TABLES LIKE 'rate_limits';" 2>/dev/null | grep -q rate_limits; then
    echo -e "  ${GREEN}✓ Tabla de rate limiting existe${NC}"
else
    echo -e "  ${RED}✗ No hay tabla de rate limiting${NC}"
    ((issues++))
fi

echo ""

# 9. Verificar HTTPS (en producción)
echo -e "${YELLOW}[9] Verificando HTTPS...${NC}"
curl -I -s https://secmautos.com 2>/dev/null | grep -q "HTTP/2"
if [ $? -eq 0 ]; then
    echo -e "  ${GREEN}✓ HTTPS habilitado${NC}"
else
    echo -e "  ${YELLOW}⚠ HTTPS no está habilitado (puede ser para desarrollo)${NC}"
fi

echo ""

# 10. Verificar CORS
echo -e "${YELLOW}[10] Verificando CORS...${NC}"
curl -I -s http://localhost/api/vehiculos.php 2>/dev/null | grep -q "Access-Control-Allow-Origin"
if [ $? -eq 0 ]; then
    echo -e "  ${YELLOW}⚠ CORS está habilitado (revisar si es necesario)${NC}"
else
    echo -e "  ${GREEN}✓ CORS no está habilitado${NC}"
fi

echo ""
echo "========================================="
echo "  📊 RESUMEN"
echo "========================================="
echo -e "Problemas encontrados: ${RED}$issues${NC}"

if [ $issues -eq 0 ]; then
    echo -e "${GREEN}✓ No se encontraron problemas críticos${NC}"
    exit 0
elif [ $issues -le 3 ]; then
    echo -e "${YELLOW}⚠ Se encontraron algunos problemas menores${NC}"
    exit 1
else
    echo -e "${RED}✗ Se encontraron múltiples problemas de seguridad${NC}"
    exit 2
fi
