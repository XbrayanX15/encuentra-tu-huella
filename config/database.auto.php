<?php
// =====================================================
// Encuentra Tu Huella - Configuración Automática
// =====================================================

// Detectar entorno automáticamente
function isProduction() {
    return isset($_ENV['RAILWAY_ENVIRONMENT']) || 
           isset($_ENV['RENDER']) || 
           isset($_ENV['VERCEL']) ||
           (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') === false);
}

// Cargar configuración según el entorno
if (isProduction()) {
    // Configuración para producción (usar variables de entorno)
    define('DB_HOST', $_ENV['DB_HOST'] ?? $_ENV['PGHOST'] ?? 'localhost');
    define('DB_NAME', $_ENV['DB_NAME'] ?? $_ENV['PGDATABASE'] ?? 'EncuentraTuHuella');
    define('DB_USER', $_ENV['DB_USER'] ?? $_ENV['PGUSER'] ?? 'postgres');
    define('DB_PASS', $_ENV['DB_PASS'] ?? $_ENV['PGPASSWORD'] ?? '');
    define('DB_PORT', $_ENV['DB_PORT'] ?? $_ENV['PGPORT'] ?? '5432');
    define('APP_ENV', 'production');
} else {
    // Configuración para desarrollo local
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'EncuentraTuHuella');
    define('DB_USER', 'postgres');
    define('DB_PASS', 'brayan2003');  // Solo para desarrollo local
    define('DB_PORT', '1573');
    define('APP_ENV', 'development');
}

// Configuración de caracteres
define('DB_CHARSET', 'utf8');

// Configuración de debugging
define('APP_DEBUG', APP_ENV === 'development');

// Clase para manejar la conexión a la base de datos
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            // Para servicios que usan DATABASE_URL completa (Railway, Render, Heroku)
            if (isset($_ENV['DATABASE_URL'])) {
                $this->connection = new PDO($_ENV['DATABASE_URL']);
            } else {
                $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
                $this->connection = new PDO($dsn, DB_USER, DB_PASS);
            }
            
            // Configurar PDO para mostrar errores
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Configurar encoding
            $this->connection->exec("SET NAMES 'UTF8'");
            
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                die("Error de conexión a la base de datos: " . $e->getMessage());
            } else {
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                die("Error de conexión a la base de datos. Por favor, inténtelo más tarde.");
            }
        }
    }
    
    // Patrón Singleton para una sola instancia de conexión
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevenir clonación de la instancia
    private function __clone() {}
    
    // Prevenir deserialización
    private function __wakeup() {}
}

// Función helper para obtener la conexión
function getDB() {
    return Database::getInstance()->getConnection();
}

// Función para detectar si estamos en desarrollo
function isDevelopment() {
    return APP_ENV === 'development';
}

// Función para obtener URL base
function getBaseUrl() {
    if (isProduction()) {
        return 'https://' . $_SERVER['HTTP_HOST'];
    } else {
        return 'http://localhost';
    }
}
?>
