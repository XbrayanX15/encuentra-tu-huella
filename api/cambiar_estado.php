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
$input = json_decode(file_get_contents('php://input'), true);

$tipo = $input['tipo'] ?? '';
$id = (int)($input['id'] ?? 0);
$estado = $input['estado'] ?? '';

if (empty($tipo) || $id <= 0 || empty($estado)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

try {
    if ($tipo === 'perdido') {
        $stmt = $pdo->prepare("UPDATE perros_perdidos SET estado = ? WHERE id = ? AND usuario_id = ?");
    } elseif ($tipo === 'encontrado') {
        $stmt = $pdo->prepare("UPDATE perros_encontrados SET estado = ? WHERE id = ? AND usuario_id = ?");
    } else {
        echo json_encode(['success' => false, 'message' => 'Tipo inválido']);
        exit();
    }
    
    $stmt->execute([$estado, $id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
}
?>
