<?php
// =====================================================
// Pet Finder CDMX - API de Autenticación
// =====================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
setSecurityHeaders();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            handleLogin();
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        case 'check-session':
            handleCheckSession();
            break;
            
        case 'check-email':
            handleCheckEmail();
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $email = cleanInput($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        throw new Exception('Email y contraseña son requeridos');
    }
    
    $user = fetchOne("SELECT * FROM usuarios WHERE email = ? AND activo = true", [$email]);
    
    if (!$user || !verifyPassword($password, $user['password'])) {
        logError("Intento de login fallido para email: $email");
        throw new Exception('Credenciales incorrectas');
    }
    
    // Iniciar sesión
    startSession();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['nombre'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['last_activity'] = time();
    
    logActivity($user['id'], 'login', 'Login via API');
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'email' => $user['email']
        ]
    ]);
}

function handleLogout() {
    startSession();
    
    if (isLoggedIn()) {
        logActivity($_SESSION['user_id'], 'logout', 'Logout via API');
        session_unset();
        session_destroy();
    }
    
    // Si es una petición AJAX, devolver JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => true]);
    } else {
        // Si es una petición normal, redirigir
        header('Location: ../pages/index.php');
        exit;
    }
}

function handleCheckSession() {
    startSession();
    checkSessionTimeout();
    
    if (isLoggedIn()) {
        $user = getCurrentUser();
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'logged_in' => false
        ]);
    }
}

function handleCheckEmail() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $email = cleanInput($input['email'] ?? '');
    
    if (empty($email) || !validateEmail($email)) {
        throw new Exception('Email no válido');
    }
    
    $exists = fetchOne("SELECT id FROM usuarios WHERE email = ?", [$email]);
    
    echo json_encode([
        'success' => true,
        'exists' => (bool)$exists
    ]);
}
?>
