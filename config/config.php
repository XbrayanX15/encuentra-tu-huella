<?php
// =====================================================
// Encuentra Tu Huella - Configuraciones Generales
// =====================================================

// Configuración del sitio
define('SITE_NAME', 'Encuentra Tu Huella');
define('SITE_DESCRIPTION', 'Sistema para encontrar mascotas perdidas en la Ciudad de México');
define('SITE_URL', $_ENV['SITE_URL'] ?? getenv('SITE_URL') ?: 'http://localhost:8000'); // Cambiar por la URL real
define('SITE_EMAIL', 'contacto@encuentratuhuella.com');

// Configuración de Google Maps
define('GOOGLE_MAPS_API_KEY', $_ENV['GOOGLE_MAPS_API_KEY'] ?? getenv('GOOGLE_MAPS_API_KEY') ?: 'TU_API_KEY_AQUI'); // Reemplazar con tu API Key real

// Configuración de archivos
define('UPLOAD_PATH', __DIR__ . '/../images/uploads/');
define('UPLOAD_URL', '/images/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB máximo
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Configuración de sesiones
define('SESSION_TIMEOUT', 7200); // 2 horas en segundos

// Configuración de seguridad
define('PASSWORD_MIN_LENGTH', 6);
define('SALT', 'petfinder_cdmx_2025'); // Cambiar por un salt único

// Configuración de paginación
define('RECORDS_PER_PAGE', 10);
define('MAX_SEARCH_RESULTS', 50);

// Configuración de contacto
define('CONTACT_PHONE', '55-1234-5678');
define('CONTACT_EMAIL', 'soporte@petfinder.com');

// Configuración de la aplicación
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', true); // Cambiar a false en producción

// Configuración de tiempo
define('DEFAULT_TIMEZONE', 'America/Mexico_City');

// Configuración de mensajes
define('SUCCESS_MESSAGE_DURATION', 5000); // milisegundos
define('ERROR_MESSAGE_DURATION', 8000); // milisegundos

// Estados permitidos para reportes
define('ESTADOS_PERDIDOS', ['perdido', 'encontrado', 'cancelado']);
define('ESTADOS_ENCONTRADOS', ['disponible', 'entregado', 'cancelado']);

// Configuración de búsqueda
define('SEARCH_RADIUS_KM', 10); // Radio de búsqueda por defecto en kilómetros
define('MAX_PHOTOS_PER_REPORT', 5);

// Configuración de notificaciones
define('ENABLE_EMAIL_NOTIFICATIONS', false); // Activar cuando se configure email
define('ENABLE_SMS_NOTIFICATIONS', false); // Para futuras implementaciones

// Configuración de logs
define('LOG_ERRORS', true);
define('LOG_PATH', __DIR__ . '/../logs/');

// Crear directorio de logs si no existe
if (!file_exists(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// Configurar zona horaria
date_default_timezone_set(DEFAULT_TIMEZONE);

// Configuración de errores para desarrollo
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configuración de CORS para desarrollo - SOLO para archivos web
if (APP_DEBUG && isset($_SERVER['HTTP_HOST'])) {
    if (!headers_sent()) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }
}

?>
