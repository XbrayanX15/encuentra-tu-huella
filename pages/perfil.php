<?php
$page_title = 'Mi Perfil';
require_once __DIR__ . '/../includes/header.php';

// Verificar que el usuario esté logueado
requireLogin();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Procesar actualización del perfil ANTES del HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = cleanInput($_POST['nombre'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $telefono = cleanInput($_POST['telefono'] ?? '');
    $direccion = cleanInput($_POST['direccion'] ?? '');
    $password_actual = $_POST['password_actual'] ?? '';
    $nuevo_password = $_POST['nuevo_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    
    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre es obligatorio.';
    } elseif (empty($email) || !validateEmail($email)) {
        $error = 'El email es obligatorio y debe ser válido.';
    } else {
        try {
            // Verificar si el email ya existe (para otro usuario)
            $existing_user = fetchOne("SELECT id FROM usuarios WHERE email = ? AND id != ?", [$email, $user_id]);
            if ($existing_user) {
                $error = 'Este email ya está registrado por otro usuario.';
            } else {
                // Si se quiere cambiar la contraseña
                if (!empty($nuevo_password)) {
                    if (empty($password_actual)) {
                        $error = 'Debes proporcionar tu contraseña actual para cambiarla.';
                    } elseif (strlen($nuevo_password) < 6) {
                        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
                    } elseif ($nuevo_password !== $confirmar_password) {
                        $error = 'Las contraseñas no coinciden.';
                    } else {
                        // Verificar contraseña actual
                        $user = fetchOne("SELECT password FROM usuarios WHERE id = ?", [$user_id]);
                        
                        if (!verifyPassword($password_actual, $user['password'])) {
                            $error = 'La contraseña actual es incorrecta.';
                        }
                    }
                }
                
                if (!$error) {
                    // Actualizar perfil
                    if (!empty($nuevo_password)) {
                        // Con cambio de contraseña
                        $hashed_password = hashPassword($nuevo_password);
                        $query = "UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, direccion = ?, password = ? WHERE id = ?";
                        executeQuery($query, [$nombre, $email, $telefono, $direccion, $hashed_password, $user_id]);
                    } else {
                        // Sin cambio de contraseña
                        $query = "UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, direccion = ? WHERE id = ?";
                        executeQuery($query, [$nombre, $email, $telefono, $direccion, $user_id]);
                    }
                    
                    // Actualizar sesión
                    $_SESSION['user_name'] = $nombre;
                    $_SESSION['user_email'] = $email;
                    
                    $_SESSION['success_message'] = 'Perfil actualizado exitosamente.';
                    header('Location: /pages/perfil.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            $error = 'Error al actualizar el perfil: ' . $e->getMessage();
            logError("Error en perfil.php: " . $e->getMessage());
        }
    }
}

// Obtener datos del usuario
$usuario = getCurrentUser();
if (!$usuario) {
    header('Location: /pages/login.php');
    exit;
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-person me-2"></i>
                        Mi Perfil
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <?php echo showAlert($error, 'danger'); ?>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Foto de perfil -->
                            <div class="col-12 text-center mb-4">
                                <div class="profile-photo-container">
                                    <img src="<?php echo $usuario['foto_perfil'] ? '../uploads/profiles/' . escape($usuario['foto_perfil']) : '../img/default-avatar.png'; ?>" 
                                         alt="Foto de perfil" 
                                         class="profile-photo rounded-circle mb-3" 
                                         style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #3182ce;">
                                    <div>
                                        <label for="foto_perfil" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-camera me-1"></i>
                                            Cambiar Foto
                                        </label>
                                        <input type="file" id="foto_perfil" name="foto_perfil" 
                                               accept="image/*" style="display: none;" 
                                               onchange="previewProfileImage(this)">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Información Personal</h5>
                                
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre completo *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo escape($usuario['nombre'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo escape($usuario['email'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" 
                                           value="<?php echo escape($usuario['telefono'] ?? ''); ?>"
                                           placeholder="55-1234-5678">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="direccion" class="form-label">Dirección</label>
                                    <textarea class="form-control" id="direccion" name="direccion" rows="3"
                                              placeholder="Tu dirección completa"><?php echo escape($usuario['direccion'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Cambiar Contraseña</h5>
                                <p class="text-muted small">Deja estos campos vacíos si no quieres cambiar tu contraseña.</p>
                                
                                <div class="mb-3">
                                    <label for="password_actual" class="form-label">Contraseña actual</label>
                                    <input type="password" class="form-control" id="password_actual" name="password_actual">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="nuevo_password" class="form-label">Nueva contraseña</label>
                                    <input type="password" class="form-control" id="nuevo_password" name="nuevo_password"
                                           minlength="6">
                                    <div class="form-text">Mínimo 6 caracteres.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirmar_password" class="form-label">Confirmar nueva contraseña</label>
                                    <input type="password" class="form-control" id="confirmar_password" name="confirmar_password">
                                </div>
                                
                                <div class="alert alert-info">
                                    <h6>
                                        <i class="bi bi-info-circle me-1"></i>
                                        Información de la cuenta
                                    </h6>
                                    <small>
                                        <strong>Fecha de registro:</strong> <?php echo formatDate($usuario['fecha_registro'], 'd/m/Y'); ?><br>
                                        <strong>Email verificado:</strong> <?php echo $usuario['activo'] ? 'Sí' : 'No'; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="/pages/dashboard.php" class="btn btn-secondary me-md-2">
                                <i class="bi bi-arrow-left me-1"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Estadísticas de actividad -->
            <div class="card shadow mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-1"></i>
                        Mi Actividad
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        // Obtener estadísticas del usuario usando las funciones correctas
                        $mascotas_registradas = fetchOne("SELECT COUNT(*) as total FROM mascotas_registradas WHERE usuario_id = ? AND activo = true", [$user_id])['total'] ?? 0;
                        $reportes_perdidos = fetchOne("SELECT COUNT(*) as total FROM perros_perdidos WHERE usuario_id = ?", [$user_id])['total'] ?? 0;
                        $reportes_encontrados = fetchOne("SELECT COUNT(*) as total FROM perros_encontrados WHERE usuario_id = ?", [$user_id])['total'] ?? 0;
                        $mascotas_recuperadas = fetchOne("SELECT COUNT(*) as total FROM perros_perdidos WHERE usuario_id = ? AND estado = 'encontrado'", [$user_id])['total'] ?? 0;
                        $mascotas_reunidas = fetchOne("SELECT COUNT(*) as total FROM perros_encontrados WHERE usuario_id = ? AND estado = 'entregado'", [$user_id])['total'] ?? 0;
                    } catch (Exception $e) {
                        $mascotas_registradas = $reportes_perdidos = $reportes_encontrados = $mascotas_recuperadas = $mascotas_reunidas = 0;
                        logError("Error obteniendo estadísticas del usuario en perfil.php: " . $e->getMessage());
                    }
                    ?>
                    
                    <div class="row text-center">
                        <div class="col-md-2 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h3 class="text-primary"><?php echo $mascotas_registradas; ?></h3>
                                    <small>Mascotas Registradas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h3 class="text-danger"><?php echo $reportes_perdidos; ?></h3>
                                    <small>Reportes de Pérdida</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h3 class="text-success"><?php echo $reportes_encontrados; ?></h3>
                                    <small>Avistamientos Reportados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h3 class="text-warning"><?php echo $mascotas_recuperadas; ?></h3>
                                    <small>Mascotas Recuperadas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h3 class="text-info"><?php echo $mascotas_reunidas; ?></h3>
                                    <small>Reuniones Facilitadas</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validar que las contraseñas coincidan
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('nuevo_password');
    const confirmPassword = document.getElementById('confirmar_password');
    
    function validatePasswords() {
        if (newPassword.value && confirmPassword.value && newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Las contraseñas no coinciden');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('input', validatePasswords);
    confirmPassword.addEventListener('input', validatePasswords);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
