<?php
$page_title = 'Página no encontrada';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card shadow">
                <div class="card-body py-5">
                    <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                    <h1 class="display-4 mt-3">404</h1>
                    <h3 class="mb-3">Página no encontrada</h3>
                    <p class="lead text-muted mb-4">
                        Lo sentimos, la página que buscas no existe o está en desarrollo.
                    </p>
                    
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                        <a href="index.php" class="btn btn-primary">
                            <i class="bi bi-house me-2"></i>Ir al Inicio
                        </a>
                        <a href="buscar.php" class="btn btn-outline-primary">
                            <i class="bi bi-search me-2"></i>Buscar Mascotas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
