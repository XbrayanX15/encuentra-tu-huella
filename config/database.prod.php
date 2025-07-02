<?php
// =====================================================
// Encuentra Tu Huella - Configuración de Base de Datos PRODUCCIÓN
// =====================================================

// Configuración de conexión usando variables de entorno
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'EncuentraTuHuella');
define('DB_USER', $_ENV['DB_USER'] ?? 'postgres');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_PORT', $_ENV['DB_PORT'] ?? '5432');

// Configuración de caracteres
define('DB_CHARSET', 'utf8');

// Clase para manejar la conexión a la base de datos
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            // Para Railway y otros servicios que usan DATABASE_URL
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
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            die("Error de conexión a la base de datos");
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
?>
