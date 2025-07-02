<?php
// Incluir funciones primero
require_once __DIR__ . '/../includes/functions.php';
startSession();

// Redirigir si ya está logueado
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Procesar formulario ANTES de incluir el header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor completa todos los campos';
    } elseif (!validateEmail($email)) {
        $error = 'Email no válido';
    } else {
        // Buscar usuario
        $user = fetchOne("SELECT * FROM usuarios WHERE email = ? AND activo = true", [$email]);
        
        if ($user && verifyPassword($password, $user['password'])) {
            // Iniciar sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['last_activity'] = time();
            
            // Configurar cookie si se seleccionó "recordar"
            if ($remember) {
                setcookie('remember_user', $user['id'], time() + (30 * 24 * 60 * 60), '/'); // 30 días
            }
            
            logActivity($user['id'], 'login', 'Inicio de sesión exitoso');
            
            // Redirigir
            $redirect = $_GET['redirect'] ?? 'dashboard.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Email o contraseña incorrectos';
            logError("Intento de login fallido para email: $email");
        }
    }
}

// Ahora incluir el header
$page_title = 'Iniciar Sesión';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 rounded-lg mt-5">
                <div class="card-header">
                    <h3 class="text-center font-weight-light my-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Iniciar Sesión
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <?php echo showAlert($error, 'danger'); ?>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="form-floating mb-3">
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   placeholder="name@example.com"
                                   value="<?php echo escape($_POST['email'] ?? ''); ?>"
                                   required>
                            <label for="email">
                                <i class="bi bi-envelope me-1"></i>
                                Correo Electrónico
                            </label>
                            <div class="invalid-feedback">
                                Por favor ingresa un email válido.
                            </div>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Password"
                                   required>
                            <label for="password">
                                <i class="bi bi-lock me-1"></i>
                                Contraseña
                            </label>
                            <div class="invalid-feedback">
                                La contraseña es requerida.
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="remember" 
                                   name="remember"
                                   <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="remember">
                                Recordar mi sesión
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Iniciar Sesión
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center py-3">
                    <div class="small">
                        <a href="/pages/forgot-password.php" class="text-decoration-none">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                    <div class="small mt-2">
                        ¿No tienes cuenta? 
                        <a href="/pages/register.php" class="text-decoration-none">
                            Regístrate aquí
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Demo accounts info -->
            
        </div>
    </div>
</div>

<script>
// Mostrar/ocultar contraseña
document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    const toggleButton = document.createElement('button');
    toggleButton.type = 'button';
    toggleButton.className = 'btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2';
    toggleButton.style.border = 'none';
    toggleButton.style.background = 'none';
    toggleButton.innerHTML = '<i class="bi bi-eye"></i>';
    
    passwordField.parentNode.style.position = 'relative';
    passwordField.parentNode.appendChild(toggleButton);
    
    toggleButton.addEventListener('click', function() {
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });
    
    // Auto-focus en el primer campo
    document.getElementById('email').focus();
    
    // Validación en tiempo real
    const form = document.querySelector('form');
    const emailField = document.getElementById('email');
    
    emailField.addEventListener('blur', function() {
        if (this.value && !this.value.includes('@')) {
            this.setCustomValidity('Por favor ingresa un email válido');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Prevenir doble envío
    form.addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Iniciando sesión...';
        
        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión';
        }, 3000);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
