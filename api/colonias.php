<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$alcaldia_id = (int)($_GET['alcaldia_id'] ?? 0);

if ($alcaldia_id <= 0) {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, nombre FROM colonias WHERE alcaldia_id = ? ORDER BY nombre");
    $stmt->execute([$alcaldia_id]);
    $colonias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($colonias);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
