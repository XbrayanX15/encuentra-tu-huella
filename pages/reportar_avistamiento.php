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
    logError("Error en reportar_avistamiento.php: " . $e->getMessage());
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
    $direccion_encontrada = cleanInput($_POST['direccion_encontrada'] ?? '');
    $fecha_encontrada = $_POST['fecha_encontrada'] ?? '';
    $hora_encontrada = $_POST['hora_encontrada'] ?? '';
    $circunstancias = cleanInput($_POST['circunstancias'] ?? '');
    $telefono_contacto = cleanInput($_POST['telefono_contacto'] ?? '');
    $email_contacto = cleanInput($_POST['email_contacto'] ?? '');
    $tiene_chip = isset($_POST['tiene_chip']) ? 1 : 0;
    $numero_chip = cleanInput($_POST['numero_chip'] ?? '');
    $esta_resguardada = isset($_POST['esta_resguardada']) ? 1 : 0;
    $lugar_resguardo = cleanInput($_POST['lugar_resguardo'] ?? '');
    
    // Validaciones
    if (empty($nombre)) {
        $nombre = 'Sin nombre'; // Permitir reportes sin nombre
    }
    
    if ($raza_id <= 0) {
        $error = 'La raza es obligatoria.';
    } elseif ($tamano_id <= 0) {
        $error = 'El tamaño es obligatorio.';
    } elseif (empty($color_primario)) {
        $error = 'El color primario es obligatorio.';
    } elseif ($municipio_id <= 0) {
        $error = 'El municipio donde se encontró es obligatorio.';
    } elseif (empty($fecha_encontrada)) {
        $error = 'La fecha de avistamiento es obligatoria.';
    } elseif (empty($telefono_contacto) && empty($email_contacto)) {
        $error = 'Debes proporcionar al menos un teléfono o email de contacto.';
    } else {
        try {
            // Si el usuario está logueado, usar su ID, sino NULL
            $usuario_id = isLoggedIn() ? $_SESSION['user_id'] : null;
            
            // Insertar reporte de encontrado/avistamiento usando campos básicos
            $query = "
                INSERT INTO perros_encontrados (
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
            if (!empty($direccion_encontrada)) $descripcion_completa .= "\nDirección donde se encontró: $direccion_encontrada";
            if ($tiene_chip && !empty($numero_chip)) $descripcion_completa .= "\nTiene microchip: $numero_chip";
            if ($esta_resguardada && !empty($lugar_resguardo)) $descripcion_completa .= "\nResguardado en: $lugar_resguardo";
            
            $fecha_hora_encontrada = $fecha_encontrada . ' ' . ($hora_encontrada ?: '00:00:00');
            
            $params = [
                $usuario_id, $nombre, $raza_id, $sexo_id, $edad_aproximada, $tamano_id,
                $descripcion_completa, $contacto, $fecha_hora_encontrada, 'disponible'
            ];
            
            $reporte_id = insertAndGetId($query, $params);
            
            if ($reporte_id) {
                // Debug temporal - agregar info del usuario
                $debug_info = "Usuario: " . ($usuario_id ?: 'Anónimo') . " | ID reporte: $reporte_id";
                $_SESSION['success_message'] = 'Reporte de avistamiento registrado exitosamente. ' . $debug_info;
                
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
            $error = 'Error al reportar el avistamiento: ' . $e->getMessage();
            logError("Error en reportar_avistamiento.php: " . $e->getMessage());
        }
    }
}

// Ahora incluir el header
$page_title = 'Reportar Mascota Encontrada';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-eye me-2"></i>
                        Reportar Mascota Encontrada / Avistamiento
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="bi bi-heart me-2"></i>
                        <strong>¡Gracias por ayudar!</strong> Tu reporte puede ayudar a reunir a una familia con su mascota perdida.
                    </div>

                    <?php if ($error): ?>
                        <?php echo showAlert($error, 'danger'); ?>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <!-- Información de la mascota -->
                            <div class="col-md-6">
                                <h5 class="text-success mb-3">
                                    <i class="bi bi-heart me-1"></i>
                                    Información de la Mascota
                                </h5>
                                
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre (si lo conoces)</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo escape($_POST['nombre'] ?? ''); ?>" 
                                           placeholder="Déjalo vacío si no sabes el nombre">
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
                                    <label for="sexo_id" class="form-label">Sexo</label>
                                    <select class="form-select" id="sexo_id" name="sexo_id">
                                        <option value="">No estoy seguro</option>
                                        <?php foreach ($sexos as $sexo): ?>
                                            <option value="<?php echo $sexo['id']; ?>" 
                                                    <?php echo ($_POST['sexo_id'] ?? '') == $sexo['id'] ? 'selected' : ''; ?>>
                                                <?php echo escape($sexo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edad_aproximada" class="form-label">Edad aproximada (años)</label>
                                    <input type="number" class="form-control" id="edad_aproximada" name="edad_aproximada" 
                                           min="0" max="25" value="<?php echo escape($_POST['edad_aproximada'] ?? ''); ?>"
                                           placeholder="Si no sabes, déjalo vacío">
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
                                    <label for="estado_salud_id" class="form-label">Estado de salud aparente</label>
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
                                    <label for="estado_emocional_id" class="form-label">Estado emocional aparente</label>
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
                                              placeholder="Características distintivas, comportamiento, collar, etc."><?php echo escape($_POST['descripcion'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="tiene_chip" name="tiene_chip"
                                           <?php echo isset($_POST['tiene_chip']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="tiene_chip">
                                        Tiene collar con placa o microchip visible
                                    </label>
                                </div>
                                
                                <div class="mb-3" id="chip_container" style="display: none;">
                                    <label for="numero_chip" class="form-label">Información del collar/chip</label>
                                    <input type="text" class="form-control" id="numero_chip" name="numero_chip" 
                                           value="<?php echo escape($_POST['numero_chip'] ?? ''); ?>"
                                           placeholder="Número, texto o información visible">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información del avistamiento -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-warning mb-3">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    Información del Avistamiento
                                </h5>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="municipio_id" class="form-label">Municipio donde la viste *</label>
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
                                    <label for="direccion_encontrada" class="form-label">Dirección aproximada</label>
                                    <input type="text" class="form-control" id="direccion_encontrada" name="direccion_encontrada" 
                                           value="<?php echo escape($_POST['direccion_encontrada'] ?? ''); ?>" 
                                           placeholder="Calle, colonia, referencias">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="fecha_encontrada" class="form-label">Fecha del avistamiento *</label>
                                    <input type="date" class="form-control" id="fecha_encontrada" name="fecha_encontrada" 
                                           value="<?php echo escape($_POST['fecha_encontrada'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">
                                        La fecha del avistamiento es obligatoria.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="hora_encontrada" class="form-label">Hora aproximada</label>
                                    <input type="time" class="form-control" id="hora_encontrada" name="hora_encontrada" 
                                           value="<?php echo escape($_POST['hora_encontrada'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="circunstancias" class="form-label">Circunstancias del avistamiento</label>
                                    <textarea class="form-control" id="circunstancias" name="circunstancias" rows="3" 
                                              placeholder="¿Dónde la viste? ¿Estaba sola? ¿Cómo se comportaba?"><?php echo escape($_POST['circunstancias'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="esta_resguardada" name="esta_resguardada"
                                           <?php echo isset($_POST['esta_resguardada']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="esta_resguardada">
                                        La tengo resguardada en lugar seguro
                                    </label>
                                </div>
                                
                                <div class="mb-3" id="resguardo_container" style="display: none;">
                                    <label for="lugar_resguardo" class="form-label">¿Dónde está resguardada?</label>
                                    <input type="text" class="form-control" id="lugar_resguardo" name="lugar_resguardo" 
                                           value="<?php echo escape($_POST['lugar_resguardo'] ?? ''); ?>"
                                           placeholder="Mi casa, veterinaria, refugio, etc.">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información de contacto -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-info mb-3">
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
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-eye me-2"></i>
                                Reportar Avistamiento
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
    
    // Mostrar/ocultar campo de resguardo
    const estaResguardadaCheckbox = document.getElementById('esta_resguardada');
    const resguardoContainer = document.getElementById('resguardo_container');
    
    estaResguardadaCheckbox.addEventListener('change', function() {
        resguardoContainer.style.display = this.checked ? 'block' : 'none';
    });
    
    // Mostrar al cargar si está marcado
    if (estaResguardadaCheckbox.checked) {
        resguardoContainer.style.display = 'block';
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
