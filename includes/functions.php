<?php
// =====================================================
// Pet Finder CDMX - Funciones Auxiliares
// =====================================================

// Incluir configuraciones
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

// =====================================================
// FUNCIONES DE AUTENTICACIÓN
// =====================================================

// Iniciar sesión si no está iniciada
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Verificar si el usuario está logueado
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Obtener información del usuario actual
function getCurrentUser() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $query = "SELECT * FROM usuarios WHERE id = ? AND activo = true";
    return fetchOne($query, [$_SESSION['user_id']]);
}

// Cerrar sesión
function logout() {
    startSession();
    session_unset();
    session_destroy();
    header('Location: /pages/index.php');
    exit;
}

// Redirigir si no está logueado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /pages/login.php');
        exit;
    }
}

// Verificar timeout de sesión
function checkSessionTimeout() {
    startSession();
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            logout();
        }
    }
    $_SESSION['last_activity'] = time();
}

// Función para registrar actividad de usuarios
function logActivity($userId, $action, $description = '') {
    if (defined('LOG_ERRORS') && LOG_ERRORS) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] Usuario: $userId | Acción: $action | $description" . PHP_EOL;
        $logFile = __DIR__ . '/../logs/activity.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// =====================================================
// FUNCIONES DE VALIDACIÓN
// =====================================================

// Validar email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validar teléfono mexicano
function validatePhone($phone) {
    $pattern = '/^(\+52\s?)?(\d{2}[-\s]?)?\d{4}[-\s]?\d{4}$/';
    return preg_match($pattern, $phone);
}

// Limpiar entrada de datos
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validar coordenadas de CDMX
function validateCoordinatesCDMX($lat, $lng) {
    // Límites aproximados de CDMX
    $minLat = 19.2; $maxLat = 19.6;
    $minLng = -99.4; $maxLng = -98.9;
    
    return ($lat >= $minLat && $lat <= $maxLat && $lng >= $minLng && $lng <= $maxLng);
}

// =====================================================
// FUNCIONES DE ARCHIVOS
// =====================================================

// Crear directorio si no existe
function createDirectory($path) {
    if (!file_exists($path)) {
        return mkdir($path, 0755, true);
    }
    return true;
}

// Subir archivo de imagen
function uploadImage($file, $subfolder = '') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Verificar tamaño
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['error' => 'El archivo es demasiado grande. Máximo ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
    }
    
    // Verificar extensión
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['error' => 'Tipo de archivo no permitido. Solo: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }
    
    // Crear directorio de destino
    $uploadDir = UPLOAD_PATH . $subfolder;
    if (!createDirectory($uploadDir)) {
        return ['error' => 'No se pudo crear el directorio de destino'];
    }
    
    // Generar nombre único
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . '/' . $filename;
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $filepath,
            'url' => UPLOAD_URL . $subfolder . '/' . $filename
        ];
    }
    
    return ['error' => 'Error al subir el archivo'];
}

// Eliminar archivo
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

// Función auxiliar para upload de archivos mejorada
function uploadImageFile($file, $destination_folder, $max_size = 5242880, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error en la carga del archivo'];
    }
    
    // Verificar tamaño
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'El archivo es demasiado grande. Máximo 5MB'];
    }
    
    // Verificar tipo de archivo
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido. Solo JPG, PNG, GIF'];
    }
    
    // Crear directorio si no existe
    if (!file_exists($destination_folder)) {
        mkdir($destination_folder, 0755, true);
    }
    
    // Generar nombre único
    $filename = uniqid() . '_' . time() . '.' . $file_ext;
    $full_path = $destination_folder . '/' . $filename;
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $full_path)) {
        return [
            'success' => true, 
            'filename' => $filename, 
            'path' => $full_path,
            'relative_path' => str_replace(__DIR__ . '/../', '', $full_path)
        ];
    } else {
        return ['success' => false, 'error' => 'Error al guardar el archivo'];
    }
}

function deleteImage($file_path) {
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return false;
}

function resizeImage($source, $destination, $max_width = 800, $max_height = 600, $quality = 85) {
    $image_info = getimagesize($source);
    if (!$image_info) return false;
    
    $original_width = $image_info[0];
    $original_height = $image_info[1];
    $mime_type = $image_info['mime'];
    
    // Calcular nuevas dimensiones manteniendo proporción
    $ratio = min($max_width / $original_width, $max_height / $original_height);
    $new_width = round($original_width * $ratio);
    $new_height = round($original_height * $ratio);
    
    // Crear imagen desde archivo fuente
    switch ($mime_type) {
        case 'image/jpeg':
            $source_image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $source_image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Crear nueva imagen
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Preservar transparencia para PNG y GIF
    if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefill($new_image, 0, 0, $transparent);
    }
    
    // Redimensionar
    imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, 
                      $new_width, $new_height, $original_width, $original_height);
    
    // Guardar imagen
    switch ($mime_type) {
        case 'image/jpeg':
            $result = imagejpeg($new_image, $destination, $quality);
            break;
        case 'image/png':
            $result = imagepng($new_image, $destination, 9);
            break;
        case 'image/gif':
            $result = imagegif($new_image, $destination);
            break;
        default:
            $result = false;
    }
    
    // Limpiar memoria
    imagedestroy($source_image);
    imagedestroy($new_image);
    
    return $result;
}

// =====================================================
// FUNCIONES DE UTILIDAD
// =====================================================

// Formatear fecha en español
function formatDate($date, $format = 'd/m/Y H:i') {
    $months = [
        'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
        'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
        'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
        'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
    ];
    
    $days = [
        'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ];
    
    $formatted = date($format, strtotime($date));
    $formatted = str_replace(array_keys($months), array_values($months), $formatted);
    $formatted = str_replace(array_keys($days), array_values($days), $formatted);
    
    return $formatted;
}

// Calcular tiempo transcurrido
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'hace un momento';
    if ($time < 3600) return 'hace ' . floor($time/60) . ' minutos';
    if ($time < 86400) return 'hace ' . floor($time/3600) . ' horas';
    if ($time < 2592000) return 'hace ' . floor($time/86400) . ' días';
    if ($time < 31104000) return 'hace ' . floor($time/2592000) . ' meses';
    
    return 'hace ' . floor($time/31104000) . ' años';
}

// Generar mensaje de alerta Bootstrap
function showAlert($message, $type = 'info') {
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

// Calcular distancia entre dos puntos (Haversine)
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371; // Radio de la Tierra en km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earth_radius * $c;
}

// =====================================================
// FUNCIONES DE BASE DE DATOS ESPECÍFICAS
// =====================================================

// Obtener catálogos
function getRazas() {
    return fetchAll("SELECT * FROM razas ORDER BY nombre");
}

function getTamaños() {
    return fetchAll("SELECT * FROM tamaños ORDER BY id");
}

function getSexos() {
    return fetchAll("SELECT * FROM sexos ORDER BY id");
}

function getTiposPelo() {
    return fetchAll("SELECT * FROM tipos_pelo ORDER BY nombre");
}

function getEstadosSalud() {
    return fetchAll("SELECT * FROM estados_salud ORDER BY nombre");
}

function getEstadosEmocionales() {
    return fetchAll("SELECT * FROM estados_emocionales ORDER BY nombre");
}

function getEntidadesFederativas() {
    return fetchAll("SELECT * FROM entidades_federativas ORDER BY nombre");
}

function getMunicipios($entidad_id = 1) {
    // Para CDMX, devolvemos todos los municipios (delegaciones)
    return fetchAll("SELECT * FROM municipios ORDER BY nombre");
}

function getColonias($municipio_id = null) {
    if ($municipio_id) {
        return fetchAll("SELECT * FROM colonias WHERE municipio_id = ? ORDER BY nombre", [$municipio_id]);
    }
    return fetchAll("SELECT c.*, m.nombre as municipio FROM colonias c JOIN municipios m ON c.municipio_id = m.id ORDER BY m.nombre, c.nombre");
}

// Obtener mascotas del usuario
function getUserPets($user_id) {
    $query = "SELECT m.*, r.nombre as raza, t.nombre as tamaño, s.nombre as sexo, tp.nombre as tipo_pelo
              FROM mascotas_registradas m
              LEFT JOIN razas r ON m.raza_id = r.id
              LEFT JOIN tamaños t ON m.tamaño_id = t.id
              LEFT JOIN sexos s ON m.sexo_id = s.id
              LEFT JOIN tipos_pelo tp ON m.tipo_pelo_id = tp.id
              WHERE m.usuario_id = ? AND m.activo = true
              ORDER BY m.fecha_registro DESC";
    
    return fetchAll($query, [$user_id]);
}

// Obtener foto principal de una entidad
function getMainPhoto($entity_type, $entity_id) {
    $column = '';
    switch($entity_type) {
        case 'mascota': $column = 'mascota_registrada_id'; break;
        case 'perdido': $column = 'perro_perdido_id'; break;
        case 'encontrado': $column = 'perro_encontrado_id'; break;
        default: return null;
    }
    
    $query = "SELECT * FROM fotos WHERE $column = ? AND principal = true LIMIT 1";
    $result = fetchOne($query, [$entity_id]);
    
    if (!$result) {
        $query = "SELECT * FROM fotos WHERE $column = ? ORDER BY fecha_subida LIMIT 1";
        $result = fetchOne($query, [$entity_id]);
    }
    
    return $result;
}

// =====================================================
// FUNCIONES DE SEGURIDAD
// =====================================================

// Hash de contraseña
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verificar contraseña
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generar token CSRF
function generateCSRFToken() {
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verificar token CSRF
function verifyCSRFToken($token) {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Escapar salida HTML
function escape($string) {
    if ($string === null) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Función para logging de errores
function logError($message, $context = []) {
    $log_file = __DIR__ . '/../logs/error.log';
    $log_dir = dirname($log_file);
    
    // Crear directorio de logs si no existe
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $context_str = empty($context) ? '' : ' | Context: ' . json_encode($context);
    $log_message = "[{$timestamp}] ERROR: {$message}{$context_str}" . PHP_EOL;
    
    error_log($log_message, 3, $log_file);
}

// Función para configurar headers de seguridad
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// =====================================================
// FUNCIONES DE UTILIDAD ADICIONALES
// =====================================================

// =====================================================
// FUNCIONES DE UTILIDAD PARA IMÁGENES
// =====================================================

// Obtener la ruta correcta de imagen dependiendo del contexto
function getImagePath($ruta_archivo, $contexto = 'pages') {
    if (!$ruta_archivo) {
        return $contexto === 'pages' ? '../img/no-image.svg' : 'img/no-image.svg';
    }
    
    // Si estamos en una página dentro de /pages/, necesitamos ../ 
    // Si estamos en la raíz, no necesitamos ../
    if ($contexto === 'pages') {
        return '../' . $ruta_archivo;
    } else {
        return $ruta_archivo;
    }
}

// Obtener la primera foto de un reporte de pérdida
function getPrimeraFotoPerdido($perdido_id, $contexto = 'pages') {
    $foto = fetchOne("
        SELECT ruta_archivo 
        FROM fotos 
        WHERE perro_perdido_id = ? 
        ORDER BY fecha_subida ASC 
        LIMIT 1
    ", [$perdido_id]);
    
    return getImagePath($foto ? $foto['ruta_archivo'] : null, $contexto);
}

// Obtener la primera foto de un reporte de avistamiento
function getPrimeraFotoEncontrado($encontrado_id, $contexto = 'pages') {
    $foto = fetchOne("
        SELECT ruta_archivo 
        FROM fotos 
        WHERE perro_encontrado_id = ? 
        ORDER BY fecha_subida ASC 
        LIMIT 1
    ", [$encontrado_id]);
    
    return getImagePath($foto ? $foto['ruta_archivo'] : null, $contexto);
}

// =====================================================
// FUNCIONES DE AUTENTICACIÓN
// =====================================================
