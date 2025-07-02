<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Procesar eliminación de mascota
if (isset($_POST['delete_pet'])) {
    $pet_id = (int)$_POST['pet_id'];
    
    try {
        // Eliminar fotos asociadas
        executeQuery("DELETE FROM fotos WHERE mascota_registrada_id = ?", [$pet_id]);
        
        // Eliminar mascota
        executeQuery("DELETE FROM mascotas_registradas WHERE id = ? AND usuario_id = ?", [$pet_id, $user_id]);
        
        $message = 'Mascota eliminada exitosamente.';
    } catch (Exception $e) {
        $error = 'Error al eliminar la mascota.';
    }
}

// Obtener mascotas del usuario
try {
    $mascotas = fetchAll("
        SELECT 
            mr.*,
            r.nombre as raza_nombre,
            s.nombre as sexo_nombre,
            t.nombre as tamano_nombre
        FROM mascotas_registradas mr
        LEFT JOIN razas r ON mr.raza_id = r.id
        LEFT JOIN sexos s ON mr.sexo_id = s.id
        LEFT JOIN tamaños t ON mr.tamaño_id = t.id
        WHERE mr.usuario_id = ? 
        ORDER BY mr.fecha_registro DESC
    ", [$user_id]);
    
    // Obtener fotos para cada mascota
    foreach ($mascotas as &$mascota) {
        $fotos = fetchAll("
            SELECT ruta_archivo 
            FROM fotos 
            WHERE mascota_registrada_id = ? 
            ORDER BY fecha_subida ASC
        ", [$mascota['id']]);
        
        // Validar que la consulta haya devuelto un array
        if ($fotos && is_array($fotos)) {
            $mascota['fotos'] = array_column($fotos, 'ruta_archivo');
        } else {
            $mascota['fotos'] = [];
        }
    }
} catch (Exception $e) {
    $error = 'Error al cargar las mascotas: ' . $e->getMessage();
    $mascotas = [];
}

$page_title = 'Mis Mascotas';
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-paw text-primary"></i> Mis Mascotas</h1>
                <a href="registrar_mascota.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Registrar Nueva Mascota
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($mascotas)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-paw fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">No tienes mascotas registradas</h3>
                    <p class="text-muted">Registra tu primera mascota para comenzar a usar el sistema.</p>
                    <a href="registrar_mascota.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Registrar Mi Primera Mascota
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($mascotas as $mascota): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <?php 
                                // Obtener primera foto usando la función utilitaria
                                $foto_principal = !empty($mascota['fotos']) ? getImagePath($mascota['fotos'][0]) : getImagePath('img/no-image.svg');
                                ?>
                                <img src="<?php echo htmlspecialchars($foto_principal); ?>" 
                                     class="card-img-top" 
                                     style="height: 200px; object-fit: cover;"
                                     alt="<?php echo htmlspecialchars($mascota['nombre']); ?>"
                                     onerror="this.src='<?php echo getImagePath('img/no-image.svg'); ?>'">
                                
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($mascota['nombre']); ?>
                                        <span class="badge bg-<?php echo ($mascota['sexo_nombre'] ?? '') === 'Macho' ? 'primary' : 'pink'; ?>">
                                            <?php echo htmlspecialchars($mascota['sexo_nombre'] ?? 'No especificado'); ?>
                                        </span>
                                    </h5>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-dog"></i> <?php echo htmlspecialchars($mascota['raza_nombre'] ?? 'Mestizo'); ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> 
                                            <?php echo ($mascota['edad'] ?? 0) > 0 ? $mascota['edad'] . ' años' : 'Edad no especificada'; ?>
                                        </small>
                                    </div>
                                    
                                    <?php if (!empty($mascota['senas_particulares'])): ?>
                                        <p class="card-text small text-muted">
                                            <?php echo htmlspecialchars(substr($mascota['senas_particulares'], 0, 100)); ?>
                                            <?php if (strlen($mascota['senas_particulares']) > 100): ?>...<?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Registrado: <?php echo date('d/m/Y', strtotime($mascota['fecha_registro'])); ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="card-footer bg-transparent">
                                    <div class="btn-group w-100" role="group">
                                        <a href="detalle_mascota.php?id=<?php echo $mascota['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                        <a href="editar_mascota.php?id=<?php echo $mascota['id']; ?>" 
                                           class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm" 
                                                onclick="confirmarEliminacion(<?php echo $mascota['id']; ?>, '<?php echo htmlspecialchars($mascota['nombre']); ?>')">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
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

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar a <strong id="petName"></strong>?</p>
                <p class="text-danger">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="pet_id" id="petIdToDelete">
                    <button type="submit" name="delete_pet" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarEliminacion(petId, petName) {
    document.getElementById('petIdToDelete').value = petId;
    document.getElementById('petName').textContent = petName;
    var modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>
