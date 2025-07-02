<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
setSecurityHeaders();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Estilos personalizados -->
    <link href="../css/styles.css" rel="stylesheet">
    
    <!-- Leaflet CSS para mapas -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../img/favicon.ico">
    
    <?php if (isset($include_maps) && $include_maps): ?>
    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&libraries=places" defer></script>
    <?php endif; ?>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: #2c5282 !important;
        }
        
        .btn-primary {
            background-color: #3182ce;
            border-color: #3182ce;
        }
        
        .btn-primary:hover {
            background-color: #2c5282;
            border-color: #2c5282;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .text-primary {
            color: #3182ce !important;
        }
        
        .bg-primary {
            background-color: #3182ce !important;
        }
        
        .alert {
            border: none;
            border-radius: 8px;
        }
        
        .form-control:focus {
            border-color: #3182ce;
            box-shadow: 0 0 0 0.2rem rgba(49, 130, 206, 0.25);
        }
        
        .page-header {
            background: linear-gradient(135deg, #3182ce 0%, #2c5282 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .pet-card {
            transition: all 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .pet-card img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        
        .map-container {
            height: 400px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .footer {
            background-color: #2d3748;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .spinner-border {
            color: #3182ce;
        }
        
        /* Header con scroll dinámico */
        .navbar-scroll {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
                        background-color 0.3s ease-in-out,
                        box-shadow 0.3s ease-in-out;
            background-color: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .navbar-scroll.navbar-hidden {
            transform: translateY(-100%);
        }
        
        .navbar-scroll.navbar-visible {
            transform: translateY(0);
        }
        
        .navbar-scroll.scrolled {
            background-color: rgba(255, 255, 255, 0.98) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        /* Efecto hover en navbar cuando está oculto */
        .navbar-scroll.navbar-hidden:hover {
            transform: translateY(0);
        }
        
        /* Mejorar estilos de navegación */
        .navbar-scroll .navbar-nav .nav-link {
            transition: color 0.2s ease-in-out;
            font-weight: 500;
        }
        
        .navbar-scroll .navbar-nav .nav-link:hover {
            color: var(--primary-color) !important;
        }
        
        .navbar-scroll .navbar-brand {
            transition: transform 0.2s ease-in-out;
        }
        
        .navbar-scroll .navbar-brand:hover {
            transform: scale(1.05);
        }
        
        /* Compensar el espacio del navbar fijo */
        body.navbar-fixed {
            padding-top: 0;
        }
        
        /* Ajustar hero section para navbar fijo */
        .hero {
            margin-top: 0;
            padding-top: 156px;
        }
        
        /* Para páginas sin hero, agregar padding top */
        body.navbar-fixed:not(.has-hero) {
            padding-top: 80px !important;
        }
        
        /* Asegurar que el contenido no se superponga con el navbar fijo */
        .main-content {
            min-height: calc(100vh - 160px);
        }
    </style>
</head>
<body>
    <!-- Navegación -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-scroll" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-heart-fill text-danger me-2"></i>
                <span><?php echo SITE_NAME; ?></span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house me-1"></i>Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="buscar.php">
                            <i class="bi bi-search me-1"></i>Buscar
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="mascotasDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-heart me-1"></i>Mis Mascotas
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="mis_mascotas.php">
                                <i class="bi bi-list me-1"></i>Ver Mis Mascotas
                            </a></li>
                            <li><a class="dropdown-item" href="registrar_mascota.php">
                                <i class="bi bi-plus me-1"></i>Registrar Nueva
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportesDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-clipboard me-1"></i>Reportes
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="mis_reportes.php">
                                <i class="bi bi-list me-1"></i>Mis Reportes
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="reportar_perdida.php">
                                <i class="bi bi-exclamation-triangle me-1"></i>Reportar Pérdida
                            </a></li>
                            <li><a class="dropdown-item text-success" href="reportar_avistamiento.php">
                                <i class="bi bi-eye me-1"></i>Reportar Avistamiento
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-plus-circle me-1"></i>Reportar
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="reportar_perdida.php">
                                <i class="bi bi-exclamation-triangle me-1"></i>Mascota Perdida
                            </a></li>
                            <li><a class="dropdown-item" href="reportar_avistamiento.php">
                                <i class="bi bi-eye me-1"></i>Mascota Encontrada
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): 
                        $user = getCurrentUser();
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <span><?php echo escape($user['nombre']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i>Mi Panel
                            </a></li>
                            <li><a class="dropdown-item" href="perfil.php">
                                <i class="bi bi-person me-1"></i>Mi Perfil
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../api/auth.php?action=logout">
                                <i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="register.php">
                            <i class="bi bi-person-plus me-1"></i>Registrarse
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Mostrar mensajes de sesión -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="container mt-3">
            <?php echo showAlert($_SESSION['success_message'], 'success'); ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="container mt-3">
            <?php echo showAlert($_SESSION['error_message'], 'danger'); ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Contenido principal -->
    <main>

    <script>
    // JavaScript para header dinámico con scroll
    document.addEventListener('DOMContentLoaded', function() {
        const navbar = document.getElementById('mainNavbar');
        const body = document.body;
        let lastScrollTop = 0;
        let scrollThreshold = 100; // Píxeles antes de que el header se oculte
        let ticking = false;

        // Agregar clase para compensar navbar fijo
        body.classList.add('navbar-fixed');

        function updateNavbar() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Agregar/remover clase 'scrolled' para efectos visuales
            if (scrollTop > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            // Mostrar/ocultar navbar basado en dirección del scroll
            if (scrollTop > scrollThreshold) {
                if (scrollTop > lastScrollTop) {
                    // Scrolling hacia abajo - ocultar navbar
                    navbar.classList.add('navbar-hidden');
                    navbar.classList.remove('navbar-visible');
                } else {
                    // Scrolling hacia arriba - mostrar navbar
                    navbar.classList.remove('navbar-hidden');
                    navbar.classList.add('navbar-visible');
                }
            } else {
                // En la parte superior - siempre mostrar
                navbar.classList.remove('navbar-hidden');
                navbar.classList.add('navbar-visible');
            }

            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; // Para mobile o scroll negativo
            ticking = false;
        }

        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateNavbar);
                ticking = true;
            }
        }

        // Event listener para scroll
        window.addEventListener('scroll', requestTick, { passive: true });

        // Mostrar navbar inicialmente
        navbar.classList.add('navbar-visible');

        // Manejar hover del navbar cuando está oculto (opcional)
        let hoverTimeout;
        
        navbar.addEventListener('mouseenter', function() {
            clearTimeout(hoverTimeout);
            if (window.pageYOffset > scrollThreshold) {
                navbar.classList.remove('navbar-hidden');
                navbar.classList.add('navbar-visible');
            }
        });

        navbar.addEventListener('mouseleave', function() {
            if (window.pageYOffset > scrollThreshold && window.pageYOffset > lastScrollTop) {
                hoverTimeout = setTimeout(function() {
                    navbar.classList.add('navbar-hidden');
                    navbar.classList.remove('navbar-visible');
                }, 1000); // Esperar 1 segundo antes de ocultar
            }
        });

        // Manejar resize de ventana
        window.addEventListener('resize', function() {
            requestTick();
        });
    });
    </script>
