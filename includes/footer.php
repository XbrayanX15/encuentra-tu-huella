    </main>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">
                        <i class="bi bi-heart-fill text-danger me-2"></i>
                        <?php echo SITE_NAME; ?>
                    </h5>
                    <p class="text-light"><?php echo SITE_DESCRIPTION; ?></p>
                    <div class="d-flex">
                        <a href="#" class="text-light me-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-light"><i class="bi bi-envelope"></i></a>
                    </div>
                </div>
                
                <div class="col-md-2 mb-4">
                    <h6 class="mb-3">Enlaces</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-light text-decoration-none">Inicio</a></li>
                        <li><a href="buscar.php" class="text-light text-decoration-none">Buscar</a></li>
                        <li><a href="login.php" class="text-light text-decoration-none">Iniciar Sesión</a></li>
                        <li><a href="register.php" class="text-light text-decoration-none">Registrarse</a></li>
                    </ul>
                </div>
                
                <div class="col-md-2 mb-4">
                    <h6 class="mb-3">Reportar</h6>
                    <ul class="list-unstyled">
                        <li><a href="reportar_perdida.php" class="text-light text-decoration-none">Mascota Perdida</a></li>
                        <li><a href="reportar_avistamiento.php" class="text-light text-decoration-none">Avistamiento</a></li>
                        <li><a href="mis_reportes.php" class="text-light text-decoration-none">Mis Reportes</a></li>
                        <li><a href="dashboard.php" class="text-light text-decoration-none">Mi Panel</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h6 class="mb-3">Contacto</h6>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-telephone me-2"></i>
                        <span class="text-light"><?php echo CONTACT_PHONE; ?></span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-envelope me-2"></i>
                        <span class="text-light"><?php echo CONTACT_EMAIL; ?></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-geo-alt me-2"></i>
                        <span class="text-light">Ciudad de México, México</span>
                    </div>
                </div>
            </div>
            
            <hr class="border-light">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-light mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-light">
                        Versión <?php echo APP_VERSION; ?> | 
                        <a href="/pages/privacidad.php" class="text-light text-decoration-none">Privacidad</a> | 
                        <a href="/pages/terminos.php" class="text-light text-decoration-none">Términos</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Toast Container para notificaciones -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi bi-info-circle-fill text-primary me-2"></i>
                <strong class="me-auto">Notificación</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                <!-- Contenido del toast -->
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript personalizado -->
    <script src="/js/app.js"></script>
    
    <?php if (isset($include_maps) && $include_maps): ?>
    <!-- Google Maps JavaScript -->
    <script src="/js/maps.js"></script>
    <?php endif; ?>
    
    <script>
        // Configuración global de JavaScript
        window.App = {
            baseUrl: '<?php echo SITE_URL; ?>',
            csrfToken: '<?php echo generateCSRFToken(); ?>',
            isLoggedIn: <?php echo isLoggedIn() ? 'true' : 'false'; ?>,
            userId: <?php echo isLoggedIn() ? $_SESSION['user_id'] : 'null'; ?>,
            googleMapsApiKey: '<?php echo GOOGLE_MAPS_API_KEY; ?>'
        };

        // Inicializar tooltips de Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Función para mostrar toast
        function showToast(message, type = 'info') {
            const toastEl = document.getElementById('liveToast');
            const toastBody = toastEl.querySelector('.toast-body');
            const toastHeader = toastEl.querySelector('.toast-header i');
            
            toastBody.textContent = message;
            
            // Cambiar icono según el tipo
            toastHeader.className = 'me-2';
            switch(type) {
                case 'success':
                    toastHeader.classList.add('bi', 'bi-check-circle-fill', 'text-success');
                    break;
                case 'error':
                case 'danger':
                    toastHeader.classList.add('bi', 'bi-exclamation-triangle-fill', 'text-danger');
                    break;
                case 'warning':
                    toastHeader.classList.add('bi', 'bi-exclamation-circle-fill', 'text-warning');
                    break;
                default:
                    toastHeader.classList.add('bi', 'bi-info-circle-fill', 'text-primary');
            }
            
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        }

        // Función para confirmar eliminaciones
        function confirmDelete(message = '¿Estás seguro de que quieres eliminar este elemento?') {
            return confirm(message);
        }

        // Función para mostrar loading
        function showLoading() {
            const loadingEl = document.querySelector('.loading');
            if (loadingEl) {
                loadingEl.style.display = 'block';
            }
        }

        function hideLoading() {
            const loadingEl = document.querySelector('.loading');
            if (loadingEl) {
                loadingEl.style.display = 'none';
            }
        }

        // Auto-hide alerts después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    if (alert.querySelector('.btn-close')) {
                        alert.querySelector('.btn-close').click();
                    }
                }, 5000);
            });
        });

        // Validación de formularios en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        });
    </script>

    <!-- Leaflet JS para mapas -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- JavaScript principal de la aplicación -->
    <script src="../js/app.js"></script>

    <!-- Script específico de la página -->
    <?php if (isset($page_script)): ?>
        <script><?php echo $page_script; ?></script>
    <?php endif; ?>

</body>
</html>
