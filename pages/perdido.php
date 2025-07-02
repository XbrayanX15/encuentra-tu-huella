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
        SELECT pp.*, 
               r.nombre as raza_nombre,
               s.nombre as sexo_nombre,
               t.nombre as tamano_nombre,
               tp.nombre as tipo_pelo_nombre,
               m.nombre as municipio_nombre,
               u.nombre as usuario_nombre,
               u.telefono as usuario_telefono,
               u.email as usuario_email
        FROM perros_perdidos pp
        LEFT JOIN razas r ON pp.raza_id = r.id
        LEFT JOIN sexos s ON pp.sexo_id = s.id
        LEFT JOIN tamaños t ON pp.tamaño_id = t.id
        LEFT JOIN tipos_pelo tp ON pp.tipo_pelo_id = tp.id
        LEFT JOIN municipios m ON pp.municipio_id = m.id
        LEFT JOIN usuarios u ON pp.usuario_id = u.id
        WHERE pp.id = ? AND pp.estado = 'perdido'
    ", [$id]);

    if (!$reporte) {
        header('Location: index.php');
        exit;
    }

    // Obtener fotos del reporte
    $fotos = fetchAll("SELECT * FROM fotos WHERE perro_perdido_id = ? ORDER BY principal DESC, fecha_subida ASC", [$id]);

} catch (Exception $e) {
    logError("Error en perdido.php: " . $e->getMessage());
    header('Location: index.php');
    exit;
}

// Ahora incluir el header
$page_title = 'Detalle de Mascota Perdida - ' . ($reporte['nombre'] ?: 'Sin nombre');
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="buscar.php">Buscar</a></li>
                    <li class="breadcrumb-item active">Mascota Perdida</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Información Principal -->
            <div class="card shadow mb-4">
                <div class="card-header bg-danger text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Mascota Perdida: <?php echo escape($reporte['nombre'] ?: 'Sin nombre'); ?>
                        </h4>
                        <span class="badge bg-light text-dark">
                            ID: <?php echo $reporte['id']; ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Fotos -->
                    <?php if (!empty($fotos)): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="bi bi-camera me-1"></i>
                                Fotos
                            </h5>
                            <div class="row">
                                <?php foreach ($fotos as $foto): ?>
                                <div class="col-md-4 mb-3">
                                    <img src="/<?php echo escape($foto['ruta_archivo']); ?>" 
                                         alt="Foto de <?php echo escape($reporte['nombre']); ?>" 
                                         class="img-fluid rounded shadow-sm">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Información de la Mascota -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="bi bi-heart me-1"></i>
                                Información de la Mascota
                            </h5>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <strong>Nombre:</strong> 
                                    <?php echo escape($reporte['nombre'] ?: 'Sin nombre'); ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Raza:</strong> 
                                    <?php echo escape($reporte['raza_nombre'] ?: 'No especificada'); ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Sexo:</strong> 
                                    <?php echo escape($reporte['sexo_nombre'] ?: 'No especificado'); ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Tamaño:</strong> 
                                    <?php echo escape($reporte['tamano_nombre'] ?: 'No especificado'); ?>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <?php if ($reporte['edad']): ?>
                                <li class="mb-2">
                                    <strong>Edad aproximada:</strong> 
                                    <?php echo $reporte['edad'] . ' años'; ?>
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
                    </div>

                    <!-- Descripción -->
                    <?php if ($reporte['descripcion']): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="bi bi-file-text me-1"></i>
                                Descripción
                            </h5>
                            <p class="text-muted">
                                <?php echo nl2br(escape($reporte['descripcion'])); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Información de la Pérdida -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-warning mb-3">
                                <i class="bi bi-geo-alt me-1"></i>
                                Información de la Pérdida
                            </h5>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <strong>Municipio:</strong> 
                                    <?php echo escape($reporte['municipio_nombre']); ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Fecha y hora de pérdida:</strong> 
                                    <?php echo formatDate($reporte['fecha_hora'], 'd/m/Y H:i'); ?>
                                </li>
                                <?php if ($reporte['latitud'] && $reporte['longitud']): ?>
                                <li class="mb-2">
                                    <strong>Ubicación:</strong> 
                                    <?php echo number_format($reporte['latitud'], 6); ?>, <?php echo number_format($reporte['longitud'], 6); ?>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <?php if ($reporte['senas_particulares']): ?>
                            <div class="mb-3">
                                <strong>Señas particulares:</strong>
                                <p class="text-muted mt-1">
                                    <?php echo nl2br(escape($reporte['senas_particulares'])); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

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
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Información de Contacto -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-telephone me-1"></i>
                        Información de Contacto
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($reporte['usuario_nombre']): ?>
                        <p><strong>Reportado por:</strong><br>
                        <?php echo escape($reporte['usuario_nombre']); ?></p>
                        
                        <?php if ($reporte['usuario_telefono']): ?>
                        <p><strong>Teléfono:</strong><br>
                        <a href="tel:<?php echo escape($reporte['usuario_telefono']); ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-telephone me-1"></i>
                            <?php echo escape($reporte['usuario_telefono']); ?>
                        </a></p>
                        <?php endif; ?>
                        
                        <?php if ($reporte['usuario_email']): ?>
                        <p><strong>Email:</strong><br>
                        <a href="mailto:<?php echo escape($reporte['usuario_email']); ?>" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-envelope me-1"></i>
                            Enviar Email
                        </a></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><strong>Contacto:</strong><br>
                        <?php echo nl2br(escape($reporte['contacto'])); ?></p>
                    <?php endif; ?>
                    
                    <small class="text-muted">
                        <i class="bi bi-calendar me-1"></i>
                        Reportado: <?php echo timeAgo($reporte['fecha_registro']); ?>
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
                        <a href="reportar_avistamiento.php" class="btn btn-success">
                            <i class="bi bi-eye me-1"></i>
                            ¡La encontré!
                        </a>
                        <a href="buscar.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-1"></i>
                            Volver a Buscar
                        </a>
                        <button class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i>
                            Imprimir
                        </button>
                        <button class="btn btn-outline-info" onclick="compartir()">
                            <i class="bi bi-share me-1"></i>
                            Compartir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function compartir() {
    if (navigator.share) {
        navigator.share({
            title: 'Mascota Perdida: <?php echo escape($reporte["nombre"] ?: "Sin nombre"); ?>',
            text: 'Ayuda a encontrar esta mascota perdida en <?php echo escape($reporte["municipio_nombre"]); ?>',
            url: window.location.href
        });
    } else {
        // Fallback para navegadores que no soportan Web Share API
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            alert('URL copiada al portapapeles');
        });
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
