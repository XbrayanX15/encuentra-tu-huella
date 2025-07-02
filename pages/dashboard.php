<?php
$page_title = 'Mi Panel';
require_once __DIR__ . '/../includes/header.php';

// Verificar autenticación
requireLogin();

$user = getCurrentUser();
$user_id = $user['id'];

// Obtener estadísticas del usuario
$stats = [
    'mascotas_registradas' => 0,
    'perdidos_activos' => 0,
    'encontrados_reportados' => 0,
    'reuniones_exitosas' => 0
];

try {
    $stats['mascotas_registradas'] = fetchOne("SELECT COUNT(*) as total FROM mascotas_registradas WHERE usuario_id = ? AND activo = true", [$user_id])['total'] ?? 0;
    $stats['perdidos_activos'] = fetchOne("SELECT COUNT(*) as total FROM perros_perdidos WHERE usuario_id = ? AND estado = 'perdido'", [$user_id])['total'] ?? 0;
    $stats['encontrados_reportados'] = fetchOne("SELECT COUNT(*) as total FROM perros_encontrados WHERE usuario_id = ?", [$user_id])['total'] ?? 0;
    $stats['reuniones_exitosas'] = fetchOne("SELECT COUNT(*) as total FROM perros_perdidos WHERE usuario_id = ? AND estado = 'encontrado'", [$user_id])['total'] ?? 0;
} catch (Exception $e) {
    logError("Error obteniendo estadísticas del usuario: " . $e->getMessage());
}

// Obtener reportes recientes del usuario
$reportes_recientes = fetchAll("
    SELECT 'perdido' as tipo, id, nombre, descripcion, fecha_registro, estado,
           (SELECT nombre FROM razas WHERE id = raza_id) as raza,
           (SELECT nombre FROM municipios WHERE id = municipio_id) as municipio
    FROM perros_perdidos 
    WHERE usuario_id = ?
    UNION ALL
    SELECT 'encontrado' as tipo, id, nombre, descripcion, fecha_registro, estado,
           (SELECT nombre FROM razas WHERE id = raza_id) as raza,
           (SELECT nombre FROM municipios WHERE id = municipio_id) as municipio
    FROM perros_encontrados 
    WHERE usuario_id = ?
    ORDER BY fecha_registro DESC 
    LIMIT 5
", [$user_id, $user_id]);

// Obtener mascotas del usuario
$mascotas = getUserPets($user_id);
?>

<div class="page-header">
    <div class="container">
        <h1>
            <i class="bi bi-speedometer2 me-2"></i>
            Bienvenido, <?php echo escape($user['nombre']); ?>
        </h1>
        <p>Panel de control de tu cuenta Pet Finder</p>
    </div>
</div>

<div class="container">
    <!-- Estadísticas del Usuario -->
    <div class="row mb-5">
        <div class="col-md-3 mb-4">
            <div class="stats-card">
                <span class="stats-number"><?php echo $stats['mascotas_registradas']; ?></span>
                <span class="stats-label">Mascotas Registradas</span>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="stats-card">
                <span class="stats-number"><?php echo $stats['perdidos_activos']; ?></span>
                <span class="stats-label">Perdidos Activos</span>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="stats-card">
                <span class="stats-number"><?php echo $stats['encontrados_reportados']; ?></span>
                <span class="stats-label">Encontrados Reportados</span>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="stats-card">
                <span class="stats-number"><?php echo $stats['reuniones_exitosas']; ?></span>
                <span class="stats-label">Reuniones Exitosas</span>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="row mb-5">
        <div class="col-12">
            <h3 class="mb-4">
                <i class="bi bi-lightning me-2"></i>
                Acciones Rápidas
            </h3>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bi bi-plus-circle fs-1 text-primary mb-3"></i>
                    <h5 class="card-title">Registrar Mascota</h5>
                    <p class="card-text">Agrega una nueva mascota a tu perfil</p>
                    <a href="/pages/mis_mascotas.php?action=new" class="btn btn-primary">
                        Registrar
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bi bi-exclamation-triangle fs-1 text-danger mb-3"></i>
                    <h5 class="card-title">Reportar Pérdida</h5>
                    <p class="card-text">Reporta que tu mascota se perdió</p>
                    <a href="/pages/reportar_perdida.php" class="btn btn-danger">
                        Reportar
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bi bi-eye fs-1 text-success mb-3"></i>
                    <h5 class="card-title">Reportar Avistamiento</h5>
                    <p class="card-text">Reporta una mascota que encontraste</p>
                    <a href="/pages/reportar_avistamiento.php" class="btn btn-success">
                        Reportar
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="bi bi-search fs-1 text-info mb-3"></i>
                    <h5 class="card-title">Buscar Mascotas</h5>
                    <p class="card-text">Busca mascotas perdidas o encontradas</p>
                    <a href="/pages/buscar.php" class="btn btn-info">
                        Buscar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Mis Mascotas -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-collection me-2"></i>
                        Mis Mascotas
                    </h5>
                    <a href="/pages/mis_mascotas.php" class="btn btn-outline-primary btn-sm">
                        Ver Todas
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($mascotas)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-plus-circle fs-1 text-muted mb-3"></i>
                            <p class="text-muted">No tienes mascotas registradas</p>
                            <a href="/pages/mis_mascotas.php?action=new" class="btn btn-primary">
                                Registrar Primera Mascota
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($mascotas, 0, 3) as $mascota): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <?php 
                                    $foto = getMainPhoto('mascota', $mascota['id']);
                                    if ($foto): ?>
                                        <img src="<?php echo escape($foto['ruta_archivo']); ?>" 
                                             alt="<?php echo escape($mascota['nombre']); ?>" 
                                             class="rounded-circle" 
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 50px; height: 50px;">
                                            <i class="bi bi-heart text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1"><?php echo escape($mascota['nombre']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo escape($mascota['raza'] ?? 'Raza no especificada'); ?> • 
                                        <?php echo escape($mascota['tamaño'] ?? 'Tamaño no especificado'); ?>
                                    </small>
                                </div>
                                <div class="flex-shrink-0">
                                    <a href="/pages/mis_mascotas.php?id=<?php echo $mascota['id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($mascotas) > 3): ?>
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    y <?php echo count($mascotas) - 3; ?> más...
                                </small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Reportes Recientes -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Reportes Recientes
                    </h5>
                    <a href="/pages/mis_reportes.php" class="btn btn-outline-primary btn-sm">
                        Ver Todos
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($reportes_recientes)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-file-earmark-text fs-1 text-muted mb-3"></i>
                            <p class="text-muted">No tienes reportes recientes</p>
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="/pages/reportar_perdida.php" class="btn btn-danger btn-sm">
                                    Reportar Pérdida
                                </a>
                                <a href="/pages/reportar_avistamiento.php" class="btn btn-success btn-sm">
                                    Reportar Avistamiento
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reportes_recientes as $reporte): ?>
                            <div class="d-flex align-items-center mb-3 p-2 border rounded">
                                <div class="flex-shrink-0">
                                    <span class="badge <?php echo $reporte['tipo'] === 'perdido' ? 'bg-danger' : 'bg-success'; ?>">
                                        <?php echo $reporte['tipo'] === 'perdido' ? 'Perdido' : 'Encontrado'; ?>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">
                                        <?php echo escape($reporte['nombre'] ?: 'Sin nombre'); ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?php echo escape($reporte['municipio']); ?> • 
                                        <?php echo timeAgo($reporte['fecha_registro']); ?>
                                    </small>
                                    <br>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst($reporte['estado']); ?>
                                    </span>
                                </div>
                                <div class="flex-shrink-0">
                                    <a href="/pages/<?php echo $reporte['tipo']; ?>.php?id=<?php echo $reporte['id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas y Notificaciones -->
    <?php if ($stats['perdidos_activos'] > 0): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-warning">
                <h5 class="alert-heading">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Mascotas Perdidas Activas
                </h5>
                <p class="mb-0">
                    Tienes <?php echo $stats['perdidos_activos']; ?> 
                    <?php echo $stats['perdidos_activos'] === 1 ? 'mascota perdida' : 'mascotas perdidas'; ?> 
                    activa<?php echo $stats['perdidos_activos'] === 1 ? '' : 's'; ?>.
                    Te recomendamos revisar regularmente los reportes de mascotas encontradas.
                </p>
                <hr>
                <div class="d-flex gap-2">
                    <a href="/pages/buscar.php" class="btn btn-warning">
                        <i class="bi bi-search me-1"></i>
                        Buscar Avistamientos
                    </a>
                    <a href="/pages/mis_reportes.php?tipo=perdido" class="btn btn-outline-warning">
                        Ver Mis Reportes
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animación de entrada para las estadísticas
    const statsCards = document.querySelectorAll('.stats-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.animation = 'fadeIn 0.6s ease-out forwards';
                }, index * 100);
            }
        });
    });
    
    statsCards.forEach(card => {
        card.style.opacity = '0';
        observer.observe(card);
    });
    
    // Actualizar estadísticas en tiempo real cada 5 minutos
    setInterval(function() {
        fetch('/api/user-stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateStats(data.stats);
                }
            })
            .catch(error => {
                console.error('Error actualizando estadísticas:', error);
            });
    }, 300000); // 5 minutos
});

function updateStats(stats) {
    const elements = {
        'mascotas_registradas': document.querySelector('.stats-card:nth-child(1) .stats-number'),
        'perdidos_activos': document.querySelector('.stats-card:nth-child(2) .stats-number'),
        'encontrados_reportados': document.querySelector('.stats-card:nth-child(3) .stats-number'),
        'reuniones_exitosas': document.querySelector('.stats-card:nth-child(4) .stats-number')
    };
    
    Object.keys(elements).forEach(key => {
        if (elements[key] && stats[key] !== undefined) {
            animateNumber(elements[key], parseInt(elements[key].textContent), stats[key]);
        }
    });
}

function animateNumber(element, from, to) {
    const duration = 1000;
    const start = Date.now();
    
    function update() {
        const progress = Math.min((Date.now() - start) / duration, 1);
        const current = Math.floor(from + (to - from) * progress);
        element.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    
    update();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
