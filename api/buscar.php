<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$tipo = $_GET['tipo'] ?? 'todos'; // todos, perdidos, encontrados
$raza = (int)($_GET['raza'] ?? 0);
$sexo = $_GET['sexo'] ?? '';
$tamano = (int)($_GET['tamano'] ?? 0);
$alcaldia = (int)($_GET['alcaldia'] ?? 0);
$colonia = (int)($_GET['colonia'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $resultados = [];
    
    if ($tipo === 'todos' || $tipo === 'perdidos') {
        // Búsqueda de perros perdidos
        $where_conditions = ["pp.estado = 'Activo'"];
        $params = [];
        
        if ($raza > 0) {
            $where_conditions[] = "pp.raza_id = ?";
            $params[] = $raza;
        }
        
        if (!empty($sexo)) {
            $where_conditions[] = "pp.sexo = ?";
            $params[] = $sexo;
        }
        
        if ($tamano > 0) {
            $where_conditions[] = "pp.tamano_id = ?";
            $params[] = $tamano;
        }
        
        if ($alcaldia > 0) {
            $where_conditions[] = "pp.alcaldia_id = ?";
            $params[] = $alcaldia;
        }
        
        if ($colonia > 0) {
            $where_conditions[] = "pp.colonia_id = ?";
            $params[] = $colonia;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $stmt = $pdo->prepare("
            SELECT pp.*, r.nombre as raza_nombre, t.descripcion as tamano_desc,
                   a.nombre as alcaldia_nombre, c.nombre as colonia_nombre,
                   u.nombre as usuario_nombre, u.telefono as usuario_telefono,
                   COALESCE(array_agg(f.ruta_archivo ORDER BY f.fecha_subida ASC) FILTER (WHERE f.ruta_archivo IS NOT NULL), ARRAY[]::text[]) as fotos,
                   'perdido' as tipo_resultado
            FROM perros_perdidos pp
            LEFT JOIN razas r ON pp.raza_id = r.id
            LEFT JOIN tamanos t ON pp.tamano_id = t.id
            LEFT JOIN alcaldias a ON pp.alcaldia_id = a.id
            LEFT JOIN colonias c ON pp.colonia_id = c.id
            LEFT JOIN usuarios u ON pp.usuario_id = u.id
            LEFT JOIN fotos f ON pp.id = f.perdido_id
            WHERE {$where_clause}
            GROUP BY pp.id, r.nombre, t.descripcion, a.nombre, c.nombre, u.nombre, u.telefono
            ORDER BY pp.fecha_reporte DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $perdidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultados = array_merge($resultados, $perdidos);
    }
    
    if ($tipo === 'todos' || $tipo === 'encontrados') {
        // Búsqueda de perros encontrados
        $where_conditions = ["pe.estado = 'Activo'"];
        $params = [];
        
        if ($raza > 0) {
            $where_conditions[] = "pe.raza_id = ?";
            $params[] = $raza;
        }
        
        if (!empty($sexo)) {
            $where_conditions[] = "pe.sexo = ?";
            $params[] = $sexo;
        }
        
        if ($tamano > 0) {
            $where_conditions[] = "pe.tamano_id = ?";
            $params[] = $tamano;
        }
        
        if ($alcaldia > 0) {
            $where_conditions[] = "pe.alcaldia_id = ?";
            $params[] = $alcaldia;
        }
        
        if ($colonia > 0) {
            $where_conditions[] = "pe.colonia_id = ?";
            $params[] = $colonia;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $stmt = $pdo->prepare("
            SELECT pe.*, r.nombre as raza_nombre, t.descripcion as tamano_desc,
                   a.nombre as alcaldia_nombre, c.nombre as colonia_nombre,
                   u.nombre as usuario_nombre, u.telefono as usuario_telefono,
                   COALESCE(array_agg(f.ruta_archivo ORDER BY f.fecha_subida ASC) FILTER (WHERE f.ruta_archivo IS NOT NULL), ARRAY[]::text[]) as fotos,
                   'encontrado' as tipo_resultado
            FROM perros_encontrados pe
            LEFT JOIN razas r ON pe.raza_id = r.id
            LEFT JOIN tamanos t ON pe.tamano_id = t.id
            LEFT JOIN alcaldias a ON pe.alcaldia_id = a.id
            LEFT JOIN colonias c ON pe.colonia_id = c.id
            LEFT JOIN usuarios u ON pe.usuario_id = u.id
            LEFT JOIN fotos f ON pe.id = f.encontrado_id
            WHERE {$where_clause}
            GROUP BY pe.id, r.nombre, t.descripcion, a.nombre, c.nombre, u.nombre, u.telefono
            ORDER BY pe.fecha_reporte DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $encontrados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultados = array_merge($resultados, $encontrados);
    }
    
    // Preparar datos para el mapa
    foreach ($resultados as &$resultado) {
        // Procesar array de fotos de PostgreSQL
        $fotos_raw = $resultado['fotos'];
        if (is_string($fotos_raw) && $fotos_raw !== '{}') {
            $fotos_raw = trim($fotos_raw, '{}');
            $fotos = explode(',', $fotos_raw);
            $resultado['fotos'] = array_filter($fotos);
        } else {
            $resultado['fotos'] = [];
        }
        
        // Agregar URL de la primera foto
        $resultado['foto_principal'] = !empty($resultado['fotos']) ? $resultado['fotos'][0] : 'img/no-image.jpg';
        
        // Preparar datos para el mapa (si tenemos ubicación)
        if ($resultado['alcaldia_id']) {
            // Aquí se podrían agregar coordenadas específicas si las tuviéramos
            // Por ahora usamos coordenadas aproximadas del centro de cada alcaldía
            $coords = getAlcaldiaCoordinates($resultado['alcaldia_id']);
            $resultado['lat'] = $coords['lat'];
            $resultado['lng'] = $coords['lng'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'resultados' => $resultados,
        'page' => $page,
        'total' => count($resultados)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en la búsqueda: ' . $e->getMessage()
    ]);
}

function getAlcaldiaCoordinates($alcaldia_id) {
    // Coordenadas aproximadas del centro de cada alcaldía de CDMX
    $coordinates = [
        1 => ['lat' => 19.3629, 'lng' => -99.0837], // Álvaro Obregón
        2 => ['lat' => 19.3909, 'lng' => -99.1398], // Azcapotzalco
        3 => ['lat' => 19.2969, 'lng' => -99.0896], // Benito Juárez
        4 => ['lat' => 19.4326, 'lng' => -99.1332], // Coyoacán
        5 => ['lat' => 19.4978, 'lng' => -99.1269], // Cuajimalpa
        6 => ['lat' => 19.4326, 'lng' => -99.1332], // Cuauhtémoc
        7 => ['lat' => 19.4901, 'lng' => -99.2442], // Gustavo A. Madero
        8 => ['lat' => 19.2847, 'lng' => -99.1408], // Iztacalco
        9 => ['lat' => 19.3467, 'lng' => -99.0557], // Iztapalapa
        10 => ['lat' => 19.3722, 'lng' => -99.2086], // La Magdalena Contreras
        11 => ['lat' => 19.3072, 'lng' => -99.1519], // Miguel Hidalgo
        12 => ['lat' => 19.2603, 'lng' => -99.1465], // Milpa Alta
        13 => ['lat' => 19.4901, 'lng' => -99.2442], // Tláhuac
        14 => ['lat' => 19.2925, 'lng' => -99.1856], // Tlalpan
        15 => ['lat' => 19.3467, 'lng' => -99.2086], // Venustiano Carranza
        16 => ['lat' => 19.2847, 'lng' => -99.2052], // Xochimilco
    ];
    
    return $coordinates[$alcaldia_id] ?? ['lat' => 19.4326, 'lng' => -99.1332]; // Centro de CDMX por defecto
}
?>
