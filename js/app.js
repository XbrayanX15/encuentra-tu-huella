// =====================================================
// Pet Finder CDMX - JavaScript Principal
// =====================================================

(function() {
    'use strict';

    // Configuración global
    window.App = window.App || {};
    
    // Inicialización cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initializeApp();
    });

    function initializeApp() {
        initializeTooltips();
        initializePopovers();
        initializeForms();
        initializeImagePreview();
        initDynamicSelectors();
        initializeGeolocation();
        initializeSearch();
        initDynamicSelectors();
        
        console.log('Pet Finder CDMX initialized successfully');
    }

    // =====================================================
    // INICIALIZACIÓN DE COMPONENTES BOOTSTRAP
    // =====================================================

    function initializeTooltips() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    function initializePopovers() {
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }

    // =====================================================
    // FORMULARIOS
    // =====================================================

    function initializeForms() {
        // Validación de formularios
        const forms = document.querySelectorAll('.needs-validation');
        
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Scroll al primer campo con error
                    const firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstInvalid.focus();
                    }
                }
                form.classList.add('was-validated');
            }, false);
        });

        // Auto-resize de textareas
        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(function(textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });

        // Validación de email en tiempo real
        const emailInputs = document.querySelectorAll('input[type="email"]');
        emailInputs.forEach(function(input) {
            input.addEventListener('blur', validateEmail);
        });

        // Validación de teléfono en tiempo real
        const phoneInputs = document.querySelectorAll('input[type="tel"]');
        phoneInputs.forEach(function(input) {
            input.addEventListener('input', formatPhone);
            input.addEventListener('blur', validatePhone);
        });
    }

    function validateEmail(event) {
        const email = event.target.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            event.target.setCustomValidity('Por favor ingresa un email válido');
        } else {
            event.target.setCustomValidity('');
        }
    }

    function formatPhone(event) {
        let value = event.target.value.replace(/\D/g, '');
        
        if (value.length >= 10) {
            value = value.replace(/(\d{2})(\d{4})(\d{4})/, '$1-$2-$3');
        }
        
        event.target.value = value;
    }

    function validatePhone(event) {
        const phone = event.target.value;
        const phoneRegex = /^\d{2}-\d{4}-\d{4}$/;
        
        if (phone && !phoneRegex.test(phone)) {
            event.target.setCustomValidity('Formato: 55-1234-5678');
        } else {
            event.target.setCustomValidity('');
        }
    }

    // =====================================================
    // PREVIEW DE IMÁGENES
    // =====================================================

    function initializeImagePreview() {
        const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
        
        imageInputs.forEach(function(input) {
            input.addEventListener('change', function(event) {
                handleImagePreview(event);
            });
        });
    }

    function handleImagePreview(event) {
        const files = event.target.files;
        const previewContainer = document.getElementById('image-preview');
        
        if (!previewContainer) return;
        
        previewContainer.innerHTML = '';
        
        Array.from(files).forEach(function(file, index) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const imageDiv = document.createElement('div');
                    imageDiv.className = 'col-md-3 mb-3';
                    imageDiv.innerHTML = `
                        <div class="card">
                            <img src="${e.target.result}" class="card-img-top" style="height: 150px; object-fit: cover;">
                            <div class="card-body p-2">
                                <button type="button" class="btn btn-sm btn-danger w-100" onclick="removeImagePreview(this)">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    `;
                    previewContainer.appendChild(imageDiv);
                };
                
                reader.readAsDataURL(file);
            }
        });
    }

    window.removeImagePreview = function(button) {
        const imageDiv = button.closest('.col-md-3');
        imageDiv.remove();
    };

    // =====================================================
    // GEOLOCALIZACIÓN
    // =====================================================

    function initializeGeolocation() {
        const locationButton = document.getElementById('get-location');
        
        if (locationButton) {
            locationButton.addEventListener('click', getCurrentLocation);
        }
    }

    function getCurrentLocation() {
        if (!navigator.geolocation) {
            showToast('Tu navegador no soporta geolocalización', 'error');
            return;
        }

        const button = document.getElementById('get-location');
        const originalText = button.innerHTML;
        
        button.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Obteniendo ubicación...';
        button.disabled = true;

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // Verificar que esté en CDMX
                if (isInCDMX(lat, lng)) {
                    document.getElementById('latitud').value = lat;
                    document.getElementById('longitud').value = lng;
                    
                    // Actualizar mapa si existe
                    if (window.updateMapLocation) {
                        window.updateMapLocation(lat, lng);
                    }
                    
                    showToast('Ubicación obtenida correctamente', 'success');
                } else {
                    showToast('La ubicación debe estar dentro de la Ciudad de México', 'warning');
                }
                
                button.innerHTML = originalText;
                button.disabled = false;
            },
            function(error) {
                let message = 'Error al obtener la ubicación';
                
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message = 'Debes permitir el acceso a la ubicación';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = 'Ubicación no disponible';
                        break;
                    case error.TIMEOUT:
                        message = 'Tiempo de espera agotado';
                        break;
                }
                
                showToast(message, 'error');
                button.innerHTML = originalText;
                button.disabled = false;
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000
            }
        );
    }

    function isInCDMX(lat, lng) {
        // Límites aproximados de CDMX
        return lat >= 19.2 && lat <= 19.6 && lng >= -99.4 && lng <= -98.9;
    }

    // =====================================================
    // BÚSQUEDA
    // =====================================================

    function initializeSearch() {
        const searchForm = document.getElementById('search-form');
        const searchInput = document.getElementById('search-input');
        const municipioSelect = document.getElementById('municipio_id');
        const coloniaSelect = document.getElementById('colonia_id');

        if (searchForm) {
            searchForm.addEventListener('submit', handleSearch);
        }

        if (municipioSelect && coloniaSelect) {
            municipioSelect.addEventListener('change', function() {
                loadColonias(this.value);
            });
        }

        // Búsqueda instantánea
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 3) {
                        performInstantSearch(this.value);
                    }
                }, 500);
            });
        }
    }

    function handleSearch(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const params = new URLSearchParams(formData);
        
        showLoading();
        
        fetch('/api/buscar.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                hideLoading();
                displaySearchResults(data);
            })
            .catch(error => {
                hideLoading();
                showToast('Error en la búsqueda', 'error');
                console.error('Error:', error);
            });
    }

    function loadColonias(municipioId) {
        if (!municipioId) {
            document.getElementById('colonia_id').innerHTML = '<option value="">Selecciona una colonia</option>';
            return;
        }

        fetch(`/api/colonias.php?municipio_id=${municipioId}`)
            .then(response => response.json())
            .then(data => {
                const coloniaSelect = document.getElementById('colonia_id');
                coloniaSelect.innerHTML = '<option value="">Selecciona una colonia</option>';
                
                data.forEach(colonia => {
                    const option = document.createElement('option');
                    option.value = colonia.id;
                    option.textContent = colonia.nombre;
                    coloniaSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error cargando colonias:', error);
            });
    }

    function performInstantSearch(query) {
        fetch(`/api/buscar.php?q=${encodeURIComponent(query)}&instant=1`)
            .then(response => response.json())
            .then(data => {
                showSearchSuggestions(data);
            })
            .catch(error => {
                console.error('Error en búsqueda instantánea:', error);
            });
    }

    function showSearchSuggestions(data) {
        // Implementar dropdown de sugerencias
        const suggestionsContainer = document.getElementById('search-suggestions');
        if (!suggestionsContainer) return;

        suggestionsContainer.innerHTML = '';
        
        if (data.length === 0) {
            suggestionsContainer.style.display = 'none';
            return;
        }

        data.slice(0, 5).forEach(item => {
            const suggestion = document.createElement('div');
            suggestion.className = 'search-suggestion p-2 border-bottom';
            suggestion.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-search me-2"></i>
                    <span>${item.nombre} - ${item.tipo}</span>
                </div>
            `;
            suggestion.addEventListener('click', () => {
                document.getElementById('search-input').value = item.nombre;
                suggestionsContainer.style.display = 'none';
            });
            suggestionsContainer.appendChild(suggestion);
        });

        suggestionsContainer.style.display = 'block';
    }

    // =====================================================
    // FUNCIONES PARA SELECTORES DINÁMICOS
    // =====================================================
    
    function initDynamicSelectors() {
        // Selector de municipios
        const municipioSelect = document.getElementById('municipio_id');
        const coloniaSelect = document.getElementById('colonia_id');
        
        if (municipioSelect && coloniaSelect) {
            municipioSelect.addEventListener('change', function() {
                const municipioId = this.value;
                loadColonias(municipioId, coloniaSelect);
            });
        }
        
        // Cargar municipios al inicio
        const entidadSelect = document.getElementById('entidad_id');
        if (entidadSelect && municipioSelect) {
            entidadSelect.addEventListener('change', function() {
                const entidadId = this.value;
                loadMunicipios(entidadId, municipioSelect, coloniaSelect);
            });
            
            // Cargar municipios por defecto (CDMX)
            loadMunicipios(1, municipioSelect, coloniaSelect);
        }
    }
    
    function loadMunicipios(entidadId, municipioSelect, coloniaSelect) {
        if (!entidadId) {
            municipioSelect.innerHTML = '<option value="">Selecciona un municipio</option>';
            coloniaSelect.innerHTML = '<option value="">Selecciona una colonia</option>';
            return;
        }
        
        // Mostrar loading
        municipioSelect.innerHTML = '<option value="">Cargando...</option>';
        coloniaSelect.innerHTML = '<option value="">Selecciona una colonia</option>';
        
        fetch(`../api/catalogos.php?action=municipios&entidad_id=${entidadId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let options = '<option value="">Selecciona un municipio</option>';
                    data.data.forEach(municipio => {
                        options += `<option value="${municipio.id}">${municipio.nombre}</option>`;
                    });
                    municipioSelect.innerHTML = options;
                } else {
                    console.error('Error cargando municipios:', data.error);
                    municipioSelect.innerHTML = '<option value="">Error al cargar</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                municipioSelect.innerHTML = '<option value="">Error al cargar</option>';
            });
    }
    
    function loadColonias(municipioId, coloniaSelect) {
        if (!municipioId) {
            coloniaSelect.innerHTML = '<option value="">Selecciona una colonia</option>';
            return;
        }
        
        // Mostrar loading
        coloniaSelect.innerHTML = '<option value="">Cargando...</option>';
        
        fetch(`../api/catalogos.php?action=colonias&municipio_id=${municipioId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let options = '<option value="">Selecciona una colonia</option>';
                    data.data.forEach(colonia => {
                        options += `<option value="${colonia.id}">${colonia.nombre}</option>`;
                    });
                    coloniaSelect.innerHTML = options;
                } else {
                    console.error('Error cargando colonias:', data.error);
                    coloniaSelect.innerHTML = '<option value="">Error al cargar</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                coloniaSelect.innerHTML = '<option value="">Error al cargar</option>';
            });
    }

    // =====================================================
    // UTILIDADES
    // =====================================================

    window.showToast = function(message, type = 'info') {
        const toastEl = document.getElementById('liveToast');
        if (!toastEl) return;

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
    };

    window.showLoading = function() {
        const loadingEl = document.querySelector('.loading');
        if (loadingEl) {
            loadingEl.style.display = 'block';
        }
    };

    window.hideLoading = function() {
        const loadingEl = document.querySelector('.loading');
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }
    };

    window.confirmDelete = function(message = '¿Estás seguro de que quieres eliminar este elemento?') {
        return confirm(message);
    };

    // Función para formatear fechas
    window.formatDate = function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-MX', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    // Función para calcular tiempo transcurrido
    window.timeAgo = function(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (minutes < 60) {
            return `hace ${minutes} minutos`;
        } else if (hours < 24) {
            return `hace ${hours} horas`;
        } else {
            return `hace ${days} días`;
        }
    };

    // =====================================================
    // AJAX HELPERS
    // =====================================================

    window.makeRequest = function(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.App.csrfToken
            }
        };

        const finalOptions = { ...defaultOptions, ...options };

        return fetch(url, finalOptions)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
    };

    // Auto-hide alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            if (alert.querySelector('.btn-close')) {
                setTimeout(() => {
                    alert.querySelector('.btn-close').click();
                }, 5000);
            }
        });
    }, 100);

})();
