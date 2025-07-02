<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: buscar.php');
    exit();
}

try {
    $perdido = fetchOne("
        SELECT pp.*, r.nombre as raza_nombre, t.nombre as tamano_nombre,
               tp.nombre as tipo_pelo_nombre, m.nombre as municipio_nombre,
               c.nombre as colonia_nombre, u.nombre as usuario_nombre, u.email as usuario_email,
               s.nombre as sexo_nombre
        FROM perros_perdidos pp
        LEFT JOIN razas r ON pp.raza_id = r.id
        LEFT JOIN tamaños t ON pp.tamaño_id = t.id
        LEFT JOIN tipos_pelo tp ON pp.tipo_pelo_id = tp.id
        LEFT JOIN municipios m ON pp.municipio_id = m.id
        LEFT JOIN colonias c ON pp.colonia_id = c.id
        LEFT JOIN usuarios u ON pp.usuario_id = u.id
        LEFT JOIN sexos s ON pp.sexo_id = s.id
        WHERE pp.id = ?
    ", [$id]);
    
    if (!$perdido) {
        header('Location: buscar.php');
        exit();
    }
    
    // Obtener fotos del reporte
    $fotos = fetchAll("
        SELECT ruta_archivo 
        FROM fotos 
        WHERE perro_perdido_id = ? 
        ORDER BY fecha_subida ASC
    ", [$id]);
    
    $perdido['fotos'] = array_column($fotos, 'ruta_archivo');
    
} catch (Exception $e) {
    header('Location: buscar.php');
    exit();
}

$page_title = 'Mascota Perdida: ' . ($perdido['nombre'] ?: 'Sin nombre');
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h2 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <?php echo htmlspecialchars($perdido['nombre'] ?: 'Mascota sin nombre'); ?>
                        <span class="badge bg-warning text-dark ms-2">PERDIDO</span>
                    </h2>
                </div>
                <div class="card-body">
                    <!-- Galería de fotos -->
                    <?php if (!empty($perdido['fotos'])): ?>
                        <div id="fotoCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach ($perdido['fotos'] as $index => $foto): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="../<?php echo htmlspecialchars($foto); ?>" 
                                             class="d-block w-100" 
                                             style="height: 400px; object-fit: cover;"
                                             alt="Foto de <?php echo htmlspecialchars($perdido['nombre']); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($perdido['fotos']) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#fotoCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#fotoCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center mb-4">
                            <img src="../img/no-image.svg" class="img-fluid" style="max-height: 300px;" alt="Sin foto">
                        </div>
                    <?php endif; ?>

                    <!-- Información básica -->
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="text-danger mb-3">Información de la Mascota</h4>
                            
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td><?php echo htmlspecialchars($perdido['nombre'] ?: 'No especificado'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Raza:</strong></td>
                                    <td><?php echo htmlspecialchars($perdido['raza_nombre'] ?? 'Mestizo'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Sexo:</strong></td>
                                    <td><?php echo htmlspecialchars($perdido['sexo_nombre'] ?? 'No especificado'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Edad:</strong></td>
                                    <td><?php echo isset($perdido['edad']) && $perdido['edad'] ? $perdido['edad'] . ' años' : 'No especificada'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tamaño:</strong></td>
                                    <td><?php echo htmlspecialchars($perdido['tamano_nombre'] ?? 'No especificado'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo de pelo:</strong></td>
                                    <td><?php echo htmlspecialchars($perdido['tipo_pelo_nombre'] ?? 'No especificado'); ?></td>
                                </tr>
                                <?php if (isset($perdido['tiene_placa']) && $perdido['tiene_placa']): ?>
                                <tr>
                                    <td><strong>Placa:</strong></td>
                                    <td>
                                        <i class="fas fa-check text-success"></i> Sí
                                        <?php if (isset($perdido['nombre_placa']) && $perdido['nombre_placa']): ?>
                                            <br><small class="text-muted">Nombre: <?php echo htmlspecialchars($perdido['nombre_placa']); ?></small>
                                        <?php endif; ?>
                                        <?php if (isset($perdido['ruac_placa']) && $perdido['ruac_placa']): ?>
                                            <br><small class="text-muted">RUAC: <?php echo htmlspecialchars($perdido['ruac_placa']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h4 class="text-danger mb-3">Información de la Pérdida</h4>
                            
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Fecha y hora de pérdida:</strong></td>
                                    <td><?php echo isset($perdido['fecha_hora']) && $perdido['fecha_hora'] ? date('d/m/Y H:i', strtotime($perdido['fecha_hora'])) : 'No especificada'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Ubicación:</strong></td>
                                    <td>
                                        <?php 
                                        $ubicacion_detalle = [];
                                        if (isset($perdido['municipio_nombre']) && $perdido['municipio_nombre']) {
                                            $ubicacion_detalle[] = $perdido['municipio_nombre'];
                                        }
                                        if (isset($perdido['colonia_nombre']) && $perdido['colonia_nombre']) {
                                            $ubicacion_detalle[] = $perdido['colonia_nombre'];
                                        }
                                        echo htmlspecialchars(!empty($ubicacion_detalle) ? implode(', ', $ubicacion_detalle) : 'Ubicación no especificada');
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Contacto:</strong></td>
                                    <td><?php echo nl2br(htmlspecialchars($perdido['contacto'] ?? 'No especificado')); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Descripciones -->
                    <?php if (isset($perdido['descripcion']) && $perdido['descripcion']): ?>
                        <div class="mt-4">
                            <h5>Descripción</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($perdido['descripcion'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($perdido['senas_particulares']) && $perdido['senas_particulares']): ?>
                        <div class="mt-4">
                            <h5>Señas particulares</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($perdido['senas_particulares'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Información de contacto -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-phone"></i> Información de Contacto</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h6>Reportado por:</h6>
                        <p class="fw-bold"><?php echo htmlspecialchars($perdido['usuario_nombre'] ?? 'Usuario'); ?></p>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Información de contacto:</h6>
                        <?php echo nl2br(htmlspecialchars($perdido['contacto'] ?? 'No especificado')); ?>
                    </div>
                    
                    <small class="text-muted d-block mt-3">
                        <i class="fas fa-calendar"></i> 
                        Reportado el <?php echo isset($perdido['fecha_registro']) && $perdido['fecha_registro'] ? date('d/m/Y', strtotime($perdido['fecha_registro'])) : 'Fecha no disponible'; ?>
                    </small>
                </div>
            </div>
            
            <!-- Mapa -->
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Ubicación</h5>
                </div>
                <div class="card-body">
                    <div id="map" style="height: 250px; border-radius: 8px;"></div>
                </div>
            </div>
            
            <!-- Estado -->
            <div class="card shadow">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Estado del Reporte</h5>
                </div>
                <div class="card-body text-center">
                    <h4>
                        <span class="badge bg-<?php echo (isset($perdido['estado']) && $perdido['estado'] === 'encontrado') ? 'success' : 'danger'; ?> fs-6">
                            <?php echo htmlspecialchars($perdido['estado'] ?? 'perdido'); ?>
                        </span>
                    </h4>
                    
                    <?php if (!isset($perdido['estado']) || $perdido['estado'] !== 'encontrado'): ?>
                        <p class="text-muted">Esta mascota aún está perdida. Si la has visto, contacta inmediatamente.</p>
                    <?php else: ?>
                        <p class="text-success">¡Esta mascota ya fue encontrada!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="buscar.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Volver a la búsqueda
            </a>
            
            <button onclick="compartir()" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-share"></i> Compartir
            </button>
        </div>
    </div>
</div>

<script>
function compartir() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo addslashes($perdido['nombre'] ?: 'Mascota perdida'); ?>',
            text: 'Ayuda a encontrar esta mascota perdida en CDMX',
            url: window.location.href
        });
    } else {
        // Fallback para navegadores que no soportan Web Share API
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Enlace copiado al portapapeles');
        });
    }
}

// Inicializar mapa
function initMap() {
    // Coordenadas aproximadas de la alcaldía
    const alcaldiaCoords = { lat: 19.4326, lng: -99.1332 }; // Default CDMX center
    
    const map = new google.maps.Map(document.getElementById('map'), {
        zoom: 13,
        center: alcaldiaCoords,
    });
    
    const marker = new google.maps.Marker({
        position: alcaldiaCoords,
        map: map,
        title: 'Zona donde se perdió: <?php echo addslashes($perdido['alcaldia_nombre']); ?>',
        icon: {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="red">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
            `),
            scaledSize: new google.maps.Size(32, 32)
        }
    });
    
    const infoWindow = new google.maps.InfoWindow({
        content: `
            <div style="max-width: 200px;">
                <h6>Mascota Perdida</h6>
                <p><strong><?php echo addslashes($perdido['nombre'] ?: 'Sin nombre'); ?></strong></p>
                <p>Perdido en: <?php echo addslashes($perdido['alcaldia_nombre']); ?></p>
                <p>Fecha: <?php echo date('d/m/Y', strtotime($perdido['fecha_perdida'])); ?></p>
            </div>
        `
    });
    
    marker.addListener('click', () => {
        infoWindow.open(map, marker);
    });
}

// Cargar el script de Google Maps
if (typeof google === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap';
    script.async = true;
    document.head.appendChild(script);
} else {
    initMap();
}
</script>

<?php include '../includes/footer.php'; ?>
