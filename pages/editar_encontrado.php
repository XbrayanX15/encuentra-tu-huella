<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$encontrado_id = (int)($_GET['id'] ?? 0);
$error = '';
$message = '';
$encontrado = null;

if ($encontrado_id <= 0) {
    header('Location: mis_reportes.php');
    exit();
}

// Obtener catálogos
try {
    $razas = fetchAll("SELECT * FROM razas ORDER BY nombre");
    $tamanos = fetchAll("SELECT * FROM tamaños ORDER BY id");
    $sexos = fetchAll("SELECT * FROM sexos ORDER BY id");
    $tipos_pelo = fetchAll("SELECT * FROM tipos_pelo ORDER BY nombre");
    $estados_salud = fetchAll("SELECT * FROM estados_salud ORDER BY nombre");
    $estados_emocionales = fetchAll("SELECT * FROM estados_emocionales ORDER BY nombre");
    $municipios = fetchAll("SELECT * FROM municipios ORDER BY nombre");
    $colonias = fetchAll("SELECT * FROM colonias ORDER BY nombre");
} catch (Exception $e) {
    $error = 'Error al cargar los catálogos.';
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $raza_id = (int)($_POST['raza_id'] ?? 0);
    $sexo_id = (int)($_POST['sexo_id'] ?? 0);
    $edad = (int)($_POST['edad'] ?? 0);
    $tamaño_id = (int)($_POST['tamano_id'] ?? 0);
    $tipo_pelo_id = (int)($_POST['tipo_pelo_id'] ?? 0);
    $estado_salud_id = (int)($_POST['estado_salud_id'] ?? 0);
    $estado_emocional_id = (int)($_POST['estado_emocional_id'] ?? 0);
    $senas_particulares = trim($_POST['senas_particulares'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $municipio_id = (int)($_POST['municipio_id'] ?? 0);
    $colonia_id = (int)($_POST['colonia_id'] ?? 0);
    $resguardado = isset($_POST['resguardado']) ? true : false;
    $tiene_placa = isset($_POST['tiene_placa']) ? true : false;
    $nombre_placa = trim($_POST['nombre_placa'] ?? '') ?: null;
    $ruac_placa = trim($_POST['ruac_placa'] ?? '') ?: null;
    
    // Validaciones
    if (empty($descripcion)) {
        $error = 'La descripción es obligatoria.';
    } elseif (empty($contacto)) {
        $error = 'El contacto es obligatorio.';
    } else {
        try {
            executeQuery("
                UPDATE perros_encontrados SET
                    nombre = ?, raza_id = ?, sexo_id = ?, edad = ?, tamaño_id = ?, 
                    tipo_pelo_id = ?, estado_salud_id = ?, estado_emocional_id = ?,
                    senas_particulares = ?, descripcion = ?, contacto = ?, 
                    municipio_id = ?, colonia_id = ?, resguardado = ?, 
                    tiene_placa = ?, nombre_placa = ?, ruac_placa = ?
                WHERE id = ? AND usuario_id = ?
            ", [
                $nombre, $raza_id, $sexo_id, $edad, $tamaño_id,
                $tipo_pelo_id, $estado_salud_id, $estado_emocional_id,
                $senas_particulares, $descripcion, $contacto,
                $municipio_id, $colonia_id, $resguardado ? 't' : 'f',
                $tiene_placa ? 't' : 'f', $nombre_placa, $ruac_placa,
                $encontrado_id, $user_id
            ]);
            
            $message = 'Reporte de mascota encontrada actualizado exitosamente.';
            
        } catch (Exception $e) {
            $error = 'Error al actualizar el reporte: ' . $e->getMessage();
        }
    }
}

// Obtener datos del reporte
try {
    $encontrado = fetchOne("
        SELECT * FROM perros_encontrados 
        WHERE id = ? AND usuario_id = ?
    ", [$encontrado_id, $user_id]);
    
    if (!$encontrado) {
        header('Location: mis_reportes.php');
        exit();
    }
    
} catch (Exception $e) {
    $error = 'Error al cargar los datos del reporte.';
}

$page_title = 'Editar Reporte - ' . ($encontrado['nombre'] ?? 'Mascota Encontrada');
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-edit"></i> Editar Reporte de Mascota Encontrada
                    </h3>
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

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-success mb-3">Información Básica</h5>
                                
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre (si lo conoces)</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo htmlspecialchars($encontrado['nombre'] ?? ''); ?>"
                                           placeholder="Nombre de la mascota (opcional)">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="raza_id" class="form-label">Raza</label>
                                    <select class="form-select" id="raza_id" name="raza_id">
                                        <option value="">No identificada</option>
                                        <?php foreach ($razas as $raza): ?>
                                            <option value="<?php echo $raza['id']; ?>" 
                                                    <?php echo ($encontrado['raza_id'] ?? '') == $raza['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($raza['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sexo_id" class="form-label">Sexo</label>
                                    <select class="form-select" id="sexo_id" name="sexo_id">
                                        <option value="">No identificado</option>
                                        <?php foreach ($sexos as $sexo): ?>
                                            <option value="<?php echo $sexo['id']; ?>" 
                                                    <?php echo ($encontrado['sexo_id'] ?? '') == $sexo['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sexo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edad" class="form-label">Edad aproximada (años)</label>
                                    <input type="number" class="form-control" id="edad" name="edad" 
                                           min="0" max="25" value="<?php echo htmlspecialchars($encontrado['edad'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tamano_id" class="form-label">Tamaño</label>
                                    <select class="form-select" id="tamano_id" name="tamano_id">
                                        <option value="">No identificado</option>
                                        <?php foreach ($tamanos as $tamano): ?>
                                            <option value="<?php echo $tamano['id']; ?>"
                                                    <?php echo ($encontrado['tamaño_id'] ?? '') == $tamano['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tamano['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tipo_pelo_id" class="form-label">Tipo de pelo</label>
                                    <select class="form-select" id="tipo_pelo_id" name="tipo_pelo_id">
                                        <option value="">No identificado</option>
                                        <?php foreach ($tipos_pelo as $tipo): ?>
                                            <option value="<?php echo $tipo['id']; ?>"
                                                    <?php echo ($encontrado['tipo_pelo_id'] ?? '') == $tipo['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tipo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="text-success mb-3">Estado y Ubicación</h5>
                                
                                <div class="mb-3">
                                    <label for="estado_salud_id" class="form-label">Estado de salud</label>
                                    <select class="form-select" id="estado_salud_id" name="estado_salud_id">
                                        <option value="">No evaluado</option>
                                        <?php foreach ($estados_salud as $estado): ?>
                                            <option value="<?php echo $estado['id']; ?>"
                                                    <?php echo ($encontrado['estado_salud_id'] ?? '') == $estado['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($estado['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="estado_emocional_id" class="form-label">Estado emocional</label>
                                    <select class="form-select" id="estado_emocional_id" name="estado_emocional_id">
                                        <option value="">No evaluado</option>
                                        <?php foreach ($estados_emocionales as $estado): ?>
                                            <option value="<?php echo $estado['id']; ?>"
                                                    <?php echo ($encontrado['estado_emocional_id'] ?? '') == $estado['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($estado['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="municipio_id" class="form-label">Municipio donde se encontró</label>
                                    <select class="form-select" id="municipio_id" name="municipio_id">
                                        <option value="">Seleccionar municipio</option>
                                        <?php foreach ($municipios as $municipio): ?>
                                            <option value="<?php echo $municipio['id']; ?>"
                                                    <?php echo ($encontrado['municipio_id'] ?? '') == $municipio['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($municipio['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="colonia_id" class="form-label">Colonia</label>
                                    <select class="form-select" id="colonia_id" name="colonia_id">
                                        <option value="">Seleccionar colonia</option>
                                        <?php foreach ($colonias as $colonia): ?>
                                            <option value="<?php echo $colonia['id']; ?>"
                                                    <?php echo ($encontrado['colonia_id'] ?? '') == $colonia['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($colonia['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="contacto" class="form-label">Información de contacto *</label>
                                    <input type="text" class="form-control" id="contacto" name="contacto" 
                                           value="<?php echo htmlspecialchars($encontrado['contacto'] ?? ''); ?>" required
                                           placeholder="Teléfono, email o dirección">
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="resguardado" name="resguardado"
                                               <?php echo ($encontrado['resguardado'] ?? false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="resguardado">
                                            La mascota está bajo mi cuidado temporal
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="tiene_placa" name="tiene_placa"
                                               <?php echo ($encontrado['tiene_placa'] ?? false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="tiene_placa">
                                            Tiene placa identificadora
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre_placa" class="form-label">Nombre en la placa</label>
                                    <input type="text" class="form-control" id="nombre_placa" name="nombre_placa" 
                                           value="<?php echo htmlspecialchars($encontrado['nombre_placa'] ?? ''); ?>"
                                           placeholder="Nombre que aparece en la placa">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ruac_placa" class="form-label">RUAC de la placa</label>
                                    <input type="text" class="form-control" id="ruac_placa" name="ruac_placa" 
                                           value="<?php echo htmlspecialchars($encontrado['ruac_placa'] ?? ''); ?>"
                                           placeholder="Número de registro RUAC">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="senas_particulares" class="form-label">Señas particulares</label>
                            <textarea class="form-control" id="senas_particulares" name="senas_particulares" rows="3"
                                      placeholder="Características físicas distintivas..."><?php echo htmlspecialchars($encontrado['senas_particulares'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción de cómo/dónde se encontró *</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required
                                      placeholder="Describe las circunstancias del encuentro, el lugar exacto, comportamiento de la mascota, etc."><?php echo htmlspecialchars($encontrado['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="detalle_encontrado.php?id=<?php echo $encontrado_id; ?>" class="btn btn-secondary me-md-2">Cancelar</a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
