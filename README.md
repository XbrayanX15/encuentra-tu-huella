# 🐕 Encuentra Tu Huella

**Plataforma web para encontrar mascotas perdidas en México**

Una aplicación web completa que conecta a dueños de mascotas perdidas con personas que las han encontrado, facilitando reuniones exitosas.

## 🌟 Características Principales

### Para Dueños de Mascotas
- ✅ **Registro de mascotas** con información detallada y fotos
- ✅ **Reportes de pérdida** con ubicación y detalles específicos
- ✅ **Panel personal** para gestionar mascotas y reportes
- ✅ **Sistema de notificaciones** por email y WhatsApp
- ✅ **Búsqueda avanzada** con múltiples filtros

### Para la Comunidad
- ✅ **Reportes de avistamiento** para mascotas encontradas
- ✅ **Búsqueda por mapa** con geolocalización
- ✅ **Galería de fotos** para identificación visual
- ✅ **Sistema de contacto directo** entre usuarios
- ✅ **Estadísticas en tiempo real** de la plataforma

## 🛠️ Tecnologías Utilizadas

- **PHP 8.0+** - Lenguaje principal del servidor
- **PostgreSQL 13+** - Base de datos principal
- **HTML5, CSS3, Bootstrap 5** - Frontend responsivo
- **JavaScript ES6+** - Interactividad del cliente
- **Google Maps API** - Mapas y geolocalización
- **Apache/Nginx** - Servidor web

## Contenido del sistema.tar.gz

```
Base/
├── config/
│   ├── database.php         # Configuración de conexión a PostgreSQL
│   └── config.php          # Configuraciones generales
├── css/
│   ├── bootstrap.min.css   # Framework CSS Bootstrap
│   └── styles.css          # Estilos personalizados
├── js/
│   ├── bootstrap.min.js    # Framework JavaScript Bootstrap
│   ├── maps.js            # Funciones de Google Maps
│   └── app.js             # JavaScript personalizado
├── images/
│   ├── uploads/           # Imágenes subidas por usuarios
│   └── assets/            # Imágenes del sistema
├── includes/
│   ├── header.php         # Header común
│   ├── footer.php         # Footer común
│   ├── navbar.php         # Barra de navegación
│   └── functions.php      # Funciones auxiliares
├── pages/
│   ├── index.php          # Página principal
│   ├── login.php          # Inicio de sesión
│   ├── register.php       # Registro de usuarios
│   ├── dashboard.php      # Panel de usuario
│   ├── mis_mascotas.php   # CRUD de mascotas
│   ├── reportar_perdida.php # Reportar mascota perdida
│   ├── reportar_avistamiento.php # Reportar avistamiento
│   ├── buscar.php         # Búsqueda de mascotas
│   └── mapa.php           # Vista de mapa
├── api/
│   ├── auth.php           # API de autenticación
│   ├── mascotas.php       # API de mascotas
│   ├── reportes.php       # API de reportes
│   └── upload.php         # API de subida de archivos
├── sql/
│   ├── basededatos.sql    # Script de creación de BD
│   └── datos.sql          # Datos iniciales
└── README.md              # Este archivo
```

## Especificaciones del Software

### Requisitos de Software
- **Servidor Web**: Apache 2.4+ o Nginx
- **PHP**: Versión 8.0 o superior
- **Base de Datos**: PostgreSQL 12+ 
- **Extensiones PHP requeridas**:
  - php-pgsql
  - php-gd
  - php-curl
  - php-mbstring
  - php-json

### Requisitos de Hardware
- **Mínimo**: 2GB RAM, 1GB espacio en disco
- **Recomendado**: 4GB RAM, 5GB espacio en disco
- **Conexión a Internet** para Google Maps API

## Instalación y Configuración

### 1. Preparar el entorno de desarrollo

#### Opción A: XAMPP (Recomendado para principiantes)
```bash
# Descargar XAMPP desde https://www.apachefriends.org/
# Instalar y iniciar Apache
```

#### Opción B: Instalación manual en Windows
```powershell
# Instalar PHP
winget install PHP.PHP

# Instalar PostgreSQL
winget install PostgreSQL.PostgreSQL

# Instalar Composer (gestor de dependencias PHP)
winget install Composer.Composer
```

### 2. Configurar PostgreSQL

```sql
-- Crear usuario para la aplicación
CREATE USER petfinder_user WITH PASSWORD 'petfinder123';

-- Crear base de datos
CREATE DATABASE petfinder_db OWNER petfinder_user;

-- Otorgar permisos
GRANT ALL PRIVILEGES ON DATABASE petfinder_db TO petfinder_user;
```

### 3. Configurar la aplicación

1. **Extraer el archivo sistema.tar.gz** en el directorio web del servidor
2. **Configurar la base de datos**:
   ```bash
   # Navegar al directorio del proyecto
   cd /ruta/del/proyecto
   
   # Ejecutar scripts SQL
   psql -U petfinder_user -d petfinder_db -f sql/basededatos.sql
   psql -U petfinder_user -d petfinder_db -f sql/datos.sql
   ```

3. **Configurar conexión a BD** en `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'petfinder_db');
   define('DB_USER', 'petfinder_user');
   define('DB_PASS', 'petfinder123');
   ```

4. **Configurar Google Maps API**:
   - Obtener API Key de Google Maps Platform
   - Editar `config/config.php` y agregar la clave

### 4. Ejecutar el sistema

#### Desarrollo local
```bash
# Si tienes PHP instalado globalmente
php -S localhost:8000

# O usar XAMPP
# Colocar archivos en htdocs/ y acceder a http://localhost/Base/
```

#### Servidor de producción
- Configurar Virtual Host en Apache/Nginx
- Asegurar permisos de escritura en `images/uploads/`
- Configurar HTTPS para producción

## Comandos de configuración

### Crear base de datos desde cero
```bash
# Conectar a PostgreSQL como superusuario
psql -U postgres

# Ejecutar comandos de creación
\i sql/basededatos.sql
\i sql/datos.sql
```

### Resetear datos de prueba
```bash
# Limpiar y repoblar datos
psql -U petfinder_user -d petfinder_db -c "TRUNCATE usuarios CASCADE;"
psql -U petfinder_user -d petfinder_db -f sql/datos.sql
```

### Backup de la base de datos
```bash
pg_dump -U petfinder_user petfinder_db > backup.sql
```

## Funcionalidades Implementadas

### ✅ Sistema de Usuarios
- Registro y autenticación
- Perfil de usuario
- Gestión de sesiones

### ✅ Gestión de Mascotas
- CRUD completo de mascotas registradas
- Subida de múltiples fotos
- Validación de datos

### ✅ Reportes de Pérdida
- Reportar mascotas perdidas
- Ubicación con Google Maps
- Seguimiento de estado

### ✅ Reportes de Avistamiento
- Reportar mascotas encontradas
- Usuarios anónimos pueden reportar
- Información de contacto

### ✅ Sistema de Búsqueda
- Filtros por fecha, raza, tamaño, ubicación
- Vista en lista y mapa
- Búsqueda para usuarios registrados y anónimos

### ✅ Integración con Mapas
- Google Maps para ubicaciones
- Marcadores interactivos
- Geolocalización

## Datos de Prueba

La base de datos incluye:
- **16 Alcaldías** de la Ciudad de México
- **200+ Colonias** principales de CDMX
- **50+ Razas** de perros comunes
- **Usuarios de prueba** con mascotas registradas
- **Reportes de ejemplo** para testing

### Usuarios de prueba:
- **Email**: admin@petfinder.com | **Password**: admin123
- **Email**: usuario1@test.com | **Password**: user123
- **Email**: usuario2@test.com | **Password**: user123

## Troubleshooting

### Error de conexión a PostgreSQL
```bash
# Verificar que PostgreSQL esté corriendo
pg_ctl status

# Reiniciar servicio
pg_ctl restart
```

### Error de permisos en uploads
```bash
# En Linux/Mac
chmod 755 images/uploads/

# En Windows, dar permisos de escritura a la carpeta
```

### Error con Google Maps
- Verificar que la API Key esté activa
- Confirmar que JavaScript API esté habilitada
- Revisar restricciones de dominio

## Contacto de Desarrollo

Para soporte técnico o consultas sobre el código:
- Revisar logs en: `error_log` del servidor
- Verificar configuración en `config/`
- Consultar documentación de PostgreSQL y PHP

---
**Desarrollado para el curso de Base de Datos - CDMX 2025**

## 📦 Entrega Final

### Contenido del Paquete `sistema.zip`

El archivo `sistema.zip` contiene el sistema completo y listo para usar:

- **Código fuente completo** (PHP, HTML, CSS, JavaScript)
- **Scripts SQL** con estructura y datos de prueba
- **Archivos de configuración** (.htaccess, .env.example)
- **Scripts de instalación automatizada** (Windows y Linux/macOS)
- **Script de validación** del sistema
- **Documentación completa** (este README)

### Validación del Sistema

Antes de usar, ejecute la validación:
```bash
# Windows
validar_sistema.bat

# Linux/macOS  
chmod +x validar_sistema.sh && ./validar_sistema.sh
```

### Especificaciones Técnicas Finales

- **Total de archivos PHP**: 23
- **APIs implementadas**: 5
- **Páginas funcionales**: 13
- **Base de datos**: PostgreSQL con 15 tablas
- **Datos de prueba**: 16 alcaldías, 500+ colonias CDMX
- **Usuarios de prueba**: admin, test1, test2
- **Reportes de prueba**: 10 mascotas perdidas/encontradas

### Estado de Desarrollo: ✅ COMPLETO

Todas las funcionalidades requeridas han sido implementadas:
- ✅ Sistema de autenticación seguro
- ✅ Gestión completa de mascotas
- ✅ Reportes de pérdida y avistamiento
- ✅ Búsqueda avanzada con filtros
- ✅ Integración con Google Maps
- ✅ Subida y gestión de imágenes
- ✅ Panel de administración
- ✅ Base de datos poblada con datos reales de CDMX
- ✅ Scripts de instalación automatizada
- ✅ Documentación completa

---
