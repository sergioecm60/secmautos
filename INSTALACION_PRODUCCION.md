# Guía de Instalación - Secmautos (Ubuntu 24 + Nginx + PHP + MySQL)

## Requisitos

- Ubuntu Server 24.04 LTS
- Nginx 1.18+
- PHP 8.1+ (8.2+ recomendado)
- MySQL 8.0+ o MariaDB 10.11+
- Git
- Composer (opcional, para gestión de dependencias)
- Acceso SSH al servidor
- Dominio o IP pública

---

## 1. Actualizar el sistema

```bash
sudo apt update && sudo apt upgrade -y
sudo apt autoremove -y
```

---

## 2. Instalar Nginx

```bash
sudo apt install nginx -y
sudo systemctl enable nginx
sudo systemctl start nginx
```

Verificar: `http://TU_IP` (debería ver "Welcome to nginx")

---

## 3. Instalar PHP y extensiones

```bash
sudo apt install php8.2-fpm php8.2-mysql php8.2-curl php8.2-gd php8.2-mbstring php8.2-xml php8.2-zip php8.2-intl -y

# Habilitar extensiones necesarias
sudo phpenmod pdo_mysql mbstring curl gd xml zip intl
```

---

## 4. Instalar MySQL/MariaDB

```bash
# Opción A: MySQL 8
sudo apt install mysql-server -y
sudo systemctl enable mysql
sudo systemctl start mysql

# Opción B: MariaDB 10.11 (recomendado)
sudo apt install mariadb-server mariadb-client -y
sudo systemctl enable mariadb
sudo systemctl start mariadb
```

### Configurar MySQL/MariaDB

```bash
sudo mysql_secure_installation
```

Responder:
- Enter current password: (Enter vacío)
- Set root password: `TU_PASSWORD_SEGURO`
- Remove anonymous users: Y
- Disallow root login remotely: Y
- Remove test database: Y
- Reload privilege tables: Y

---

## 5. Crear base de datos y usuario

```bash
sudo mysql -u root -p
```

Ejecutar en MySQL:

```sql
CREATE DATABASE secmautos CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
CREATE USER 'secmautos_user'@'localhost' IDENTIFIED BY 'PASSWORD_MUY_SEGURO';
GRANT ALL PRIVILEGES ON secmautos.* TO 'secmautos_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 6. Importar la base de datos

```bash
# Copiar el archivo SQL al servidor (usando scp o FileZilla)
# Luego ejecutar:
mysql -u secmautos_user -p secmautos < db/install.sql
```

O desde el repositorio:

```bash
mysql -u secmautos_user -p secmautos < db/install.sql
```

---

## 7. Crear estructura de directorios

```bash
sudo mkdir -p /var/www/secmautos
sudo chown -R $USER:$USER /var/www/secmautos
cd /var/www/secmautos
```

---

## 8. Clonar el proyecto (o subir archivos)

**Opción A: Desde Git (recomendado)**

```bash
git clone TU_REPO_GIT .
# O si el repo ya tiene otros proyectos:
git clone TU_REPO_GIT temp
mv temp/* temp/.* .
rmdir temp
```

**Opción B: Subir archivos (SCP/SFTP)**

```bash
# Desde tu PC local
scp -r C:\laragon\www\secmautos/* usuario@TU_IP:/var/www/secmautos/
```

---

## 9. Configurar permisos

```bash
sudo chown -R www-data:www-data /var/www/secmautos
sudo find /var/www/secmautos -type d -exec chmod 755 {} \;
sudo find /var/www/secmautos -type f -exec chmod 644 {} \;

# Permisos especiales para writable:
sudo chmod -R 775 logs sessions assets/img/uploads
sudo chown -R www-data:www-data logs sessions assets/img/uploads
```

---

## 10. Configurar archivo .env

```bash
cd /var/www/secmautos
cp .env.example .env
nano .env
```

Editar:

```env
# Base de Datos
DB_HOST=localhost
DB_NAME=secmautos
DB_USER=secmautos_user
DB_PASS=PASSWORD_MUY_SEGURO

# Configuración de Aplicación
APP_URL=https://tudominio.com
APP_ENV=production
```

---

## 11. Configurar PHP-FPM

```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

Cambiar:
- `user = www-data`
- `user = www-data`
- `listen = /var/run/php/php8.2-fpm.sock`

Reiniciar:
```bash
sudo systemctl restart php8.2-fpm
```

---

## 12. Configurar Nginx

```bash
sudo nano /etc/nginx/sites-available/secmautos
```

Crear el siguiente contenido:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tudominio.com www.tudominio.com;
    root /var/www/secmautos;
    index index.php index.html;

    # Límites de subida (ajustar según necesidad)
    client_max_body_size 100M;
    client_body_timeout 300s;

    # Logs
    access_log /var/log/nginx/secmautos_access.log;
    error_log /var/log/nginx/secmautos_error.log;

    # Bloquear acceso a archivos sensibles
    location ~ /\. {
        deny all;
    }
    
    location ~ /(composer\.json|package\.json|\.env|\.git) {
        deny all;
    }

    # Servir archivos estáticos
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # PHP
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Bloquear acceso directo a API sin autenticación (excepto login)
    location ~ ^/api/(login\.php|logout\.php) {
        try_files $uri =404;
    }

    location ~ ^/api/ {
        # La autenticación se maneja en bootstrap.php
        try_files $uri =404;
    }

    # Todo lo demás
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

Activar el sitio:

```bash
sudo ln -s /etc/nginx/sites-available/secmautos /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 13. Configurar SSL con Let's Encrypt (HTTPS - RECOMENDADO)

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx -y

# Obtener certificado SSL
sudo certbot --nginx -d tudominio.com -d www.tudominio.com

# Renovación automática
sudo certbot renew --dry-run
```

---

## 14. Configurar firewall (UFW)

```bash
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable
```

---

## 15. Verificar instalación

```bash
# Verificar PHP-FPM
sudo systemctl status php8.2-fpm

# Verificar Nginx
sudo systemctl status nginx

# Verificar MySQL/MariaDB
sudo systemctl status mysql
# o
sudo systemctl status mariadb

# Probar conexión a la base de datos
mysql -u secmautos_user -p secmautos -e "SELECT 1;"
```

---

## 16. Crear el primer usuario administrador

```bash
mysql -u secmautos_user -p secmautos
```

```sql
INSERT INTO usuarios (usuario, password, nombre_completo, email, rol, activo, fecha_creacion)
VALUES (
    'admin',
    '$2y$10$HASH_DE_PASSWORD_BCRIPT',  -- Generar con password_hash() en PHP
    'Administrador',
    'admin@tudominio.com',
    'admin',
    1,
    NOW()
);
```

Para generar el hash BCrypt: http://bcrypt-generator.com/

---

## 17. Pruebas

1. **Navegar al sitio**: `https://tudominio.com`
2. **Login con usuario admin**
3. **Verificar todos los módulos funcionan**
4. **Probar registro de vehículos, empleados, etc.**
5. **Verificar logs**:

```bash
tail -f /var/log/nginx/secmautos_error.log
tail -f /var/www/secmautos/logs/error.log
```

---

## 18. Configurar backup automático

```bash
sudo nano /usr/local/bin/backup_secmautos.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/secmautos"
mkdir -p $BACKUP_DIR

# Backup de base de datos
mysqldump -u secmautos_user -p'PASSWORD' secmautos | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup de archivos (opcional)
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/secmautos

# Eliminar backups de más de 30 días
find $BACKUP_DIR -type f -mtime +30 -delete
```

Dar permisos y agregar al cron:

```bash
sudo chmod +x /usr/local/bin/backup_secmautos.sh
sudo crontab -e
```

Agregar:
```bash
0 2 * * * /usr/local/bin/backup_secmautos.sh
```

---

## Solución de problemas

### Error 502 Bad Gateway
```bash
sudo systemctl restart php8.2-fpm
sudo tail -f /var/log/nginx/error.log
```

### Error 504 Gateway Timeout
En nginx.conf: aumentar `fastcgi_read_timeout` y `client_body_timeout`

### Permisos denegados
```bash
sudo chown -R www-data:www-data /var/www/secmautos
```

### PHP no funciona
```bash
sudo systemctl status php8.2-fpm
sudo tail -f /var/log/php8.2-fpm.log
```

---

## Monitorización básica

```bash
# Uso de disco
df -h

# Uso de memoria
free -h

# Uso de CPU
htop  # instalar con: sudo apt install htop -y

# Logs de errores
tail -f /var/log/nginx/error.log
```

---

## Seguridad adicional

1. **Instalar Fail2ban**:
```bash
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

2. **Ocultar versión de PHP/Nginx** (en nginx.conf):
```nginx
server_tokens off;
```

3. **Actualizaciones automáticas** (opcional):
```bash
sudo apt install unattended-upgrades -y
sudo dpkg-reconfigure -plow unattended-upgrades
```

---

## Actualizaciones

```bash
# Actualizar código
cd /var/www/secmautos
git pull origin main

# O descargar y reemplazar archivos

# Limpiar caché (si aplica)
sudo chown -R www-data:www-data .
```

---

## Soporte

- Revisar logs: `/var/log/nginx/` y `/var/www/secmautos/logs/`
- Documentación del proyecto: `README.md`
- Configuración de ejemplo: `nginx.conf.example`
