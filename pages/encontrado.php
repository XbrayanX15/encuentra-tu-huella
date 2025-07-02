<?php
// Incluir funciones primero
require_once __DIR__ . '/../includes/functions.php';
startSession();

// Obtener ID del reporte
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener datos del reporte
try {
    $reporte = fetchOne("
        SELECT pe.*, 
               r.nombre as raza_nombre,
               s.nombre as sexo_nombre,
               t.nombre as tamano_nombre,
               tp.nombre as tipo_pelo_nombre,
               es.nombre as estado_salud_nombre,
               ee.nombre as estado_emocional_nombre,
               m.nombre as municipio_nombre,
               u.nombre as usuario_nombre,
               u.telefono as usuario_telefono,
               u.email as usuario_email
        FROM perros_encontrados pe
        LEFT JOIN razas r ON pe.raza_id = r.id
        LEFT JOIN sexos s ON pe.sexo_id = s.id
        LEFT JOIN tamaños t ON pe.tamaño_id = t.id
        LEFT JOIN tipos_pelo tp ON pe.tipo_pelo_id = tp.id
        LEFT JOIN estados_salud es ON pe.estado_salud_id = es.id
        LEFT JOIN estados_emocionales ee ON pe.estado_emocional_id = ee.id
        LEFT JOIN municipios m ON pe.municipio_id = m.id
        LEFT JOIN usuarios u ON pe.usuario_id = u.id
        WHERE pe.id = ? AND pe.estado = 'disponible'
    ", [$id]);

    if (!$reporte) {
        header('Location: index.php');
        exit;
    }

    // Obtener fotos del reporte
    $fotos = fetchAll("SELECT * FROM fotos WHERE perro_encontrado_id = ? ORDER BY principal DESC, fecha_subida ASC", [$id]);

} catch (Exception $e) {
    logError("Error en encontrado.php: " . $e->getMessage());
    header('Location: index.php');
    exit;
}

// Ahora incluir el header
$page_title = 'Detalle de Mascota Encontrada - ' . ($reporte['nombre'] ?: 'Sin nombre');
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="buscar.php">Buscar</a></li>
            <li class="breadcrumb-item active" aria-current="page">Mascota Encontrada</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Contenido Principal -->
        <div class="col-lg-8">
            <!-- Información Básica -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-search me-1"></i>
                        <?php echo escape($reporte['nombre'] ?: 'Mascota Encontrada'); ?>
                        <span class="badge bg-light text-dark ms-2">Encontrada</span>
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Galería de Fotos -->
                    <?php if (!empty($fotos)): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div id="petCarousel" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    <?php foreach ($fotos as $index => $foto): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="../<?php echo escape($foto['ruta_archivo']); ?>" 
                                             class="d-block w-100 rounded" 
                                             style="height: 400px; object-fit: cover;"
                                             alt="Foto de <?php echo escape($reporte['nombre'] ?: 'la mascota'); ?>">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (count($fotos) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#petCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#petCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Descripción -->
                    <div class="mb-4">
                        <h5>Descripción</h5>
                        <p class="text-muted">
                            <?php echo nl2br(escape($reporte['descripcion'])); ?>
                        </p>
                    </div>

                    <!-- Características -->
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Características</h5>
                            <ul class="list-unstyled">
                                <?php if ($reporte['raza_nombre']): ?>
                                <li class="mb-2">
                                    <strong>Raza:</strong> 
                                    <?php echo escape($reporte['raza_nombre']); ?>
                                </li>
                                <?php endif; ?>
                                
                                <?php if ($reporte['edad']): ?>
                                <li class="mb-2">
                                    <strong>Edad aproximada:</strong> 
                                    <?php echo $reporte['edad']; ?> años
                                </li>
                                <?php endif; ?>
                                
                                <?php if ($reporte['sexo_nombre']): ?>
                                <li class="mb-2">
                                    <strong>Sexo:</strong> 
                                    <?php echo escape($reporte['sexo_nombre']); ?>
                                </li>
                                <?php endif; ?>
                                
                                <?php if ($reporte['tamano_nombre']): ?>
                                <li class="mb-2">
                                    <strong>Tamaño:</strong> 
                                    <?php echo escape($reporte['tamano_nombre']); ?>
                                </li>
                                <?php endif; ?>
                                
                                <?php if ($reporte['tipo_pelo_nombre']): ?>
                                <li class="mb-2">
                                    <strong>Tipo de pelo:</strong> 
                                    <?php echo escape($reporte['tipo_pelo_nombre']); ?>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Información del Avistamiento</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <strong>Municipio:</strong> 
                                    <?php echo escape($reporte['municipio_nombre']); ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Fecha y hora del avistamiento:</strong> 
                                    <?php echo formatDate($reporte['fecha_hora'], 'd/m/Y H:i'); ?>
                                </li>
                                <?php if ($reporte['latitud'] && $reporte['longitud']): ?>
                                <li class="mb-2">
                                    <strong>Ubicación:</strong> 
                                    <?php echo number_format($reporte['latitud'], 6); ?>, <?php echo number_format($reporte['longitud'], 6); ?>
                                </li>
                                <?php endif; ?>
                                <li class="mb-2">
                                    <strong>Resguardado:</strong> 
                                    <?php echo $reporte['resguardado'] ? 'Sí, en lugar seguro' : 'No, aún en la calle'; ?>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <?php if ($reporte['senas_particulares']): ?>
                    <div class="mb-3">
                        <h5>Señas Particulares</h5>
                        <p class="text-muted">
                            <?php echo nl2br(escape($reporte['senas_particulares'])); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <!-- Placa de identificación -->
                    <?php if ($reporte['tiene_placa']): ?>
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="bi bi-tag me-1"></i>
                            Placa de Identificación
                        </h6>
                        <p class="mb-0">
                            Esta mascota tiene placa de identificación.
                            <?php if ($reporte['nombre_placa']): ?>
                                <br><strong>Nombre en la placa:</strong> <?php echo escape($reporte['nombre_placa']); ?>
                            <?php endif; ?>
                            <?php if ($reporte['ruac_placa']): ?>
                                <br><strong>RUAC:</strong> <?php echo escape($reporte['ruac_placa']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Estado de Salud -->
            <?php if ($reporte['estado_salud_nombre'] || $reporte['estado_emocional_nombre']): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-heart-pulse me-1"></i>
                        Estado de Salud
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($reporte['estado_salud_nombre']): ?>
                        <p><strong>Estado físico:</strong><br>
                        <?php echo escape($reporte['estado_salud_nombre']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($reporte['estado_emocional_nombre']): ?>
                        <p><strong>Estado emocional:</strong><br>
                        <?php echo escape($reporte['estado_emocional_nombre']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Información de Contacto -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-person-check me-1"></i>
                        Información de Contacto
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($reporte['usuario_nombre']): ?>
                        <p><strong>Reportado por:</strong><br>
                        <?php echo escape($reporte['usuario_nombre']); ?></p>
                        
                        <?php if ($reporte['usuario_telefono']): ?>
                        <p><strong>Teléfono:</strong><br>
                        <a href="tel:<?php echo escape($reporte['usuario_telefono']); ?>" class="text-decoration-none">
                            <?php echo escape($reporte['usuario_telefono']); ?>
                        </a></p>
                        <?php endif; ?>
                        
                        <?php if ($reporte['usuario_email']): ?>
                        <p><strong>Email:</strong><br>
                        <a href="mailto:<?php echo escape($reporte['usuario_email']); ?>" class="text-decoration-none">
                            <?php echo escape($reporte['usuario_email']); ?>
                        </a></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><strong>Contacto:</strong><br>
                        <?php echo nl2br(escape($reporte['contacto'])); ?></p>
                    <?php endif; ?>

                    <small class="text-muted d-block mt-3">
                        <i class="bi bi-clock me-1"></i>
                        Reportado <?php echo timeAgo($reporte['fecha_registro']); ?>
                    </small>
                </div>
            </div>

            <!-- Acciones -->
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-gear me-1"></i>
                        Acciones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="reportar_perdida.php" class="btn btn-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            ¡Es mi mascota!
                        </a>
                        <a href="buscar.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-1"></i>
                            Volver a Buscar
                        </a>
                        <button class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i>
                            Imprimir
                        </button>
                        <button class="btn btn-outline-info" onclick="navigator.share ? navigator.share({title: 'Mascota Encontrada', url: window.location.href}) : alert('Copia la URL para compartir')">
                            <i class="bi bi-share me-1"></i>
                            Compartir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mapa (si hay coordenadas) -->
<?php if ($reporte['latitud'] && $reporte['longitud']): ?>
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-geo-alt me-1"></i>
                Ubicación del Avistamiento
            </h5>
        </div>
        <div class="card-body">
            <div id="map" style="height: 300px; border-radius: 8px;"></div>
        </div>
    </div>
</div>

<script>
// Mapa básico con Leaflet (requerirá incluir la librería en el header)
document.addEventListener('DOMContentLoaded', function() {
    if (typeof L !== 'undefined') {
        const lat = <?php echo $reporte['latitud']; ?>;
        const lng = <?php echo $reporte['longitud']; ?>;
        
        const map = L.map('map').setView([lat, lng], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        L.marker([lat, lng])
            .addTo(map)
            .bindPopup('Lugar donde fue encontrada la mascota')
            .openPopup();
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
