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

// Obtener catálogos para los formularios
try {
    $razas = fetchAll("SELECT * FROM razas ORDER BY nombre");
    $tamanos = fetchAll("SELECT * FROM tamaños ORDER BY id");
    $sexos = fetchAll("SELECT * FROM sexos ORDER BY id");
    $tipos_pelo = fetchAll("SELECT * FROM tipos_pelo ORDER BY nombre");
} catch (Exception $e) {
    $error = 'Error al cargar los catálogos.';
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug temporal - eliminar después de confirmar que funciona
    error_log("=== DEBUG REGISTRO MASCOTA ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("USER_ID: " . $user_id);
    
    $nombre = trim($_POST['nombre'] ?? '');
    $raza_id = (int)($_POST['raza_id'] ?? 0);
    $sexo_id = (int)($_POST['sexo_id'] ?? 0);
    $edad = (int)($_POST['edad_aproximada'] ?? 0);
    $tamaño_id = (int)($_POST['tamano_id'] ?? 0);
    $tipo_pelo_id = (int)($_POST['tipo_pelo_id'] ?? 0);
    $senas_particulares = trim($_POST['descripcion'] ?? '');
    $tiene_placa = isset($_POST['tiene_chip']) ? true : false;
    $nombre_placa = trim($_POST['numero_chip'] ?? '') ?: null;
    $ruac_placa = trim($_POST['ruac_placa'] ?? '') ?: null;
    
    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre de la mascota es obligatorio.';
    } elseif ($sexo_id <= 0) {
        $error = 'El sexo de la mascota es obligatorio.';
    } elseif ($raza_id <= 0) {
        $error = 'La raza es obligatoria.';
    } elseif ($tamaño_id <= 0) {
        $error = 'El tamaño es obligatorio.';
    } elseif (empty($senas_particulares)) {
        $error = 'La descripción es obligatoria.';
    } else {
        try {
            // Insertar mascota usando executeQuery con mejor manejo de errores
            $stmt = executeQuery("
                INSERT INTO mascotas_registradas (
                    usuario_id, nombre, raza_id, sexo_id, edad, tamaño_id, 
                    tipo_pelo_id, senas_particulares, tiene_placa, nombre_placa, 
                    ruac_placa, fecha_registro
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [
                $user_id, $nombre, $raza_id, $sexo_id, $edad, $tamaño_id,
                $tipo_pelo_id, $senas_particulares, $tiene_placa ? 't' : 'f', 
                $nombre_placa, $ruac_placa
            ]);
            
            // Verificar que la inserción fue exitosa
            if (!$stmt || $stmt->rowCount() === 0) {
                // Log más específico del error
                error_log("Error específico en inserción de mascota:");
                error_log("- Usuario ID: " . $user_id);
                error_log("- Nombre: " . $nombre);
                error_log("- Raza ID: " . $raza_id);
                error_log("- Tamaño ID: " . $tamano_id);
                error_log("- stmt es: " . var_export($stmt, true));
                if ($stmt) {
                    error_log("- rowCount: " . $stmt->rowCount());
                }
                throw new Exception('Error al insertar la mascota en la base de datos - No se insertaron filas');
            }
            
            // Obtener el ID de la mascota recién insertada
            $mascota_result = fetchOne("
                SELECT id FROM mascotas_registradas 
                WHERE usuario_id = ? AND nombre = ? 
                ORDER BY fecha_registro DESC 
                LIMIT 1
            ", [$user_id, $nombre]);
            
            if (!$mascota_result) {
                throw new Exception('No se pudo obtener el ID de la mascota insertada');
            }
            
            $mascota_id = $mascota_result['id'];
            
            // Debug temporal
            error_log("Mascota registrada exitosamente con ID: " . $mascota_id);
            
            // Procesar imágenes subidas
            if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
                $upload_dir = '../uploads/mascotas/';
                
                // Crear directorio si no existe
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['fotos']['name'] as $key => $filename) {
                    if (!empty($filename) && $_FILES['fotos']['error'][$key] === 0) {
                        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (in_array($file_extension, $allowed_extensions)) {
                            $new_filename = 'mascota_' . $mascota_id . '_' . time() . '_' . $key . '.' . $file_extension;
                            $upload_path = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES['fotos']['tmp_name'][$key], $upload_path)) {
                                // Guardar en base de datos
                                executeQuery("
                                    INSERT INTO fotos (mascota_registrada_id, ruta_archivo, fecha_subida)
                                    VALUES (?, ?, NOW())
                                ", [$mascota_id, 'uploads/mascotas/' . $new_filename]);
                            }
                        }
                    }
                }
            }
            
            header('Location: mis_mascotas.php?success=1');
            exit();
            
        } catch (Exception $e) {
            $error = 'Error al registrar la mascota: ' . $e->getMessage();
            // Log detallado para debugging
            error_log("=== ERROR DETALLADO REGISTRO MASCOTA ===");
            error_log("Mensaje: " . $e->getMessage());
            error_log("Archivo: " . $e->getFile());
            error_log("Línea: " . $e->getLine());
            error_log("Usuario ID: " . $user_id);
            error_log("Datos POST: " . print_r($_POST, true));
            error_log("=== FIN ERROR DETALLADO ===");
        }
    }
}

$page_title = 'Registrar Mascota';
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-paw"></i> Registrar Nueva Mascota</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
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
                                           value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="raza_id" class="form-label">Raza *</label>
                                    <select class="form-select" id="raza_id" name="raza_id" required>
                                        <option value="">Seleccionar raza</option>
                                        <?php foreach ($razas as $raza): ?>
                                            <option value="<?php echo $raza['id']; ?>" 
                                                    <?php echo ($_POST['raza_id'] ?? '') == $raza['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($raza['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sexo_id" class="form-label">Sexo *</label>
                                    <select class="form-select" id="sexo_id" name="sexo_id" required>
                                        <option value="">Seleccionar sexo</option>
                                        <?php foreach ($sexos as $sexo): ?>
                                            <option value="<?php echo $sexo['id']; ?>" 
                                                    <?php echo ($_POST['sexo_id'] ?? '') == $sexo['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sexo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edad_aproximada" class="form-label">Edad aproximada (años): <br> Dejar en blanco si no se conoce</label>
                                    <input type="number" class="form-control" id="edad_aproximada" name="edad_aproximada" 
                                           min="0" max="25" value="<?php echo htmlspecialchars($_POST['edad_aproximada'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tamano_id" class="form-label">Tamaño *</label>
                                    <select class="form-select" id="tamano_id" name="tamano_id" required>
                                        <option value="">Seleccionar tamaño</option>
                                        <?php foreach ($tamanos as $tamano): ?>
                                            <option value="<?php echo $tamano['id']; ?>"
                                                    <?php echo ($_POST['tamano_id'] ?? '') == $tamano['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tamano['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Características físicas -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Características Físicas</h5>
                                
                                <div class="mb-3">
                                    <label for="tipo_pelo_id" class="form-label">Tipo de pelo</label>
                                    <select class="form-select" id="tipo_pelo_id" name="tipo_pelo_id">
                                        <option value="">Seleccionar tipo de pelo</option>
                                        <?php foreach ($tipos_pelo as $tipo): ?>
                                            <option value="<?php echo $tipo['id']; ?>"
                                                    <?php echo ($_POST['tipo_pelo_id'] ?? '') == $tipo['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tipo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">Señas particulares/Descripción *</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required
                                              placeholder="Describe las características especiales de tu mascota..."><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="tiene_chip" name="tiene_chip"
                                               <?php echo isset($_POST['tiene_chip']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="tiene_chip">
                                            Tiene placa identificadora
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="numero_chip" class="form-label">Nombre en la placa</label>
                                    <input type="text" class="form-control" id="numero_chip" name="numero_chip" 
                                           value="<?php echo htmlspecialchars($_POST['numero_chip'] ?? ''); ?>"
                                           placeholder="Nombre que aparece en la placa">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="ruac_placa" class="form-label">RUAC de la placa (si aplica)</label>
                                    <input type="text" class="form-control" id="ruac_placa" name="ruac_placa" 
                                           value="<?php echo htmlspecialchars($_POST['ruac_placa'] ?? ''); ?>"
                                           placeholder="Número de registro RUAC">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Fotos de la mascota -->
                        <h5 class="text-primary mb-3">Fotos</h5>
                        <div class="mb-3">
                            <label for="fotos" class="form-label">Fotos de la mascota</label>
                            <input type="file" class="form-control" id="fotos" name="fotos[]" 
                                   accept="image/*" multiple>
                            <div class="form-text">Puedes subir múltiples fotos (JPG, PNG, GIF).</div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="mis_mascotas.php" class="btn btn-secondary me-md-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Registrar Mascota
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
