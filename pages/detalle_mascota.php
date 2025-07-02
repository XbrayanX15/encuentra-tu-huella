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
$mascota_id = (int)($_GET['id'] ?? 0);
$error = '';
$mascota = null;

if ($mascota_id <= 0) {
    header('Location: mis_mascotas.php');
    exit();
}

// Obtener datos de la mascota
try {
    $mascota = fetchOne("
        SELECT 
            mr.*,
            r.nombre as raza_nombre,
            s.nombre as sexo_nombre,
            t.nombre as tamano_nombre,
            tp.nombre as tipo_pelo_nombre
        FROM mascotas_registradas mr
        LEFT JOIN razas r ON mr.raza_id = r.id
        LEFT JOIN sexos s ON mr.sexo_id = s.id
        LEFT JOIN tamaños t ON mr.tamaño_id = t.id
        LEFT JOIN tipos_pelo tp ON mr.tipo_pelo_id = tp.id
        WHERE mr.id = ? AND mr.usuario_id = ?
    ", [$mascota_id, $user_id]);
    
    if (!$mascota) {
        header('Location: mis_mascotas.php');
        exit();
    }
    
    // Obtener fotos de la mascota
    $fotos = fetchAll("
        SELECT ruta_archivo 
        FROM fotos 
        WHERE mascota_registrada_id = ? 
        ORDER BY fecha_subida ASC
    ", [$mascota_id]);
    
    $mascota['fotos'] = $fotos && is_array($fotos) ? array_column($fotos, 'ruta_archivo') : [];
    
} catch (Exception $e) {
    $error = 'Error al cargar los datos de la mascota.';
}

$page_title = 'Detalle de ' . ($mascota['nombre'] ?? 'Mascota');
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-paw text-primary"></i> <?php echo htmlspecialchars($mascota['nombre'] ?? 'Mascota'); ?></h1>
                <div>
                    <a href="editar_mascota.php?id=<?php echo $mascota_id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a href="mis_mascotas.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php elseif ($mascota): ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-camera"></i> Fotos</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($mascota['fotos'])): ?>
                                    <div id="fotosCarousel" class="carousel slide" data-bs-ride="carousel">
                                        <div class="carousel-inner">
                                            <?php foreach ($mascota['fotos'] as $index => $foto): ?>
                                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                    <img src="<?php echo htmlspecialchars(getImagePath($foto)); ?>" 
                                                         class="d-block w-100" 
                                                         style="height: 300px; object-fit: cover;"
                                                         alt="Foto de <?php echo htmlspecialchars($mascota['nombre']); ?>">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if (count($mascota['fotos']) > 1): ?>
                                            <button class="carousel-control-prev" type="button" data-bs-target="#fotosCarousel" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon"></span>
                                            </button>
                                            <button class="carousel-control-next" type="button" data-bs-target="#fotosCarousel" data-bs-slide="next">
                                                <span class="carousel-control-next-icon"></span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <img src="<?php echo getImagePath('img/no-image.svg'); ?>" 
                                         class="img-fluid" 
                                         style="height: 300px; object-fit: cover; width: 100%;"
                                         alt="Sin fotos">
                                    <p class="text-muted text-center mt-2">No hay fotos disponibles</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-info-circle"></i> Información</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Nombre:</th>
                                        <td><?php echo htmlspecialchars($mascota['nombre']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Sexo:</th>
                                        <td>
                                            <span class="badge bg-<?php echo ($mascota['sexo_nombre'] ?? '') === 'Macho' ? 'primary' : 'pink'; ?>">
                                                <?php echo htmlspecialchars($mascota['sexo_nombre'] ?? 'No especificado'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Edad:</th>
                                        <td><?php echo ($mascota['edad'] ?? 0) > 0 ? $mascota['edad'] . ' años' : 'No especificada'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Raza:</th>
                                        <td><?php echo htmlspecialchars($mascota['raza_nombre'] ?? 'No especificada'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Tamaño:</th>
                                        <td><?php echo htmlspecialchars($mascota['tamano_nombre'] ?? 'No especificado'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Tipo de pelo:</th>
                                        <td><?php echo htmlspecialchars($mascota['tipo_pelo_nombre'] ?? 'No especificado'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Tiene placa:</th>
                                        <td>
                                            <?php if ($mascota['tiene_placa']): ?>
                                                <span class="badge bg-success">Sí</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if ($mascota['tiene_placa'] && !empty($mascota['nombre_placa'])): ?>
                                        <tr>
                                            <th>Nombre en placa:</th>
                                            <td><?php echo htmlspecialchars($mascota['nombre_placa']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($mascota['tiene_placa'] && !empty($mascota['ruac_placa'])): ?>
                                        <tr>
                                            <th>RUAC:</th>
                                            <td><?php echo htmlspecialchars($mascota['ruac_placa']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th>Registrado:</th>
                                        <td><?php echo date('d/m/Y H:i', strtotime($mascota['fecha_registro'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <?php if (!empty($mascota['senas_particulares'])): ?>
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5><i class="fas fa-clipboard-list"></i> Señas Particulares</h5>
                                </div>
                                <div class="card-body">
                                    <p><?php echo nl2br(htmlspecialchars($mascota['senas_particulares'])); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
