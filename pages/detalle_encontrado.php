<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: buscar.php');
    exit();
}

try {
    $encontrado = fetchOne("
        SELECT pe.*, r.nombre as raza_nombre, t.descripcion as tamano_desc,
               tp.nombre as tipo_pelo_nombre, es.nombre as estado_salud_nombre,
               ee.nombre as estado_emocional_nombre, a.nombre as alcaldia_nombre,
               c.nombre as colonia_nombre, u.nombre as usuario_nombre
        FROM perros_encontrados pe
        LEFT JOIN razas r ON pe.raza_id = r.id
        LEFT JOIN tamaños t ON pe.tamaño_id = t.id
        LEFT JOIN tipos_pelo tp ON pe.tipo_pelo_id = tp.id
        LEFT JOIN estados_salud es ON pe.estado_salud_id = es.id
        LEFT JOIN estados_emocionales ee ON pe.estado_emocional_id = ee.id
        LEFT JOIN alcaldias a ON pe.alcaldia_id = a.id
        LEFT JOIN colonias c ON pe.colonia_id = c.id
        LEFT JOIN usuarios u ON pe.usuario_id = u.id
        WHERE pe.id = ?
    ", [$id]);
    
    if (!$encontrado) {
        header('Location: buscar.php');
        exit();
    }
    
    // Obtener fotos del reporte encontrado
    $fotos = fetchAll("
        SELECT ruta_archivo 
        FROM fotos 
        WHERE perro_encontrado_id = ? 
        ORDER BY fecha_subida ASC
    ", [$id]);
    
    // Procesar fotos
    if ($fotos && is_array($fotos)) {
        $encontrado['fotos'] = array_column($fotos, 'ruta_archivo');
    } else {
        $encontrado['fotos'] = [];
    }
    
} catch (Exception $e) {
    header('Location: buscar.php');
    exit();
}

$page_title = 'Mascota Encontrada: ' . ($encontrado['nombre'] ?: 'Sin nombre conocido');
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h2 class="mb-0">
                        <i class="fas fa-heart"></i> 
                        <?php echo htmlspecialchars($encontrado['nombre'] ?: 'Mascota encontrada'); ?>
                        <span class="badge bg-light text-success ms-2">ENCONTRADO</span>
                    </h2>
                </div>
                <div class="card-body">
                    <!-- Galería de fotos -->
                    <?php if (!empty($encontrado['fotos'])): ?>
                        <div id="fotoCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach ($encontrado['fotos'] as $index => $foto): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="<?php echo htmlspecialchars(getImagePath($foto)); ?>" 
                                             class="d-block w-100" 
                                             style="height: 400px; object-fit: cover;"
                                             alt="Foto de mascota encontrada"
                                             onerror="this.src='<?php echo getImagePath('img/no-image.svg'); ?>'">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($encontrado['fotos']) > 1): ?>
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
                            <img src="<?php echo getImagePath('img/no-image.svg'); ?>" class="img-fluid" style="max-height: 300px;" alt="Sin foto">
                        </div>
                    <?php endif; ?>

                    <!-- Información básica -->
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="text-success mb-3">Información de la Mascota</h4>
                            
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td><?php echo htmlspecialchars($encontrado['nombre'] ?: 'No conocido'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Raza:</strong></td>
                                    <td><?php echo htmlspecialchars($encontrado['raza_nombre'] ?? 'Mestizo'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Sexo:</strong></td>
                                    <td><?php echo htmlspecialchars($encontrado['sexo']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Edad aproximada:</strong></td>
                                    <td><?php echo $encontrado['edad_aproximada'] ? $encontrado['edad_aproximada'] . ' años' : 'No determinada'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tamaño:</strong></td>
                                    <td><?php echo htmlspecialchars($encontrado['tamano_desc'] ?? 'No especificado'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo de pelo:</strong></td>
                                    <td><?php echo htmlspecialchars($encontrado['tipo_pelo_nombre'] ?? 'No especificado'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Color primario:</strong></td>
                                    <td><?php echo htmlspecialchars($encontrado['color_primario']); ?></td>
                                </tr>
                                <?php if ($encontrado['color_secundario']): ?>
                                <tr>
                                    <td><strong>Color secundario:</strong></td>
                                    <td><?php echo htmlspecialchars($encontrado['color_secundario']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td><strong>Estado de salud:</strong></td>
                                    <td><?php echo htmlspecialchars($encontrado['estado_salud_nombre'] ?? 'Bueno'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Estado emocional:</strong></td>
                                    <td><?php echo htmlspecialchars($encontrado['estado_emocional_nombre'] ?? 'Tranquilo'); ?></td>
                                </tr>
                                <?php if ($encontrado['tiene_chip']): ?>
                                <tr>
                                    <td><strong>Microchip:</strong></td>
                                    <td>
                                        <i class="fas fa-check text-success"></i> Verificado
                                        <?php if ($encontrado['numero_chip']): ?>
                                            <br><small class="text-muted">Número: <?php echo htmlspecialchars($encontrado['numero_chip']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h4 class="text-success mb-3">Información del Avistamiento</h4>
                            
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Fecha encontrado:</strong></td>
                                    <td><?php echo date('d/m/Y', strtotime($encontrado['fecha_avistamiento'])); ?></td>
                                </tr>
                                <?php if ($encontrado['hora_avistamiento']): ?>
                                <tr>
                                    <td><strong>Hora aproximada:</strong></td>
                                    <td><?php echo date('H:i', strtotime($encontrado['hora_avistamiento'])); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td><strong>Alcaldía:</strong></td>
                                    <td><?php echo htmlspecialchars($encontrado['alcaldia_nombre']); ?></td>
                                </tr>
                                <?php if ($encontrado['colonia_nombre']): ?>
                                <tr>
                                    <td><strong>Colonia:</strong></td>
                                    <td><?php echo htmlspecialchars($encontrado['colonia_nombre']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($encontrado['direccion_avistamiento']): ?>
                                <tr>
                                    <td><strong>Dirección:</strong></td>
                                    <td><?php echo htmlspecialchars($encontrado['direccion_avistamiento']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td><strong>Reportado por:</strong></td>
                                    <td><?php echo htmlspecialchars($encontrado['usuario_nombre']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $encontrado['estado'] === 'Activo' ? 'success' : 'secondary'; ?>">
                                            <?php echo htmlspecialchars($encontrado['estado']); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Descripciones -->
                    <?php if ($encontrado['descripcion']): ?>
                        <div class="mt-4">
                            <h5>Descripción de la mascota</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($encontrado['descripcion'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($encontrado['condicion_animal']): ?>
                        <div class="mt-4">
                            <h5>Condición del animal cuando fue encontrado</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($encontrado['condicion_animal'])); ?></p>
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
                        <p class="fw-bold"><?php echo htmlspecialchars($encontrado['usuario_nombre']); ?></p>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="tel:<?php echo htmlspecialchars($encontrado['telefono_contacto']); ?>" 
                           class="btn btn-success">
                            <i class="fas fa-phone"></i> Llamar: <?php echo htmlspecialchars($encontrado['telefono_contacto']); ?>
                        </a>
                        
                        <a href="https://wa.me/52<?php echo preg_replace('/[^0-9]/', '', $encontrado['telefono_contacto']); ?>?text=Hola,%20vi%20tu%20reporte%20de%20mascota%20encontrada%20en%20PetFinder%20CDMX" 
                           target="_blank" class="btn btn-success" style="background-color: #25D366;">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                    
                    <small class="text-muted d-block mt-3">
                        <i class="fas fa-calendar"></i> 
                        Reportado el <?php echo date('d/m/Y', strtotime($encontrado['fecha_reporte'])); ?>
                    </small>
                </div>
            </div>
            
            <!-- Mapa -->
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Ubicación del Avistamiento</h5>
                </div>
                <div class="card-body">
                    <div id="map" style="height: 250px; border-radius: 8px;"></div>
                </div>
            </div>
            
            <!-- Acciones -->
            <div class="card shadow">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-exclamation-circle"></i> ¿Es tu mascota?</h5>
                </div>
                <div class="card-body text-center">
                    <?php if ($encontrado['estado'] === 'Activo'): ?>
                        <p class="text-muted mb-3">Si esta es tu mascota perdida, contacta inmediatamente usando los datos de arriba.</p>
                        
                        <div class="alert alert-info">
                            <small>
                                <strong>Consejos para el contacto:</strong><br>
                                • Sé específico sobre características únicas<br>
                                • Proporciona pruebas de propiedad<br>
                                • Agradece al reportante por su ayuda
                            </small>
                        </div>
                    <?php else: ?>
                        <p class="text-success">Esta mascota ya fue reunida con su familia.</p>
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
            title: 'Mascota encontrada en CDMX',
            text: 'Se encontró esta mascota en CDMX - PetFinder',
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Enlace copiado al portapapeles');
        });
    }
}

// Inicializar mapa
function initMap() {
    const alcaldiaCoords = { lat: 19.4326, lng: -99.1332 };
    
    const map = new google.maps.Map(document.getElementById('map'), {
        zoom: 13,
        center: alcaldiaCoords,
    });
    
    const marker = new google.maps.Marker({
        position: alcaldiaCoords,
        map: map,
        title: 'Zona donde se encontró: <?php echo addslashes($encontrado['alcaldia_nombre']); ?>',
        icon: {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="green">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
            `),
            scaledSize: new google.maps.Size(32, 32)
        }
    });
    
    const infoWindow = new google.maps.InfoWindow({
        content: `
            <div style="max-width: 200px;">
                <h6>Mascota Encontrada</h6>
                <p><strong><?php echo addslashes($encontrado['nombre'] ?: 'Sin nombre'); ?></strong></p>
                <p>Encontrado en: <?php echo addslashes($encontrado['alcaldia_nombre']); ?></p>
                <p>Fecha: <?php echo date('d/m/Y', strtotime($encontrado['fecha_avistamiento'])); ?></p>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
