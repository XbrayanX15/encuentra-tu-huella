<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Mis Reportes';
require_once __DIR__ . '/../includes/header.php';

// Verificar que el usuario esté logueado
requireLogin();

$user_id = $_SESSION['user_id'];
$success_type = $_GET['success'] ?? '';

// Debug temporal - eliminar después
if (isset($_GET['debug'])) {
    echo "<div class='alert alert-info'>";
    echo "Debug: Usuario ID = " . $user_id . "<br>";
    
    // Contar reportes totales del usuario
    $count_perdidos = fetchOne("SELECT COUNT(*) as total FROM perros_perdidos WHERE usuario_id = ?", [$user_id]);
    $count_encontrados = fetchOne("SELECT COUNT(*) as total FROM perros_encontrados WHERE usuario_id = ?", [$user_id]);
    
    echo "Reportes de pérdida: " . $count_perdidos['total'] . "<br>";
    echo "Reportes de avistamiento: " . $count_encontrados['total'] . "<br>";
    echo "</div>";
}

// Mostrar mensaje de éxito si existe
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($_SESSION['success_message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
    unset($_SESSION['success_message']);
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1><i class="fas fa-clipboard-list text-primary"></i> Mis Reportes</h1>
            
            <?php if ($success_type === 'perdida'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>¡Reporte enviado!</strong> Tu reporte de mascota perdida ha sido publicado. Te notificaremos si alguien la encuentra.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($success_type === 'avistamiento'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>¡Avistamiento reportado!</strong> Gracias por ayudar. El dueño podrá contactarte para más información.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Nav tabs -->
            <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="perdidos-tab" data-bs-toggle="tab" data-bs-target="#perdidos" type="button" role="tab">
                        <i class="fas fa-exclamation-triangle text-danger"></i> Mascotas Perdidas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="encontrados-tab" data-bs-toggle="tab" data-bs-target="#encontrados" type="button" role="tab">
                        <i class="fas fa-eye text-success"></i> Mascotas Encontradas
                    </button>
                </li>
            </ul>

            <!-- Tab content -->
            <div class="tab-content" id="reportTabContent">
                <!-- Mascotas perdidas -->
                <div class="tab-pane fade show active" id="perdidos" role="tabpanel">
                    <?php
                    try {
                        $perdidos = fetchAll("
                            SELECT pp.*, r.nombre as raza_nombre, m.nombre as municipio_nombre, 
                                   s.nombre as sexo_nombre, c.nombre as colonia_nombre
                            FROM perros_perdidos pp
                            LEFT JOIN razas r ON pp.raza_id = r.id
                            LEFT JOIN municipios m ON pp.municipio_id = m.id
                            LEFT JOIN sexos s ON pp.sexo_id = s.id
                            LEFT JOIN colonias c ON pp.colonia_id = c.id
                            WHERE pp.usuario_id = ?
                            ORDER BY pp.fecha_registro DESC
                        ", [$user_id]);
                    } catch (Exception $e) {
                        $perdidos = [];
                    }
                    ?>

                    <?php if (empty($perdidos)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h3 class="text-muted">No has reportado mascotas perdidas</h3>
                            <p class="text-muted">Esperamos que nunca tengas que usar esta sección.</p>
                            <a href="reportar_perdida.php" class="btn btn-danger">
                                <i class="fas fa-exclamation-triangle"></i> Reportar Pérdida
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($perdidos as $perdido): ?>
                                <?php
                                // Obtener la primera foto del reporte
                                $imagen_src = getPrimeraFotoPerdido($perdido['id'], 'pages');
                                ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 shadow-sm border-danger">
                                        <img src="<?php echo htmlspecialchars($imagen_src); ?>" 
                                             class="card-img-top" 
                                             style="height: 200px; object-fit: cover;"
                                             alt="<?php echo htmlspecialchars($perdido['nombre']); ?>">
                                        
                                        <div class="card-body">
                                            <h5 class="card-title text-danger">
                                                <?php echo htmlspecialchars($perdido['nombre'] ?: 'Sin nombre'); ?>
                                                <span class="badge bg-danger">PERDIDO</span>
                                            </h5>
                                                              <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-dog"></i> <?php echo htmlspecialchars($perdido['raza_nombre'] ?? 'Mestizo/Criollo'); ?>
                                    <?php if (isset($perdido['sexo_nombre']) && $perdido['sexo_nombre']): ?>
                                        • <?php echo htmlspecialchars($perdido['sexo_nombre']); ?>
                                    <?php endif; ?>
                                    <?php if (isset($perdido['edad']) && $perdido['edad']): ?>
                                        • Edad: <?php echo $perdido['edad']; ?> años
                                    <?php endif; ?>
                                </small>
                            </div>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt"></i> 
                                                    <?php 
                                                    $ubicacion = [];
                                                    if (isset($perdido['municipio_nombre']) && $perdido['municipio_nombre']) {
                                                        $ubicacion[] = $perdido['municipio_nombre'];
                                                    }
                                                    if (isset($perdido['colonia_nombre']) && $perdido['colonia_nombre']) {
                                                        $ubicacion[] = $perdido['colonia_nombre'];
                                                    }
                                                    echo htmlspecialchars(!empty($ubicacion) ? implode(', ', $ubicacion) : 'Ubicación no especificada');
                                                    ?>
                                                </small>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i> 
                                                    Perdido: <?php echo isset($perdido['fecha_hora']) && $perdido['fecha_hora'] ? date('d/m/Y', strtotime($perdido['fecha_hora'])) : 'Fecha no especificada'; ?>
                                                </small>
                                            </div>
                                                              <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    <?php 
                                    $desc = isset($perdido['descripcion']) ? (string)$perdido['descripcion'] : '';
                                    if ($desc) {
                                        echo htmlspecialchars(substr($desc, 0, 100)); 
                                        if (strlen($desc) > 100) {
                                            echo '...';
                                        }
                                    } else {
                                        echo 'Sin descripción';
                                    }
                                    ?>
                                </small>
                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    Estado: 
                                                    <span class="badge bg-<?php echo $perdido['estado'] === 'Activo' ? 'danger' : 'success'; ?>">
                                                        <?php echo htmlspecialchars($perdido['estado']); ?>
                                                    </span>
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <div class="card-footer bg-transparent">
                                            <div class="btn-group w-100" role="group">
                                                <a href="detalle_perdido.php?id=<?php echo $perdido['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                                <a href="editar_perdido.php?id=<?php echo $perdido['id']; ?>" 
                                                   class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                                <?php if ($perdido['estado'] === 'Activo'): ?>
                                                    <button type="button" 
                                                            class="btn btn-outline-success btn-sm" 
                                                            onclick="marcarEncontrado(<?php echo $perdido['id']; ?>)">
                                                        <i class="fas fa-check"></i> Encontrado
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Mascotas encontradas -->
                <div class="tab-pane fade" id="encontrados" role="tabpanel">
                    <?php
                    try {
                        $encontrados = fetchAll("
                            SELECT pe.*, r.nombre as raza_nombre, m.nombre as municipio_nombre,
                                   s.nombre as sexo_nombre, c.nombre as colonia_nombre
                            FROM perros_encontrados pe
                            LEFT JOIN razas r ON pe.raza_id = r.id
                            LEFT JOIN municipios m ON pe.municipio_id = m.id
                            LEFT JOIN sexos s ON pe.sexo_id = s.id
                            LEFT JOIN colonias c ON pe.colonia_id = c.id
                            WHERE pe.usuario_id = ?
                            ORDER BY pe.fecha_registro DESC
                        ", [$user_id]);
                    } catch (Exception $e) {
                        $encontrados = [];
                    }
                    ?>

                    <?php if (empty($encontrados)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                            <h3 class="text-muted">No has reportado mascotas encontradas</h3>
                            <p class="text-muted">¡Qué bueno sería que pudieras ayudar a una mascota perdida!</p>
                            <a href="reportar_avistamiento.php" class="btn btn-success">
                                <i class="fas fa-eye"></i> Reportar Avistamiento
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($encontrados as $encontrado): ?>
                                <?php
                                // Obtener la primera foto del reporte encontrado
                                $imagen_src_encontrado = getPrimeraFotoEncontrado($encontrado['id'], 'pages');
                                ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 shadow-sm border-success">
                                        <img src="<?php echo htmlspecialchars($imagen_src_encontrado); ?>" 
                                             class="card-img-top" 
                                             style="height: 200px; object-fit: cover;"
                                             alt="Mascota encontrada">
                                        
                                        <div class="card-body">
                                            <h5 class="card-title text-success">
                                                <?php echo htmlspecialchars($encontrado['nombre'] ?: 'Sin nombre conocido'); ?>
                                                <span class="badge bg-success">ENCONTRADO</span>
                                            </h5>
                                                              <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-dog"></i> <?php echo htmlspecialchars($encontrado['raza_nombre'] ?? 'Mestizo/Criollo'); ?>
                                    <?php if (isset($encontrado['sexo_nombre']) && $encontrado['sexo_nombre']): ?>
                                        • <?php echo htmlspecialchars($encontrado['sexo_nombre']); ?>
                                    <?php endif; ?>
                                    <?php if (isset($encontrado['edad']) && $encontrado['edad']): ?>
                                        • Edad: <?php echo $encontrado['edad']; ?> años
                                    <?php endif; ?>
                                </small>
                            </div>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt"></i> 
                                                    <?php 
                                                    $ubicacion_encontrado = [];
                                                    if (isset($encontrado['municipio_nombre']) && $encontrado['municipio_nombre']) {
                                                        $ubicacion_encontrado[] = $encontrado['municipio_nombre'];
                                                    }
                                                    if (isset($encontrado['colonia_nombre']) && $encontrado['colonia_nombre']) {
                                                        $ubicacion_encontrado[] = $encontrado['colonia_nombre'];
                                                    }
                                                    echo htmlspecialchars(!empty($ubicacion_encontrado) ? implode(', ', $ubicacion_encontrado) : 'Ubicación no especificada');
                                                    ?>
                                                </small>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i> 
                                                    Encontrado: <?php echo isset($encontrado['fecha_hora']) && $encontrado['fecha_hora'] ? date('d/m/Y', strtotime($encontrado['fecha_hora'])) : 'Fecha no especificada'; ?>
                                                </small>
                                            </div>
                                                              <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    <?php 
                                    $desc_encontrado = isset($encontrado['descripcion']) ? (string)$encontrado['descripcion'] : '';
                                    if ($desc_encontrado) {
                                        echo htmlspecialchars(substr($desc_encontrado, 0, 100));
                                        if (strlen($desc_encontrado) > 100) {
                                            echo '...';
                                        }
                                    } else {
                                        echo 'Sin descripción';
                                    }
                                    ?>
                                </small>
                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    Estado: 
                                                    <span class="badge bg-<?php echo $encontrado['estado'] === 'Activo' ? 'success' : 'secondary'; ?>">
                                                        <?php echo htmlspecialchars($encontrado['estado']); ?>
                                                    </span>
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <div class="card-footer bg-transparent">
                                            <div class="btn-group w-100" role="group">
                                                <a href="detalle_encontrado.php?id=<?php echo $encontrado['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                                <a href="editar_encontrado.php?id=<?php echo $encontrado['id']; ?>" 
                                                   class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                                <?php if ($encontrado['estado'] === 'Activo'): ?>
                                                    <button type="button" 
                                                            class="btn btn-outline-info btn-sm" 
                                                            onclick="marcarReunido(<?php echo $encontrado['id']; ?>)">
                                                        <i class="fas fa-handshake"></i> Reunido
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function marcarEncontrado(perdidoId) {
    if (confirm('¿Confirmas que tu mascota fue encontrada?')) {
        fetch('../api/cambiar_estado.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tipo: 'perdido',
                id: perdidoId,
                estado: 'Encontrado'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al actualizar el estado');
            }
        });
    }
}

function marcarReunido(encontradoId) {
    if (confirm('¿Confirmas que la mascota fue reunida con su familia?')) {
        fetch('../api/cambiar_estado.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tipo: 'encontrado',
                id: encontradoId,
                estado: 'Reunido'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al actualizar el estado');
            }
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>
