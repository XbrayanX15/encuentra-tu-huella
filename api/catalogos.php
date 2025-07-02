<?php
// =====================================================
// API para obtener municipios y colonias dinámicamente
// =====================================================

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
setSecurityHeaders();

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'municipios':
            $entidad_id = $_GET['entidad_id'] ?? 1; // Por defecto CDMX
            $municipios = getMunicipios($entidad_id);
            echo json_encode(['success' => true, 'data' => $municipios]);
            break;
            
        case 'colonias':
            $municipio_id = $_GET['municipio_id'] ?? '';
            if (empty($municipio_id)) {
                echo json_encode(['success' => false, 'error' => 'Municipio requerido']);
                return;
            }
            $colonias = getColonias($municipio_id);
            echo json_encode(['success' => true, 'data' => $colonias]);
            break;
            
        case 'razas':
            $razas = getRazas();
            echo json_encode(['success' => true, 'data' => $razas]);
            break;
            
        case 'tamaños':
            $tamaños = getTamaños();
            echo json_encode(['success' => true, 'data' => $tamaños]);
            break;
            
        case 'sexos':
            $sexos = getSexos();
            echo json_encode(['success' => true, 'data' => $sexos]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    logError("Error en API de catálogos: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>
