<?php
$page_title = 'Inicio';
require_once __DIR__ . '/../includes/header.php';

// Agregar clase al body para páginas con hero
echo '<script>document.body.classList.add("has-hero");</script>';

// Obtener estadísticas básicas
$stats = [
    'perdidos' => 0,
    'encontrados' => 0,
    'reuniones' => 0,
    'usuarios' => 0
];

try {
    $stats['perdidos'] = fetchOne("SELECT COUNT(*) as total FROM perros_perdidos WHERE estado = 'perdido'")['total'] ?? 0;
    $stats['encontrados'] = fetchOne("SELECT COUNT(*) as total FROM perros_encontrados WHERE estado = 'disponible'")['total'] ?? 0;
    $stats['reuniones'] = fetchOne("SELECT COUNT(*) as total FROM perros_perdidos WHERE estado = 'encontrado'")['total'] ?? 0;
    $stats['usuarios'] = fetchOne("SELECT COUNT(*) as total FROM usuarios WHERE activo = true")['total'] ?? 0;
} catch (Exception $e) {
    logError("Error obteniendo estadísticas: " . $e->getMessage());
}

// Obtener reportes recientes
$reportes_recientes = fetchAll("
    SELECT 'perdido' as tipo, id, nombre, fecha_registro, 
           (SELECT nombre FROM razas WHERE id = raza_id) as raza,
           (SELECT nombre FROM municipios WHERE id = municipio_id) as municipio
    FROM perros_perdidos 
    WHERE estado = 'perdido'
    UNION ALL
    SELECT 'encontrado' as tipo, id, nombre, fecha_registro,
           (SELECT nombre FROM razas WHERE id = raza_id) as raza,
           (SELECT nombre FROM municipios WHERE id = municipio_id) as municipio
    FROM perros_encontrados 
    WHERE estado = 'disponible'
    ORDER BY fecha_registro DESC 
    LIMIT 6
");
?>

<!-- Hero Section -->
<div class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">
                    Encuentra a tu mejor amigo
                </h1>
                <p class="lead mb-4">
                    Cada mascota es un miembro de la familia, y juntos podemos traer de vuelta ese amor incondicional. Ayudamos a que los corazones se reencuentren, porque cada mascota merece volver a su hogar.
                </p>
                <div class="d-flex flex-column flex-sm-row gap-3">
                    <a href="buscar.php" class="btn btn-light btn-lg">
                        <i class="bi bi-search me-2"></i>Buscar Mascota
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <a href="reportar_perdida.php" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-exclamation-triangle me-2"></i>Reportar Pérdida
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-person-plus me-2"></i>Iniciar Sesión
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="../img/perro.png" alt="Perro feliz" class="img-fluid" style="max-height: 400px;">
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="container">
    <div class="row mb-5">
        <div class="col-md-3 mb-4">
            <div class="stats-card">
                <span class="stats-number"><?php echo number_format($stats['perdidos']); ?></span>
                <span class="stats-label">Mascotas Perdidas</span>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="stats-card">
                <span class="stats-number"><?php echo number_format($stats['encontrados']); ?></span>
                <span class="stats-label">Encontradas</span>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="stats-card">
                <span class="stats-number"><?php echo number_format($stats['reuniones']); ?></span>
                <span class="stats-label">Reuniones Exitosas</span>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="stats-card">
                <span class="stats-number"><?php echo number_format($stats['usuarios']); ?></span>
                <span class="stats-label">Usuarios Activos</span>
            </div>
        </div>
    </div>

    <!-- Cómo funciona -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-5">¿Cómo funciona?</h2>
        </div>
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-1-circle fs-1"></i>
                </div>
                <h4>Reporta</h4>
                <p class="text-muted">
                    Registra a tu mascota perdida con fotos, descripción y ubicación donde se perdió.
                </p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-2-circle fs-1"></i>
                </div>
                <h4>Busca</h4>
                <p class="text-muted">
                    Explora reportes de mascotas encontradas y avistamientos en tu zona.
                </p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-3-circle fs-1"></i>
                </div>
                <h4>Reúnete</h4>
                <p class="text-muted">
                    Conecta con quien encontró a tu mascota y organiza el reencuentro.
                </p>
            </div>
        </div>
    </div>

    <!-- Reportes Recientes -->
    <?php if (!empty($reportes_recientes)): ?>
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-4">Reportes Recientes</h2>
            <div class="row">
                <?php foreach ($reportes_recientes as $reporte): ?>
                <div class="col-md-4 mb-4">
                    <div class="card pet-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0">
                                    <?php echo escape($reporte['nombre'] ?: 'Sin nombre'); ?>
                                </h6>
                                <span class="badge <?php echo $reporte['tipo'] === 'perdido' ? 'bg-danger' : 'bg-success'; ?>">
                                    <?php echo $reporte['tipo'] === 'perdido' ? 'Perdido' : 'Encontrado'; ?>
                                </span>
                            </div>
                            <p class="card-text text-muted small mb-2">
                                <i class="bi bi-geo-alt me-1"></i>
                                <?php echo escape($reporte['municipio']); ?>
                            </p>
                            <p class="card-text text-muted small mb-2">
                                <i class="bi bi-tag me-1"></i>
                                <?php echo escape($reporte['raza'] ?: 'Raza no especificada'); ?>
                            </p>
                            <p class="card-text text-muted small">
                                <i class="bi bi-clock me-1"></i>
                                <?php echo timeAgo($reporte['fecha_registro']); ?>
                            </p>
                            <a href="<?php echo $reporte['tipo'] === 'perdido' ? 'perdido' : 'encontrado'; ?>.php?id=<?php echo $reporte['id']; ?>" 
                               class="btn btn-primary btn-sm w-100">
                                Ver Detalles
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center">
                <a href="buscar.php" class="btn btn-outline-primary">
                    Ver Todos los Reportes
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Call to Action -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card bg-primary text-white text-center">
                <div class="card-body py-5">
                    <h3 class="mb-3">¿Viste una mascota perdida?</h3>
                    <p class="lead mb-4">
                        Cualquier persona puede reportar el avistamiento de una mascota, 
                        no necesitas estar registrado para ayudar.
                    </p>
                    <a href="reportar_avistamiento.php" class="btn btn-light btn-lg">
                        <i class="bi bi-eye me-2"></i>Reportar Avistamiento
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Consejos -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="text-center mb-4">Consejos Importantes</h2>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title text-primary">
                        <i class="bi bi-shield-check me-2"></i>
                        Si encuentras una mascota
                    </h5>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check-circle text-success me-2"></i>Mantén la calma y acércate lentamente</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Verifica si tiene placa identificatoria</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Toma fotos desde diferentes ángulos</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Reporta la ubicación exacta</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Si es posible, resguárdala en lugar seguro</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title text-primary">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Si perdiste tu mascota
                    </h5>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check-circle text-success me-2"></i>Busca inmediatamente en la zona</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Pregunta a vecinos y transeúntes</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Reporta en nuestra plataforma con fotos</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Revisa refugios y veterinarias cercanas</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Mantén actualizada la información de contacto</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Animaciones de entrada para las estadísticas
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Animación de números contadores
    const numbers = document.querySelectorAll('.stats-number');
    numbers.forEach(number => {
        const finalNumber = parseInt(number.textContent.replace(/,/g, ''));
        let currentNumber = 0;
        const increment = finalNumber / 50;
        
        const timer = setInterval(() => {
            currentNumber += increment;
            if (currentNumber >= finalNumber) {
                currentNumber = finalNumber;
                clearInterval(timer);
            }
            number.textContent = Math.floor(currentNumber).toLocaleString();
        }, 30);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
