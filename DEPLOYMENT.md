# Encuentra Tu Huella - Deployment en Render

## Configuración del Deployment

### 1. Crear Base de Datos PostgreSQL en Render

1. Ve a tu dashboard de Render
2. Clic en "New" → "PostgreSQL"
3. Configura la base de datos:
   - **Name**: encuentra-tu-huella-db
   - **Database**: EncuentraTuHuella
   - **User**: postgres
   - **Region**: Oregon (US West)
   - **PostgreSQL Version**: 15
   - **Plan**: Free

### 2. Configurar Variables de Entorno

Una vez creada la base de datos, ve a tu servicio web y configura estas variables de entorno:

- `DB_HOST`: [Hostname interno de la base de datos de Render]
- `DB_NAME`: EncuentraTuHuella
- `DB_USER`: [Usuario de la base de datos]
- `DB_PASS`: [Contraseña de la base de datos]
- `DB_PORT`: 5432

**Nota**: Render proporciona automáticamente estas variables cuando conectas una base de datos PostgreSQL a tu servicio web.

### 3. Ejecutar Scripts de Base de Datos

Conecta a tu base de datos PostgreSQL en Render y ejecuta los siguientes scripts en orden:

1. **Estructura de la base de datos**:
   ```bash
   psql $DATABASE_URL < sql/basededatos.sql
   ```

2. **Datos iniciales**:
   ```bash
   psql $DATABASE_URL < sql/datos.sql
   ```

### 4. Configuración del Servicio Web

El servicio web ya está configurado con:
- **Build Command**: (ninguno, se usa Dockerfile)
- **Start Command**: `php -S 0.0.0.0:10000 -t .`
- **Environment**: Docker
- **Dockerfile**: Configurado para PHP 8.1 con extensiones PostgreSQL

### 5. Verificar el Deployment

1. La URL de tu aplicación debería mostrar la página principal
2. Verifica que la redirección de `/` a `/pages/` funciona correctamente
3. Comprueba que la conexión a la base de datos es exitosa

### Estructura de Archivos Importantes

- `Dockerfile`: Configuración del contenedor con PHP y extensiones PostgreSQL
- `index.php`: Redirección a la aplicación principal en `/pages/`
- `config/database.php`: Configuración de conexión con variables de entorno
- `sql/basededatos.sql`: Estructura de la base de datos
- `sql/datos.sql`: Datos iniciales para la aplicación

### Troubleshooting

1. **Error de conexión a base de datos**: Verifica que las variables de entorno estén configuradas correctamente
2. **Página en blanco**: Revisa los logs del servicio en Render
3. **Errores de permisos**: Asegúrate de que las carpetas `uploads/` y `logs/` tengan permisos de escritura

### URLs de Ejemplo

- **Página principal**: `https://tu-app.onrender.com/`
- **Búsqueda**: `https://tu-app.onrender.com/pages/buscar.php`
- **Login**: `https://tu-app.onrender.com/pages/login.php`
