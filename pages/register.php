<?php
$page_title = 'Registro';

// Cargar funciones necesarias
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirigir si ya está logueado
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = cleanInput($_POST['nombre'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $telefono = cleanInput($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    
    // Validaciones
    if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Por favor completa todos los campos obligatorios';
    } elseif (!validateEmail($email)) {
        $error = 'Email no válido';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } elseif (!$terms) {
        $error = 'Debes aceptar los términos y condiciones';
    } else {
        // Verificar si el email ya existe
        $existing_user = fetchOne("SELECT id FROM usuarios WHERE email = ?", [$email]);
        if ($existing_user) {
            $error = 'Ya existe una cuenta con este email';
        } else {
            // Crear nuevo usuario
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO usuarios (nombre, email, password, telefono, fecha_registro, activo) 
                      VALUES (?, ?, ?, ?, NOW(), true)";
            
            try {
                $user_id = executeQuery($query, [$nombre, $email, $hashed_password, $telefono]);
                
                if ($user_id) {
                    // No iniciar sesión automáticamente, redirigir al login
                    $_SESSION['success_message'] = '¡Registro exitoso! Ahora puedes iniciar sesión.';
                    header('Location: login.php');
                    exit;
                } else {
                    $error = 'Error al crear la cuenta. Inténtalo de nuevo.';
                }
            } catch (Exception $e) {
                $error = 'Error al crear la cuenta. Inténtalo de nuevo.';
                logError("Error creando usuario: " . $e->getMessage());
            }
        }
    }
}

// Incluir header después del procesamiento
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-plus text-primary" style="font-size: 3rem;"></i>
                        <h3 class="mt-3">Crear Cuenta</h3>
                        <p class="text-muted">Únete a nuestra comunidad</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="registerForm">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre" 
                                       name="nombre" 
                                       required 
                                       value="<?php echo escape($_POST['nombre'] ?? ''); ?>"
                                       placeholder="Tu nombre completo">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       required 
                                       value="<?php echo escape($_POST['email'] ?? ''); ?>"
                                       placeholder="tu@email.com">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" 
                                       class="form-control" 
                                       id="telefono" 
                                       name="telefono" 
                                       value="<?php echo escape($_POST['telefono'] ?? ''); ?>"
                                       placeholder="55-1234-5678">
                            </div>
                            <div class="form-text">Opcional - Para contactarte en caso de emergencias</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       required 
                                       minlength="6"
                                       placeholder="Mínimo 6 caracteres">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Contraseña *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       required 
                                       minlength="6"
                                       placeholder="Repite tu contraseña">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       value="1" 
                                       id="terms" 
                                       name="terms" 
                                       required>
                                <label class="form-check-label" for="terms">
                                    Acepto los <a href="/pages/terms.php" target="_blank">términos y condiciones</a> 
                                    y la <a href="/pages/privacy.php" target="_blank">política de privacidad</a> *
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="bi bi-person-plus me-2"></i>Crear Cuenta
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="mb-0">¿Ya tienes cuenta? 
                            <a href="/pages/login.php" class="text-decoration-none">Inicia sesión aquí</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPassword = document.getElementById('confirm_password');
    
    togglePassword.addEventListener('click', function() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });
    
    toggleConfirmPassword.addEventListener('click', function() {
        const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPassword.setAttribute('type', type);
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });
    
    // Validación de contraseñas
    const form = document.getElementById('registerForm');
    form.addEventListener('submit', function(e) {
        if (password.value !== confirmPassword.value) {
            e.preventDefault();
            alert('Las contraseñas no coinciden');
            confirmPassword.focus();
        }
    });
    
    // Validación en tiempo real
    confirmPassword.addEventListener('input', function() {
        if (this.value !== password.value) {
            this.setCustomValidity('Las contraseñas no coinciden');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
