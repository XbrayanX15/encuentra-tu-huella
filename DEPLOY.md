# Encuentra Tu Huella - Deploy Instructions

## Requisitos del Servidor
- PHP 8.0 o superior
- PostgreSQL 12 o superior
- Extensiones PHP: pdo, pdo_pgsql, gd, mbstring

## Deploy en Railway (Recomendado)

### 1. Preparar el proyecto
```bash
# 1. Exportar base de datos local
./export_db.ps1  # En Windows PowerShell
# o
./export_db.sh   # En Linux/Mac

# 2. Inicializar repositorio Git (si no lo tienes)
git init
git add .
git commit -m "Initial commit"
```

### 2. Deploy en Railway
1. Ve a [railway.app](https://railway.app)
2. Conecta tu cuenta de GitHub
3. Crea nuevo proyecto
4. Selecciona "Deploy from GitHub repo"
5. Conecta tu repositorio
6. Railway detectará automáticamente que es PHP

### 3. Configurar PostgreSQL
1. En el dashboard de Railway, click "New Service"
2. Selecciona "PostgreSQL"
3. Railway creará automáticamente las variables de entorno

### 4. Configurar Variables de Entorno
En Railway, ve a tu servicio → Variables:
```
DB_HOST=<postgresql-host>
DB_NAME=<database-name>
DB_USER=<username>
DB_PASS=<password>
DB_PORT=5432
DATABASE_URL=<se-genera-automaticamente>
```

### 5. Importar Base de Datos
1. Conecta a la base de datos de Railway usando psql o pgAdmin
2. Ejecuta el archivo `database_export/full_backup.sql`

```bash
# Desde línea de comandos
psql $DATABASE_URL < database_export/full_backup.sql
```

## Deploy en Hosting Compartido

### Si tu hosting soporta PostgreSQL:
1. Sube archivos por FTP
2. Crea base de datos PostgreSQL en cPanel
3. Importa el archivo SQL
4. Configura `config/database.php` con los datos del hosting

### Si solo tiene MySQL:
Necesitarás migrar la base de datos de PostgreSQL a MySQL:
1. Usar herramientas como `pgloader`
2. Modificar consultas SQL específicas de PostgreSQL
3. Actualizar `config/database.php` para MySQL

## Deploy en VPS

### Instalación en Ubuntu/Debian:
```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar NGINX, PHP y PostgreSQL
sudo apt install nginx php8.1-fpm php8.1-pgsql php8.1-gd php8.1-mbstring postgresql

# Configurar NGINX
sudo nano /etc/nginx/sites-available/tu-sitio

# Configurar PostgreSQL
sudo -u postgres createdb EncuentraTuHuella
sudo -u postgres createuser --interactive

# Subir archivos
rsync -avz --exclude='.git' ./ user@tu-servidor:/var/www/tu-sitio/

# Importar base de datos
psql -h localhost -U tu-usuario -d EncuentraTuHuella < database_export/full_backup.sql
```

## Configuración Post-Deploy

### 1. Permisos de archivos
```bash
# En el servidor
chmod 755 -R /ruta/a/tu/proyecto
chmod 777 -R /ruta/a/tu/proyecto/uploads
chmod 777 -R /ruta/a/tu/proyecto/logs
```

### 2. Configurar dominio personalizado
- En Railway: Settings → Domains → Add Custom Domain
- Actualizar DNS de tu dominio para apuntar a Railway

### 3. SSL/HTTPS
Railway proporciona SSL automáticamente para dominios personalizados.

## Solución de Problemas

### Error de conexión a base de datos:
- Verificar variables de entorno
- Comprobar que PostgreSQL esté corriendo
- Revisar logs en `/logs/error.log`

### Imágenes no se suben:
- Verificar permisos del directorio `uploads/`
- Comprobar límites de PHP (`upload_max_filesize`)

### Errores 500:
- Revisar logs del servidor
- Verificar sintaxis PHP
- Comprobar permisos de archivos

## URLs Importantes
- **Producción**: https://tu-proyecto.railway.app
- **Admin Railway**: https://railway.app/dashboard
- **Base de datos**: Se conecta automáticamente via DATABASE_URL

## Contacto
Si tienes problemas con el deploy, revisa la documentación de Railway o contacta soporte.
