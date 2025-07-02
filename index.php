<?php
// Punto de entrada principal para Railway
// Redirige automáticamente a la carpeta pages

if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === '/') {
    header('Location: /pages/');
    exit;
}

// Para otras rutas, servir archivos estáticos o redirigir
$path = $_SERVER['REQUEST_URI'];

// Si es una ruta a pages/, incluir el archivo correspondiente
if (strpos($path, '/pages/') === 0) {
    $file = __DIR__ . $path;
    if (is_dir($file)) {
        $file .= '/index.php';
    }
    if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        include $file;
        exit;
    }
}

// Para archivos estáticos (css, js, img)
$staticFile = __DIR__ . $path;
if (file_exists($staticFile) && !is_dir($staticFile)) {
    return false; // Dejar que el servidor web maneje archivos estáticos
}

// Si no se encuentra nada, redirigir a pages/
header('Location: /pages/');
exit;
?>
