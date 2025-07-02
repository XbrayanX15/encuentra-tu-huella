<?php
// Incluir funciones primero
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
startSession();

$message = '';
$error = '';

// Obtener catálogos usando las funciones existentes
try {
    $razas = getRazas();
    $tamanos = getTamaños();
    $sexos = getSexos();
    $tipos_pelo = getTiposPelo();
    $estados_salud = getEstadosSalud();
    $estados_emocionales = getEstadosEmocionales();
    $municipios = getMunicipios(1); // CDMX
} catch (Exception $e) {
    $error = 'Error al cargar los catálogos.';
    logError("Error en reportar_perdida.php: " . $e->getMessage());
}

// Procesar el formulario ANTES de incluir el header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = cleanInput($_POST['nombre'] ?? '');
    $raza_id = (int)($_POST['raza_id'] ?? 0);
    $sexo_id = (int)($_POST['sexo_id'] ?? 0);
    $edad_aproximada = (int)($_POST['edad_aproximada'] ?? 0);
    $tamano_id = (int)($_POST['tamano_id'] ?? 0);
    $tipo_pelo_id = (int)($_POST['tipo_pelo_id'] ?? 0);
    $color_primario = cleanInput($_POST['color_primario'] ?? '');
    $color_secundario = cleanInput($_POST['color_secundario'] ?? '');
    $descripcion = cleanInput($_POST['descripcion'] ?? '');
    $estado_salud_id = (int)($_POST['estado_salud_id'] ?? 1);
    $estado_emocional_id = (int)($_POST['estado_emocional_id'] ?? 1);
    $municipio_id = (int)($_POST['municipio_id'] ?? 0);
    $direccion_perdida = cleanInput($_POST['direccion_perdida'] ?? '');
    $fecha_perdida = $_POST['fecha_perdida'] ?? '';
    $hora_perdida = $_POST['hora_perdida'] ?? '';
    $circunstancias = cleanInput($_POST['circunstancias'] ?? '');
    $recompensa = cleanInput($_POST['recompensa'] ?? '');
    $telefono_contacto = cleanInput($_POST['telefono_contacto'] ?? '');
    $email_contacto = cleanInput($_POST['email_contacto'] ?? '');
    $tiene_chip = isset($_POST['tiene_chip']) ? 1 : 0;
    $numero_chip = cleanInput($_POST['numero_chip'] ?? '');
    
    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre de la mascota es obligatorio.';
    } elseif ($sexo_id <= 0) {
        $error = 'El sexo de la mascota es obligatorio.';
    } elseif ($raza_id <= 0) {
        $error = 'La raza es obligatoria.';
    } elseif ($tamano_id <= 0) {
        $error = 'El tamaño es obligatorio.';
    } elseif (empty($color_primario)) {
        $error = 'El color primario es obligatorio.';
    } elseif ($municipio_id <= 0) {
        $error = 'El municipio donde se perdió es obligatorio.';
    } elseif (empty($fecha_perdida)) {
        $error = 'La fecha de pérdida es obligatoria.';
    } elseif (empty($telefono_contacto) && empty($email_contacto)) {
        $error = 'Debes proporcionar al menos un teléfono o email de contacto.';
    } else {
        try {
            // Si el usuario está logueado, usar su ID, sino NULL
            $usuario_id = isLoggedIn() ? $_SESSION['user_id'] : null;
            
            // Insertar reporte de pérdida usando solo campos obligatorios y básicos
            $query = "
                INSERT INTO perros_perdidos (
                    usuario_id, nombre, raza_id, sexo_id, edad, tamaño_id, 
                    descripcion, contacto, fecha_hora, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            // Preparar datos básicos
            $contacto_info = [];
            if (!empty($telefono_contacto)) $contacto_info[] = "Tel: $telefono_contacto";
            if (!empty($email_contacto)) $contacto_info[] = "Email: $email_contacto";
            $contacto = implode(', ', $contacto_info);
            
            // Combinar toda la información adicional en la descripción
            $descripcion_completa = $descripcion;
            if (!empty($color_primario)) $descripcion_completa .= "\nColor primario: $color_primario";
            if (!empty($color_secundario)) $descripcion_completa .= "\nColor secundario: $color_secundario";
            if (!empty($circunstancias)) $descripcion_completa .= "\nCircunstancias: $circunstancias";
            if (!empty($recompensa)) $descripcion_completa .= "\nRecompensa: $recompensa";
            if (!empty($direccion_perdida)) $descripcion_completa .= "\nDirección donde se perdió: $direccion_perdida";
            if ($tiene_chip && !empty($numero_chip)) $descripcion_completa .= "\nTiene microchip: $numero_chip";
            
            $fecha_hora_perdida = $fecha_perdida . ' ' . ($hora_perdida ?: '00:00:00');
            
            $params = [
                $usuario_id, $nombre, $raza_id, $sexo_id, $edad_aproximada, $tamano_id,
                $descripcion_completa, $contacto, $fecha_hora_perdida, 'perdido'
            ];
            
            $reporte_id = insertAndGetId($query, $params);
            
            if ($reporte_id) {
                // Debug temporal - agregar info del usuario
                $debug_info = "Usuario: " . ($usuario_id ?: 'Anónimo') . " | ID reporte: $reporte_id";
                $_SESSION['success_message'] = 'Reporte de pérdida registrado exitosamente. ' . $debug_info;
                
                if (isLoggedIn()) {
                    header('Location: mis_reportes.php?debug=1');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Error al registrar el reporte.';
            }
            
        } catch (Exception $e) {
            $error = 'Error al reportar la pérdida: ' . $e->getMessage();
            logError("Error en reportar_perdida.php: " . $e->getMessage());
        }
    }
}

// Ahora incluir el header
$page_title = 'Reportar Mascota Perdida';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Reportar Mascota Perdida
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>¡Tu mascota está perdida!</strong> Completa toda la información posible para ayudar a que otras personas puedan identificarla y contactarte.
                    </div>

                    <?php if ($error): ?>
                        <?php echo showAlert($error, 'danger'); ?>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <!-- Información de la mascota -->
                            <div class="col-md-6">
                                <h5 class="text-danger mb-3">
                                    <i class="bi bi-heart me-1"></i>
                                    Información de la Mascota
                                </h5>
                                
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre de la mascota *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo escape($_POST['nombre'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">
                                        El nombre es obligatorio.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="raza_id" class="form-label">Raza *</label>
                                    <select class="form-select" id="raza_id" name="raza_id" required>
                                        <option value="">Seleccionar raza</option>
                                        <?php foreach ($razas as $raza): ?>
                                            <option value="<?php echo $raza['id']; ?>" 
                                                    <?php echo ($_POST['raza_id'] ?? '') == $raza['id'] ? 'selected' : ''; ?>>
                                                <?php echo escape($raza['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        La raza es obligatoria.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sexo_id" class="form-label">Sexo *</label>
                                    <select class="form-select" id="sexo_id" name="sexo_id" required>
                                        <option value="">Seleccionar sexo</option>
                                        <?php foreach ($sexos as $sexo): ?>
                                            <option value="<?php echo $sexo['id']; ?>" 
                                                    <?php echo ($_POST['sexo_id'] ?? '') == $sexo['id'] ? 'selected' : ''; ?>>
                                                <?php echo escape($sexo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        El sexo es obligatorio.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edad_aproximada" class="form-label">Edad aproximada (años)</label>
                                    <input type="number" class="form-control" id="edad_aproximada" name="edad_aproximada" 
                                           min="0" max="25" value="<?php echo escape($_POST['edad_aproximada'] ?? ''); ?>">
                                    <div class="form-text">Dejar en blanco si se desconoce</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tamano_id" class="form-label">Tamaño *</label>
                                    <select class="form-select" id="tamano_id" name="tamano_id" required>
                                        <option value="">Seleccionar tamaño</option>
                                        <?php foreach ($tamanos as $tamano): ?>
                                            <option value="<?php echo $tamano['id']; ?>" 
                                                    <?php echo ($_POST['tamano_id'] ?? '') == $tamano['id'] ? 'selected' : ''; ?>>
                                                <?php echo escape($tamano['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        El tamaño es obligatorio.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tipo_pelo_id" class="form-label">Tipo de pelo</label>
                                    <select class="form-select" id="tipo_pelo_id" name="tipo_pelo_id">
                                        <option value="">Seleccionar tipo de pelo</option>
                                        <?php foreach ($tipos_pelo as $tipo): ?>
                                            <option value="<?php echo $tipo['id']; ?>" 
                                                    <?php echo ($_POST['tipo_pelo_id'] ?? '') == $tipo['id'] ? 'selected' : ''; ?>>
                                                <?php echo escape($tipo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Descripción física -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">
                                    <i class="bi bi-palette me-1"></i>
                                    Descripción Física
                                </h5>
                                
                                <div class="mb-3">
                                    <label for="color_primario" class="form-label">Color primario *</label>
                                    <input type="text" class="form-control" id="color_primario" name="color_primario" 
                                           value="<?php echo escape($_POST['color_primario'] ?? ''); ?>" 
                                           placeholder="Ej: Negro, Café, Blanco" required>
                                    <div class="invalid-feedback">
                                        El color primario es obligatorio.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="color_secundario" class="form-label">Color secundario</label>
                                    <input type="text" class="form-control" id="color_secundario" name="color_secundario" 
                                           value="<?php echo escape($_POST['color_secundario'] ?? ''); ?>" 
                                           placeholder="Ej: Manchas blancas, rayas">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="estado_salud_id" class="form-label">Estado de salud</label>
                                    <select class="form-select" id="estado_salud_id" name="estado_salud_id">
                                        <?php foreach ($estados_salud as $estado): ?>
                                            <option value="<?php echo $estado['id']; ?>" 
                                                    <?php echo ($_POST['estado_salud_id'] ?? '1') == $estado['id'] ? 'selected' : ''; ?>>
                                                <?php echo escape($estado['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="estado_emocional_id" class="form-label">Estado emocional</label>
                                    <select class="form-select" id="estado_emocional_id" name="estado_emocional_id">
                                        <?php foreach ($estados_emocionales as $estado): ?>
                                            <option value="<?php echo $estado['id']; ?>" 
                                                    <?php echo ($_POST['estado_emocional_id'] ?? '1') == $estado['id'] ? 'selected' : ''; ?>>
                                                <?php echo escape($estado['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">Descripción adicional</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                              placeholder="Características distintivas, marcas, comportamiento, etc."><?php echo escape($_POST['descripcion'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="tiene_chip" name="tiene_chip"
                                           <?php echo isset($_POST['tiene_chip']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="tiene_chip">
                                        Tiene microchip
                                    </label>
                                </div>
                                
                                <div class="mb-3" id="chip_container" style="display: none;">
                                    <label for="numero_chip" class="form-label">Número de microchip</label>
                                    <input type="text" class="form-control" id="numero_chip" name="numero_chip" 
                                           value="<?php echo escape($_POST['numero_chip'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información de la pérdida -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-warning mb-3">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    Información de la Pérdida
                                </h5>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="municipio_id" class="form-label">Municipio donde se perdió *</label>
                                    <select class="form-select" id="municipio_id" name="municipio_id" required>
                                        <option value="">Seleccionar municipio</option>
                                        <?php foreach ($municipios as $municipio): ?>
                                            <option value="<?php echo $municipio['id']; ?>" 
                                                    <?php echo ($_POST['municipio_id'] ?? '') == $municipio['id'] ? 'selected' : ''; ?>>
                                                <?php echo escape($municipio['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        El municipio es obligatorio.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="direccion_perdida" class="form-label">Dirección aproximada</label>
                                    <input type="text" class="form-control" id="direccion_perdida" name="direccion_perdida" 
                                           value="<?php echo escape($_POST['direccion_perdida'] ?? ''); ?>" 
                                           placeholder="Calle, colonia, referencias">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="fecha_perdida" class="form-label">Fecha de pérdida *</label>
                                    <input type="date" class="form-control" id="fecha_perdida" name="fecha_perdida" 
                                           value="<?php echo escape($_POST['fecha_perdida'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">
                                        La fecha de pérdida es obligatoria.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="hora_perdida" class="form-label">Hora aproximada</label>
                                    <input type="time" class="form-control" id="hora_perdida" name="hora_perdida" 
                                           value="<?php echo escape($_POST['hora_perdida'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="circunstancias" class="form-label">Circunstancias de la pérdida</label>
                                    <textarea class="form-control" id="circunstancias" name="circunstancias" rows="3" 
                                              placeholder="¿Cómo se perdió? ¿Qué estaba pasando?"><?php echo escape($_POST['circunstancias'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="recompensa" class="form-label">Recompensa (opcional)</label>
                                    <input type="text" class="form-control" id="recompensa" name="recompensa" 
                                           value="<?php echo escape($_POST['recompensa'] ?? ''); ?>" 
                                           placeholder="Ej: $500, regalo, etc.">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información de contacto -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-success mb-3">
                                    <i class="bi bi-telephone me-1"></i>
                                    Información de Contacto
                                </h5>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telefono_contacto" class="form-label">Teléfono de contacto *</label>
                                    <input type="tel" class="form-control" id="telefono_contacto" name="telefono_contacto" 
                                           value="<?php echo escape($_POST['telefono_contacto'] ?? ''); ?>" 
                                           placeholder="55-1234-5678" required>
                                    <div class="invalid-feedback">
                                        El teléfono de contacto es obligatorio.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email_contacto" class="form-label">Email de contacto</label>
                                    <input type="email" class="form-control" id="email_contacto" name="email_contacto" 
                                           value="<?php echo escape($_POST['email_contacto'] ?? ''); ?>" 
                                           placeholder="tu@email.com">
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Reportar Pérdida
                            </button>
                            <a href="/pages/index.php" class="btn btn-secondary btn-lg ms-2">
                                <i class="bi bi-x-circle me-2"></i>
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/ocultar campo de microchip
    const tieneChipCheckbox = document.getElementById('tiene_chip');
    const chipContainer = document.getElementById('chip_container');
    
    tieneChipCheckbox.addEventListener('change', function() {
        chipContainer.style.display = this.checked ? 'block' : 'none';
    });
    
    // Mostrar al cargar si está marcado
    if (tieneChipCheckbox.checked) {
        chipContainer.style.display = 'block';
    }
    
    // Validación del formulario
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
