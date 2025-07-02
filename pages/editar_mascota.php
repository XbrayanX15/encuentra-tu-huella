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
$message = '';
$mascota = null;

if ($mascota_id <= 0) {
    header('Location: mis_mascotas.php');
    exit();
}

// Obtener catálogos
try {
    $razas = fetchAll("SELECT * FROM razas ORDER BY nombre");
    $tamanos = fetchAll("SELECT * FROM tamaños ORDER BY id");
    $sexos = fetchAll("SELECT * FROM sexos ORDER BY id");
    $tipos_pelo = fetchAll("SELECT * FROM tipos_pelo ORDER BY nombre");
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
    $senas_particulares = trim($_POST['senas_particulares'] ?? '');
    $tiene_placa = isset($_POST['tiene_placa']) ? true : false;
    $nombre_placa = trim($_POST['nombre_placa'] ?? '') ?: null;
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
        $error = 'Las señas particulares son obligatorias.';
    } else {
        try {
            executeQuery("
                UPDATE mascotas_registradas SET
                    nombre = ?, raza_id = ?, sexo_id = ?, edad = ?, tamaño_id = ?, 
                    tipo_pelo_id = ?, senas_particulares = ?, tiene_placa = ?, 
                    nombre_placa = ?, ruac_placa = ?
                WHERE id = ? AND usuario_id = ?
            ", [
                $nombre, $raza_id, $sexo_id, $edad, $tamaño_id,
                $tipo_pelo_id, $senas_particulares, $tiene_placa ? 't' : 'f',
                $nombre_placa, $ruac_placa, $mascota_id, $user_id
            ]);
            
            $message = 'Mascota actualizada exitosamente.';
            
        } catch (Exception $e) {
            $error = 'Error al actualizar la mascota: ' . $e->getMessage();
        }
    }
}

// Obtener datos de la mascota
try {
    $mascota = fetchOne("
        SELECT * FROM mascotas_registradas 
        WHERE id = ? AND usuario_id = ?
    ", [$mascota_id, $user_id]);
    
    if (!$mascota) {
        header('Location: mis_mascotas.php');
        exit();
    }
    
} catch (Exception $e) {
    $error = 'Error al cargar los datos de la mascota.';
}

$page_title = 'Editar ' . ($mascota['nombre'] ?? 'Mascota');
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-edit"></i> Editar Mascota: <?php echo htmlspecialchars($mascota['nombre'] ?? ''); ?>
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
                                <h5 class="text-primary mb-3">Información Básica</h5>
                                
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre de la mascota *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo htmlspecialchars($mascota['nombre'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="raza_id" class="form-label">Raza *</label>
                                    <select class="form-select" id="raza_id" name="raza_id" required>
                                        <option value="">Seleccionar raza</option>
                                        <?php foreach ($razas as $raza): ?>
                                            <option value="<?php echo $raza['id']; ?>" 
                                                    <?php echo ($mascota['raza_id'] ?? '') == $raza['id'] ? 'selected' : ''; ?>>
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
                                                    <?php echo ($mascota['sexo_id'] ?? '') == $sexo['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sexo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edad" class="form-label">Edad (años)</label>
                                    <input type="number" class="form-control" id="edad" name="edad" 
                                           min="0" max="25" value="<?php echo htmlspecialchars($mascota['edad'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tamano_id" class="form-label">Tamaño *</label>
                                    <select class="form-select" id="tamano_id" name="tamano_id" required>
                                        <option value="">Seleccionar tamaño</option>
                                        <?php foreach ($tamanos as $tamano): ?>
                                            <option value="<?php echo $tamano['id']; ?>"
                                                    <?php echo ($mascota['tamaño_id'] ?? '') == $tamano['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tamano['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Características</h5>
                                
                                <div class="mb-3">
                                    <label for="tipo_pelo_id" class="form-label">Tipo de pelo</label>
                                    <select class="form-select" id="tipo_pelo_id" name="tipo_pelo_id">
                                        <option value="">Seleccionar tipo de pelo</option>
                                        <?php foreach ($tipos_pelo as $tipo): ?>
                                            <option value="<?php echo $tipo['id']; ?>"
                                                    <?php echo ($mascota['tipo_pelo_id'] ?? '') == $tipo['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tipo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="senas_particulares" class="form-label">Señas particulares *</label>
                                    <textarea class="form-control" id="senas_particulares" name="senas_particulares" rows="4" required
                                              placeholder="Describe las características especiales de tu mascota..."><?php echo htmlspecialchars($mascota['senas_particulares'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="tiene_placa" name="tiene_placa"
                                               <?php echo ($mascota['tiene_placa'] ?? false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="tiene_placa">
                                            Tiene placa identificadora
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="nombre_placa" class="form-label">Nombre en la placa</label>
                                    <input type="text" class="form-control" id="nombre_placa" name="nombre_placa" 
                                           value="<?php echo htmlspecialchars($mascota['nombre_placa'] ?? ''); ?>"
                                           placeholder="Nombre que aparece en la placa">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="ruac_placa" class="form-label">RUAC de la placa</label>
                                    <input type="text" class="form-control" id="ruac_placa" name="ruac_placa" 
                                           value="<?php echo htmlspecialchars($mascota['ruac_placa'] ?? ''); ?>"
                                           placeholder="Número de registro RUAC">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="detalle_mascota.php?id=<?php echo $mascota_id; ?>" class="btn btn-secondary me-md-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
