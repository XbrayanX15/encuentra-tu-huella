<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Configuración de subida
$upload_dir = '../uploads/';
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_file_size = 5 * 1024 * 1024; // 5MB

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo válido']);
    exit();
}

$file = $_FILES['file'];
$file_type = $file['type'];
$file_size = $file['size'];

// Validaciones
if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo JPG, PNG y GIF.']);
    exit();
}

if ($file_size > $max_file_size) {
    echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 5MB.']);
    exit();
}

// Determinar directorio según el tipo
$tipo = $_POST['tipo'] ?? 'general';
switch ($tipo) {
    case 'mascota':
        $upload_dir .= 'mascotas/';
        break;
    case 'perdido':
        $upload_dir .= 'perdidos/';
        break;
    case 'encontrado':
        $upload_dir .= 'encontrados/';
        break;
    default:
        $upload_dir .= 'general/';
        break;
}

// Crear directorio si no existe
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generar nombre único
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$new_filename = $tipo . '_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
$upload_path = $upload_dir . $new_filename;

// Mover archivo
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Ruta relativa para la base de datos
    $relative_path = str_replace('../', '', $upload_path);
    
    echo json_encode([
        'success' => true,
        'filename' => $new_filename,
        'path' => $relative_path,
        'url' => $relative_path
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al subir el archivo']);
}
?>
