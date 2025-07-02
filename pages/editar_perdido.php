<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: mis_reportes.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Verificar que el reporte pertenece al usuario logueado
try {
    $perdido = fetchOne("
        SELECT pp.*, r.nombre as raza_nombre, s.nombre as sexo_nombre
        FROM perros_perdidos pp
        LEFT JOIN razas r ON pp.raza_id = r.id
        LEFT JOIN sexos s ON pp.sexo_id = s.id
        WHERE pp.id = ? AND pp.usuario_id = ?
    ", [$id, $user_id]);
    
    if (!$perdido) {
        header('Location: mis_reportes.php');
        exit();
    }
} catch (Exception $e) {
    header('Location: mis_reportes.php');
    exit();
}

// Obtener catálogos para los formularios
try {
    $razas = fetchAll("SELECT * FROM razas ORDER BY nombre");
    $tamanos = fetchAll("SELECT * FROM tamaños ORDER BY id");
    $tipos_pelo = fetchAll("SELECT * FROM tipos_pelo ORDER BY nombre");
    $estados_salud = fetchAll("SELECT * FROM estados_salud ORDER BY nombre");
    $estados_emocionales = fetchAll("SELECT * FROM estados_emocionales ORDER BY nombre");
    $sexos = fetchAll("SELECT * FROM sexos ORDER BY id");
    $municipios = fetchAll("SELECT * FROM municipios ORDER BY nombre");
} catch (Exception $e) {
    $error = 'Error al cargar los catálogos.';
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $raza_id = (int)($_POST['raza_id'] ?? 0);
    $sexo_id = (int)($_POST['sexo_id'] ?? 0);
    $edad = (int)($_POST['edad'] ?? 0);
    $tamano_id = (int)($_POST['tamano_id'] ?? 0);
    $tipo_pelo_id = (int)($_POST['tipo_pelo_id'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $senas_particulares = trim($_POST['senas_particulares'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $municipio_id = (int)($_POST['municipio_id'] ?? 0);
    $fecha_hora = $_POST['fecha_hora'] ?? '';
    
    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre de la mascota es obligatorio.';
    } elseif ($raza_id <= 0) {
        $error = 'La raza es obligatoria.';
    } elseif (empty($contacto)) {
        $error = 'La información de contacto es obligatoria.';
    } elseif (empty($fecha_hora)) {
        $error = 'La fecha y hora de pérdida son obligatorias.';
    } else {
        try {
            // Actualizar el reporte
            executeQuery("
                UPDATE perros_perdidos SET 
                    nombre = ?, raza_id = ?, sexo_id = ?, edad = ?, tamaño_id = ?, 
                    tipo_pelo_id = ?, descripcion = ?, senas_particulares = ?, 
                    contacto = ?, municipio_id = ?, fecha_hora = ?
                WHERE id = ? AND usuario_id = ?
            ", [
                $nombre, $raza_id, $sexo_id, $edad, $tamano_id,
                $tipo_pelo_id, $descripcion, $senas_particulares,
                $contacto, $municipio_id, $fecha_hora, $id, $user_id
            ]);
            
            // Procesar nuevas imágenes si se subieron
            if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
                $upload_dir = '../uploads/perdidos/';
                
                // Crear directorio si no existe
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['fotos']['name'] as $key => $filename) {
                    if (!empty($filename) && $_FILES['fotos']['error'][$key] === 0) {
                        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (in_array($file_extension, $allowed_extensions)) {
                            $new_filename = 'perdido_' . $id . '_' . time() . '_' . $key . '.' . $file_extension;
                            $upload_path = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES['fotos']['tmp_name'][$key], $upload_path)) {
                                // Guardar en base de datos
                                executeQuery("
                                    INSERT INTO fotos (perro_perdido_id, ruta_archivo, fecha_subida)
                                    VALUES (?, ?, NOW())
                                ", [$id, 'uploads/perdidos/' . $new_filename]);
                            }
                        }
                    }
                }
            }
            
            $message = 'Reporte actualizado exitosamente.';
            
            // Recargar datos actualizados
            $perdido = fetchOne("
                SELECT pp.*, r.nombre as raza_nombre, s.nombre as sexo_nombre
                FROM perros_perdidos pp
                LEFT JOIN razas r ON pp.raza_id = r.id
                LEFT JOIN sexos s ON pp.sexo_id = s.id
                WHERE pp.id = ? AND pp.usuario_id = ?
            ", [$id, $user_id]);
            
        } catch (Exception $e) {
            $error = 'Error al actualizar el reporte: ' . $e->getMessage();
        }
    }
}

$page_title = 'Editar Reporte: ' . ($perdido['nombre'] ?: 'Sin nombre');
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h3 class="mb-0"><i class="fas fa-edit"></i> Editar Reporte de Mascota Perdida</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Información básica -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Información Básica</h5>
                                
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre de la mascota *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo htmlspecialchars($perdido['nombre'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="raza_id" class="form-label">Raza *</label>
                                    <select class="form-select" id="raza_id" name="raza_id" required>
                                        <option value="">Seleccionar raza</option>
                                        <?php foreach ($razas as $raza): ?>
                                            <option value="<?php echo $raza['id']; ?>" 
                                                    <?php echo ($perdido['raza_id'] ?? '') == $raza['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($raza['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sexo_id" class="form-label">Sexo</label>
                                    <select class="form-select" id="sexo_id" name="sexo_id">
                                        <option value="">Seleccionar sexo</option>
                                        <?php foreach ($sexos as $sexo): ?>
                                            <option value="<?php echo $sexo['id']; ?>" 
                                                    <?php echo ($perdido['sexo_id'] ?? '') == $sexo['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sexo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edad" class="form-label">Edad aproximada (años)</label>
                                    <input type="number" class="form-control" id="edad" name="edad" 
                                           min="0" max="25" value="<?php echo htmlspecialchars($perdido['edad'] ?? ''); ?>">
                                    <div class="form-text">Dejar en blanco si no se conoce</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tamano_id" class="form-label">Tamaño</label>
                                    <select class="form-select" id="tamano_id" name="tamano_id">
                                        <option value="">Seleccionar tamaño</option>
                                        <?php foreach ($tamanos as $tamano): ?>
                                            <option value="<?php echo $tamano['id']; ?>"
                                                    <?php echo ($perdido['tamaño_id'] ?? '') == $tamano['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tamano['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tipo_pelo_id" class="form-label">Tipo de pelo</label>
                                    <select class="form-select" id="tipo_pelo_id" name="tipo_pelo_id">
                                        <option value="">Seleccionar tipo de pelo</option>
                                        <?php foreach ($tipos_pelo as $tipo): ?>
                                            <option value="<?php echo $tipo['id']; ?>"
                                                    <?php echo ($perdido['tipo_pelo_id'] ?? '') == $tipo['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tipo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Ubicación y fecha -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Ubicación y Fecha</h5>
                                
                                <div class="mb-3">
                                    <label for="municipio_id" class="form-label">Municipio donde se perdió</label>
                                    <select class="form-select" id="municipio_id" name="municipio_id">
                                        <option value="">Seleccionar municipio</option>
                                        <?php foreach ($municipios as $municipio): ?>
                                            <option value="<?php echo $municipio['id']; ?>"
                                                    <?php echo ($perdido['municipio_id'] ?? '') == $municipio['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($municipio['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="fecha_hora" class="form-label">Fecha y hora de pérdida *</label>
                                    <input type="datetime-local" class="form-control" id="fecha_hora" name="fecha_hora" 
                                           value="<?php echo isset($perdido['fecha_hora']) ? date('Y-m-d\TH:i', strtotime($perdido['fecha_hora'])) : ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="contacto" class="form-label">Información de contacto *</label>
                                    <textarea class="form-control" id="contacto" name="contacto" rows="3" required
                                              placeholder="Teléfono, email, etc."><?php echo htmlspecialchars($perdido['contacto'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="fotos" class="form-label">Agregar nuevas fotos</label>
                                    <input type="file" class="form-control" id="fotos" name="fotos[]" 
                                           accept="image/*" multiple>
                                    <div class="form-text">Puedes agregar más fotos (JPG, PNG, GIF).</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Descripción -->
                        <h5 class="text-primary mb-3">Descripción y Señas Particulares</h5>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción general</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                      placeholder="Describe a tu mascota..."><?php echo htmlspecialchars($perdido['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="senas_particulares" class="form-label">Señas particulares</label>
                            <textarea class="form-control" id="senas_particulares" name="senas_particulares" rows="3"
                                      placeholder="Cicatrices, marcas, comportamiento especial, etc."><?php echo htmlspecialchars($perdido['senas_particulares'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="mis_reportes.php" class="btn btn-secondary me-md-2">Cancelar</a>
                            <a href="detalle_perdido.php?id=<?php echo $id; ?>" class="btn btn-info me-md-2">Ver Detalle</a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Actualizar Reporte
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
