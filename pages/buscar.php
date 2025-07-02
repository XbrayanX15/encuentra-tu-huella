<?php
$page_title = 'Buscar Mascotas';
$include_maps = true;
require_once __DIR__ . '/../includes/header.php';

// Obtener parámetros de búsqueda
$search_params = [
    'q' => cleanInput($_GET['q'] ?? ''),
    'tipo' => $_GET['tipo'] ?? '', // perdido, encontrado
    'raza_id' => $_GET['raza_id'] ?? '',
    'tamaño_id' => $_GET['tamaño_id'] ?? '',
    'sexo_id' => $_GET['sexo_id'] ?? '',
    'municipio_id' => $_GET['municipio_id'] ?? '',
    'colonia_id' => $_GET['colonia_id'] ?? '',
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
    'page' => max(1, intval($_GET['page'] ?? 1))
];

// Construcción de la consulta
$where_conditions = [];
$params = [];
$joins = [];

// Búsqueda por texto
if (!empty($search_params['q'])) {
    $search_term = '%' . $search_params['q'] . '%';
    $where_conditions[] = "(nombre ILIKE ? OR descripcion ILIKE ? OR senas_particulares ILIKE ?)";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

// Filtros específicos
if (!empty($search_params['raza_id'])) {
    $where_conditions[] = "raza_id = ?";
    $params[] = $search_params['raza_id'];
}

if (!empty($search_params['tamaño_id'])) {
    $where_conditions[] = "tamaño_id = ?";
    $params[] = $search_params['tamaño_id'];
}

if (!empty($search_params['sexo_id'])) {
    $where_conditions[] = "sexo_id = ?";
    $params[] = $search_params['sexo_id'];
}

if (!empty($search_params['municipio_id'])) {
    $where_conditions[] = "municipio_id = ?";
    $params[] = $search_params['municipio_id'];
}

if (!empty($search_params['colonia_id'])) {
    $where_conditions[] = "colonia_id = ?";
    $params[] = $search_params['colonia_id'];
}

// Filtro de fechas
if (!empty($search_params['fecha_desde'])) {
    $where_conditions[] = "DATE(fecha_hora) >= ?";
    $params[] = $search_params['fecha_desde'];
}

if (!empty($search_params['fecha_hasta'])) {
    $where_conditions[] = "DATE(fecha_hora) <= ?";
    $params[] = $search_params['fecha_hasta'];
}

// Construir consultas para perdidos y encontrados
$base_where = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$queries = [];
$all_params = [];

if (empty($search_params['tipo']) || $search_params['tipo'] === 'perdido') {
    $perdidos_query = "
        SELECT 'perdido' as tipo, id, nombre, raza_id, tamaño_id, sexo_id, edad,
               senas_particulares, descripcion, fecha_hora, latitud, longitud,
               municipio_id, colonia_id, estado, fecha_registro,
               (SELECT nombre FROM razas WHERE id = raza_id) as raza,
               (SELECT nombre FROM tamaños WHERE id = tamaño_id) as tamaño,
               (SELECT nombre FROM sexos WHERE id = sexo_id) as sexo,
               (SELECT nombre FROM municipios WHERE id = municipio_id) as municipio,
               (SELECT nombre FROM colonias WHERE id = colonia_id) as colonia
        FROM perros_perdidos 
        $base_where AND estado = 'perdido'
    ";
    $queries[] = $perdidos_query;
    $all_params = array_merge($all_params, $params);
}

if (empty($search_params['tipo']) || $search_params['tipo'] === 'encontrado') {
    $encontrados_query = "
        SELECT 'encontrado' as tipo, id, nombre, raza_id, tamaño_id, sexo_id, edad,
               senas_particulares, descripcion, fecha_hora, latitud, longitud,
               municipio_id, colonia_id, estado, fecha_registro,
               (SELECT nombre FROM razas WHERE id = raza_id) as raza,
               (SELECT nombre FROM tamaños WHERE id = tamaño_id) as tamaño,
               (SELECT nombre FROM sexos WHERE id = sexo_id) as sexo,
               (SELECT nombre FROM municipios WHERE id = municipio_id) as municipio,
               (SELECT nombre FROM colonias WHERE id = colonia_id) as colonia
        FROM perros_encontrados 
        $base_where AND estado = 'disponible'
    ";
    $queries[] = $encontrados_query;
    $all_params = array_merge($all_params, $params);
}

// Unir consultas
$final_query = implode(' UNION ALL ', $queries) . ' ORDER BY fecha_registro DESC';

// Paginación
$offset = ($search_params['page'] - 1) * RECORDS_PER_PAGE;
$final_query .= " LIMIT " . RECORDS_PER_PAGE . " OFFSET $offset";

// Ejecutar búsqueda
$resultados = [];
$total_resultados = 0;

try {
    $resultados = fetchAll($final_query, $all_params);
    
    // Contar total de resultados
    $count_query = "SELECT COUNT(*) as total FROM (" . implode(' UNION ALL ', $queries) . ") as combined";
    $total_resultados = fetchOne($count_query, $all_params)['total'] ?? 0;
} catch (Exception $e) {
    logError("Error en búsqueda: " . $e->getMessage());
    $error_message = "Error en la búsqueda. Inténtalo de nuevo.";
}

// Obtener datos para los filtros
$razas = getRazas();
$tamaños = getTamaños();
$sexos = getSexos();
$municipios = getMunicipios();
$colonias = [];

if (!empty($search_params['municipio_id'])) {
    $colonias = getColonias($search_params['municipio_id']);
}

// Calcular paginación
$total_pages = ceil($total_resultados / RECORDS_PER_PAGE);
?>

<div class="page-header">
    <div class="container">
        <h1>
            <i class="bi bi-search me-2"></i>
            Buscar Mascotas
        </h1>
        <p>Encuentra mascotas perdidas y reportes de avistamientos en CDMX</p>
    </div>
</div>

<div class="container">
    <!-- Formulario de Búsqueda -->
    <div class="card search-filters">
        <div class="card-body">
            <form method="GET" id="search-form" class="row g-3">
                <!-- Búsqueda por texto -->
                <div class="col-md-6">
                    <label for="q" class="form-label">Buscar por nombre o descripción</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               id="q" 
                               name="q" 
                               placeholder="Ej: Max, Golden Retriever, collar rojo..."
                               value="<?php echo escape($search_params['q']); ?>">
                    </div>
                </div>

                <!-- Tipo de reporte -->
                <div class="col-md-6">
                    <label for="tipo" class="form-label">Tipo de reporte</label>
                    <select class="form-select" id="tipo" name="tipo">
                        <option value="">Todos los tipos</option>
                        <option value="perdido" <?php echo $search_params['tipo'] === 'perdido' ? 'selected' : ''; ?>>
                            Mascotas Perdidas
                        </option>
                        <option value="encontrado" <?php echo $search_params['tipo'] === 'encontrado' ? 'selected' : ''; ?>>
                            Mascotas Encontradas
                        </option>
                    </select>
                </div>

                <!-- Raza -->
                <div class="col-md-4">
                    <label for="raza_id" class="form-label">Raza</label>
                    <select class="form-select" id="raza_id" name="raza_id">
                        <option value="">Todas las razas</option>
                        <?php foreach ($razas as $raza): ?>
                            <option value="<?php echo $raza['id']; ?>" 
                                    <?php echo $search_params['raza_id'] == $raza['id'] ? 'selected' : ''; ?>>
                                <?php echo escape($raza['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tamaño -->
                <div class="col-md-4">
                    <label for="tamaño_id" class="form-label">Tamaño</label>
                    <select class="form-select" id="tamaño_id" name="tamaño_id">
                        <option value="">Todos los tamaños</option>
                        <?php foreach ($tamaños as $tamaño): ?>
                            <option value="<?php echo $tamaño['id']; ?>" 
                                    <?php echo $search_params['tamaño_id'] == $tamaño['id'] ? 'selected' : ''; ?>>
                                <?php echo escape($tamaño['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sexo -->
                <div class="col-md-4">
                    <label for="sexo_id" class="form-label">Sexo</label>
                    <select class="form-select" id="sexo_id" name="sexo_id">
                        <option value="">Todos</option>
                        <?php foreach ($sexos as $sexo): ?>
                            <option value="<?php echo $sexo['id']; ?>" 
                                    <?php echo $search_params['sexo_id'] == $sexo['id'] ? 'selected' : ''; ?>>
                                <?php echo escape($sexo['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Municipio -->
                <div class="col-md-6">
                    <label for="municipio_id" class="form-label">Alcaldía</label>
                    <select class="form-select" id="municipio_id" name="municipio_id">
                        <option value="">Todas las alcaldías</option>
                        <?php foreach ($municipios as $municipio): ?>
                            <option value="<?php echo $municipio['id']; ?>" 
                                    <?php echo $search_params['municipio_id'] == $municipio['id'] ? 'selected' : ''; ?>>
                                <?php echo escape($municipio['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Colonia -->
                <div class="col-md-6">
                    <label for="colonia_id" class="form-label">Colonia</label>
                    <select class="form-select" id="colonia_id" name="colonia_id">
                        <option value="">Todas las colonias</option>
                        <?php foreach ($colonias as $colonia): ?>
                            <option value="<?php echo $colonia['id']; ?>" 
                                    <?php echo $search_params['colonia_id'] == $colonia['id'] ? 'selected' : ''; ?>>
                                <?php echo escape($colonia['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Fecha desde -->
                <div class="col-md-6">
                    <label for="fecha_desde" class="form-label">Fecha desde</label>
                    <input type="date" 
                           class="form-control" 
                           id="fecha_desde" 
                           name="fecha_desde"
                           value="<?php echo escape($search_params['fecha_desde']); ?>">
                </div>

                <!-- Fecha hasta -->
                <div class="col-md-6">
                    <label for="fecha_hasta" class="form-label">Fecha hasta</label>
                    <input type="date" 
                           class="form-control" 
                           id="fecha_hasta" 
                           name="fecha_hasta"
                           value="<?php echo escape($search_params['fecha_hasta']); ?>">
                </div>

                <!-- Botones -->
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>
                            Buscar
                        </button>
                        <a href="/pages/buscar.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Limpiar Filtros
                        </a>
                        <button type="button" class="btn btn-outline-primary" onclick="toggleMapView()">
                            <i class="bi bi-geo-alt me-1"></i>
                            <span id="map-toggle-text">Ver en Mapa</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <div class="row mt-4">
        <div class="col-12">
            <?php if (isset($error_message)): ?>
                <?php echo showAlert($error_message, 'danger'); ?>
            <?php else: ?>
                <!-- Header de resultados -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>
                        <?php if ($total_resultados > 0): ?>
                            Se encontraron <?php echo number_format($total_resultados); ?> resultados
                        <?php else: ?>
                            No se encontraron resultados
                        <?php endif; ?>
                    </h4>
                    
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="view-mode" id="list-view" checked>
                        <label class="btn btn-outline-primary" for="list-view">
                            <i class="bi bi-list"></i> Lista
                        </label>
                        
                        <input type="radio" class="btn-check" name="view-mode" id="grid-view">
                        <label class="btn btn-outline-primary" for="grid-view">
                            <i class="bi bi-grid"></i> Cuadrícula
                        </label>
                    </div>
                </div>

                <!-- Mapa (inicialmente oculto) -->
                <div id="map-container" class="map-container" style="display: none;">
                    <div id="map"></div>
                </div>

                <!-- Lista de resultados -->
                <div id="results-container">
                    <?php if (empty($resultados)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-search fs-1 text-muted mb-3"></i>
                            <h5 class="text-muted">No se encontraron mascotas con estos criterios</h5>
                            <p class="text-muted">Intenta con diferentes filtros o amplía tu búsqueda</p>
                            <div class="mt-3">
                                <a href="/pages/buscar.php" class="btn btn-outline-primary me-2">
                                    Limpiar Filtros
                                </a>
                                <a href="/pages/reportar_avistamiento.php" class="btn btn-primary">
                                    Reportar Avistamiento
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div id="list-results" class="row">
                            <?php foreach ($resultados as $mascota): ?>
                                <div class="col-md-6 col-lg-4 mb-4 result-item">
                                    <div class="card pet-card h-100">
                                        <?php 
                                        $foto = getMainPhoto($mascota['tipo'], $mascota['id']);
                                        ?>
                                        
                                        <div class="position-relative">
                                            <?php if ($foto): ?>
                                                <img src="<?php echo escape($foto['ruta_archivo']); ?>" 
                                                     class="card-img-top" 
                                                     alt="<?php echo escape($mascota['nombre'] ?: 'Sin nombre'); ?>"
                                                     style="height: 200px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                                     style="height: 200px;">
                                                    <i class="bi bi-heart fs-1 text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <span class="status-badge badge <?php echo $mascota['tipo'] === 'perdido' ? 'bg-danger' : 'bg-success'; ?>">
                                                <?php echo $mascota['tipo'] === 'perdido' ? 'Perdido' : 'Encontrado'; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <?php echo escape($mascota['nombre'] ?: 'Sin nombre'); ?>
                                            </h6>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-geo-alt me-1"></i>
                                                    <?php echo escape($mascota['municipio'] ?: 'CDMX'); ?>
                                                    <?php if ($mascota['colonia']): ?>
                                                        , <?php echo escape($mascota['colonia']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-tag me-1"></i>
                                                    <?php echo escape($mascota['raza'] ?: 'Raza no especificada'); ?>
                                                    <?php if ($mascota['tamaño']): ?>
                                                        • <?php echo escape($mascota['tamaño']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?php echo timeAgo($mascota['fecha_registro']); ?>
                                                </small>
                                            </div>
                                            
                                            <p class="card-text text-truncate">
                                                <?php echo escape(substr($mascota['descripcion'], 0, 100)); ?>
                                                <?php if (strlen($mascota['descripcion']) > 100): ?>...<?php endif; ?>
                                            </p>
                                        </div>
                                        
                                        <div class="card-footer bg-transparent">
                                            <a href="/pages/<?php echo $mascota['tipo']; ?>.php?id=<?php echo $mascota['id']; ?>" 
                                               class="btn btn-primary w-100">
                                                <i class="bi bi-eye me-1"></i>
                                                Ver Detalles
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Paginación -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Navegación de páginas">
                                <ul class="pagination justify-content-center">
                                    <!-- Página anterior -->
                                    <?php if ($search_params['page'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo buildSearchUrl($search_params, $search_params['page'] - 1); ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Páginas -->
                                    <?php
                                    $start_page = max(1, $search_params['page'] - 2);
                                    $end_page = min($total_pages, $search_params['page'] + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                        <li class="page-item <?php echo $i === $search_params['page'] ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?php echo buildSearchUrl($search_params, $i); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <!-- Página siguiente -->
                                    <?php if ($search_params['page'] < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo buildSearchUrl($search_params, $search_params['page'] + 1); ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Datos para el mapa
window.mapData = <?php echo json_encode(array_map(function($item) {
    return [
        'id' => $item['id'],
        'tipo' => $item['tipo'],
        'nombre' => $item['nombre'],
        'raza' => $item['raza'],
        'municipio' => $item['municipio'],
        'fecha_hora' => $item['fecha_hora'],
        'latitud' => $item['latitud'],
        'longitud' => $item['longitud']
    ];
}, array_filter($resultados, function($item) {
    return !empty($item['latitud']) && !empty($item['longitud']);
}))); ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Cargar colonias cuando se selecciona municipio
    const municipioSelect = document.getElementById('municipio_id');
    const coloniaSelect = document.getElementById('colonia_id');
    
    municipioSelect.addEventListener('change', function() {
        const municipioId = this.value;
        
        // Limpiar colonias
        coloniaSelect.innerHTML = '<option value="">Todas las colonias</option>';
        
        if (municipioId) {
            fetch(`/api/colonias.php?municipio_id=${municipioId}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(colonia => {
                        const option = document.createElement('option');
                        option.value = colonia.id;
                        option.textContent = colonia.nombre;
                        coloniaSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error cargando colonias:', error);
                });
        }
    });
    
    // Alternar vista de mapa
    window.toggleMapView = function() {
        const mapContainer = document.getElementById('map-container');
        const resultsContainer = document.getElementById('results-container');
        const toggleText = document.getElementById('map-toggle-text');
        
        if (mapContainer.style.display === 'none') {
            mapContainer.style.display = 'block';
            resultsContainer.style.display = 'none';
            toggleText.textContent = 'Ver Lista';
            
            // Inicializar mapa si no existe
            if (typeof map === 'undefined') {
                initMap();
            } else {
                loadMarkers(window.mapData);
            }
        } else {
            mapContainer.style.display = 'none';
            resultsContainer.style.display = 'block';
            toggleText.textContent = 'Ver en Mapa';
        }
    };
    
    // Cambiar vista de lista/cuadrícula
    const listViewBtn = document.getElementById('list-view');
    const gridViewBtn = document.getElementById('grid-view');
    const listResults = document.getElementById('list-results');
    
    gridViewBtn.addEventListener('change', function() {
        if (this.checked) {
            listResults.className = 'row';
            // Cambiar clases de columnas para vista de cuadrícula
            document.querySelectorAll('.result-item').forEach(item => {
                item.className = 'col-md-4 col-lg-3 mb-4 result-item';
            });
        }
    });
    
    listViewBtn.addEventListener('change', function() {
        if (this.checked) {
            listResults.className = 'row';
            // Cambiar clases de columnas para vista de lista
            document.querySelectorAll('.result-item').forEach(item => {
                item.className = 'col-md-6 col-lg-4 mb-4 result-item';
            });
        }
    });
});
</script>

<?php
// Función helper para construir URLs de búsqueda
function buildSearchUrl($params, $page) {
    $new_params = $params;
    $new_params['page'] = $page;
    
    // Remover parámetros vacíos
    $new_params = array_filter($new_params, function($value) {
        return $value !== '' && $value !== null;
    });
    
    return '/pages/buscar.php?' . http_build_query($new_params);
}

require_once __DIR__ . '/../includes/footer.php';
?>
