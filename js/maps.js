// =====================================================
// Pet Finder CDMX - Google Maps JavaScript
// =====================================================

let map;
let markers = [];
let infoWindow;
let userLocation = null;

// Configuración por defecto de CDMX
const CDMX_CENTER = { lat: 19.4326, lng: -99.1332 };
const CDMX_BOUNDS = {
    north: 19.6,
    south: 19.2,
    east: -98.9,
    west: -99.4
};

// Inicializar mapa
function initMap() {
    // Crear mapa centrado en CDMX
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 11,
        center: CDMX_CENTER,
        restriction: {
            latLngBounds: CDMX_BOUNDS,
            strictBounds: false
        },
        mapTypeControl: true,
        streetViewControl: true,
        fullscreenControl: true,
        styles: [
            {
                featureType: "poi.business",
                stylers: [{ visibility: "off" }]
            }
        ]
    });

    // Crear ventana de información
    infoWindow = new google.maps.InfoWindow();

    // Event listeners
    map.addListener('click', onMapClick);
    
    // Cargar marcadores si hay datos
    if (typeof window.mapData !== 'undefined') {
        loadMarkers(window.mapData);
    }
    
    // Intentar obtener ubicación del usuario
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            position => {
                userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                
                // Verificar que esté en CDMX
                if (isInCDMX(userLocation.lat, userLocation.lng)) {
                    // Agregar marcador de usuario
                    addUserLocationMarker(userLocation);
                    
                    // Centrar mapa en la ubicación del usuario
                    map.setCenter(userLocation);
                    map.setZoom(13);
                }
            },
            error => {
                console.log('Error obteniendo ubicación:', error);
            }
        );
    }
}

// Verificar si las coordenadas están en CDMX
function isInCDMX(lat, lng) {
    return lat >= CDMX_BOUNDS.south && 
           lat <= CDMX_BOUNDS.north && 
           lng >= CDMX_BOUNDS.west && 
           lng <= CDMX_BOUNDS.east;
}

// Manejar click en el mapa
function onMapClick(event) {
    const lat = event.latLng.lat();
    const lng = event.latLng.lng();
    
    // Verificar que esté en CDMX
    if (!isInCDMX(lat, lng)) {
        showToast('La ubicación debe estar dentro de la Ciudad de México', 'warning');
        return;
    }
    
    // Si hay campos de latitud y longitud, actualizar
    const latInput = document.getElementById('latitud');
    const lngInput = document.getElementById('longitud');
    
    if (latInput && lngInput) {
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
        
        // Agregar marcador temporal
        clearTemporaryMarkers();
        addTemporaryMarker({ lat, lng });
        
        // Obtener dirección
        getAddressFromCoordinates(lat, lng);
    }
}

// Agregar marcador de ubicación del usuario
function addUserLocationMarker(location) {
    const marker = new google.maps.Marker({
        position: location,
        map: map,
        title: 'Tu ubicación',
        icon: {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#4285f4">
                    <circle cx="12" cy="12" r="8"/>
                    <circle cx="12" cy="12" r="3" fill="white"/>
                </svg>
            `),
            scaledSize: new google.maps.Size(24, 24)
        }
    });
    
    marker.addListener('click', () => {
        infoWindow.setContent('<strong>Tu ubicación actual</strong>');
        infoWindow.open(map, marker);
    });
}

// Agregar marcador temporal (para selección de ubicación)
function addTemporaryMarker(location) {
    const marker = new google.maps.Marker({
        position: location,
        map: map,
        title: 'Ubicación seleccionada',
        icon: {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="#e53e3e">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
            `),
            scaledSize: new google.maps.Size(32, 32)
        },
        animation: google.maps.Animation.DROP
    });
    
    markers.push({ marker, type: 'temporary' });
}

// Limpiar marcadores temporales
function clearTemporaryMarkers() {
    markers = markers.filter(item => {
        if (item.type === 'temporary') {
            item.marker.setMap(null);
            return false;
        }
        return true;
    });
}

// Cargar marcadores de mascotas
function loadMarkers(data) {
    clearAllMarkers();
    
    data.forEach(item => {
        if (item.latitud && item.longitud) {
            addPetMarker(item);
        }
    });
    
    // Ajustar vista para mostrar todos los marcadores
    if (data.length > 0) {
        fitMapToMarkers();
    }
}

// Agregar marcador de mascota
function addPetMarker(pet) {
    const position = {
        lat: parseFloat(pet.latitud),
        lng: parseFloat(pet.longitud)
    };
    
    // Icono según el tipo
    let iconColor = '#3182ce';
    let iconSymbol = '🐕';
    
    if (pet.tipo === 'perdido') {
        iconColor = '#e53e3e';
        iconSymbol = '❗';
    } else if (pet.tipo === 'encontrado') {
        iconColor = '#38a169';
        iconSymbol = '👁';
    }
    
    const marker = new google.maps.Marker({
        position: position,
        map: map,
        title: pet.nombre || 'Sin nombre',
        icon: {
            url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="${iconColor}">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
            `)}`,
            scaledSize: new google.maps.Size(32, 32)
        }
    });
    
    // Contenido del info window
    const contentString = `
        <div class="info-window">
            <h6>${pet.nombre || 'Sin nombre'}</h6>
            <p class="mb-1"><strong>Tipo:</strong> ${pet.tipo === 'perdido' ? 'Perdido' : 'Encontrado'}</p>
            <p class="mb-1"><strong>Raza:</strong> ${pet.raza || 'No especificada'}</p>
            <p class="mb-1"><strong>Fecha:</strong> ${formatDate(pet.fecha_hora)}</p>
            <p class="mb-2"><strong>Ubicación:</strong> ${pet.municipio || 'CDMX'}</p>
            <a href="/pages/${pet.tipo}.php?id=${pet.id}" class="btn btn-primary btn-sm" target="_blank">
                Ver Detalles
            </a>
        </div>
    `;
    
    marker.addListener('click', () => {
        infoWindow.setContent(contentString);
        infoWindow.open(map, marker);
    });
    
    markers.push({ marker, type: 'pet', data: pet });
}

// Limpiar todos los marcadores
function clearAllMarkers() {
    markers.forEach(item => {
        item.marker.setMap(null);
    });
    markers = [];
}

// Ajustar mapa para mostrar todos los marcadores
function fitMapToMarkers() {
    if (markers.length === 0) return;
    
    const bounds = new google.maps.LatLngBounds();
    
    markers.forEach(item => {
        if (item.type === 'pet') {
            bounds.extend(item.marker.getPosition());
        }
    });
    
    map.fitBounds(bounds);
    
    // Asegurar zoom mínimo
    google.maps.event.addListenerOnce(map, 'bounds_changed', () => {
        if (map.getZoom() > 15) {
            map.setZoom(15);
        }
    });
}

// Obtener dirección desde coordenadas
function getAddressFromCoordinates(lat, lng) {
    const geocoder = new google.maps.Geocoder();
    const latlng = { lat, lng };
    
    geocoder.geocode({ location: latlng }, (results, status) => {
        if (status === 'OK' && results[0]) {
            const address = results[0].formatted_address;
            const addressField = document.getElementById('direccion');
            
            if (addressField) {
                addressField.value = address;
            }
            
            // Extraer componentes de dirección
            extractAddressComponents(results[0]);
        }
    });
}

// Extraer componentes de dirección
function extractAddressComponents(result) {
    const components = result.address_components;
    let colonia = '';
    let municipio = '';
    
    components.forEach(component => {
        const types = component.types;
        
        if (types.includes('sublocality') || types.includes('neighborhood')) {
            colonia = component.long_name;
        }
        
        if (types.includes('administrative_area_level_1') || 
            types.includes('locality')) {
            municipio = component.long_name;
        }
    });
    
    // Actualizar selects si existen
    updateLocationSelects(colonia, municipio);
}

// Actualizar selects de ubicación
function updateLocationSelects(colonia, municipio) {
    const municipioSelect = document.getElementById('municipio_id');
    const coloniaSelect = document.getElementById('colonia_id');
    
    if (municipioSelect) {
        // Buscar municipio que coincida
        for (let option of municipioSelect.options) {
            if (option.text.toLowerCase().includes(municipio.toLowerCase())) {
                municipioSelect.value = option.value;
                
                // Cargar colonias para este municipio
                if (typeof loadColonias === 'function') {
                    loadColonias(option.value);
                }
                break;
            }
        }
    }
    
    if (coloniaSelect && colonia) {
        // Esperar a que se carguen las colonias
        setTimeout(() => {
            for (let option of coloniaSelect.options) {
                if (option.text.toLowerCase().includes(colonia.toLowerCase())) {
                    coloniaSelect.value = option.value;
                    break;
                }
            }
        }, 1000);
    }
}

// Buscar lugares
function searchPlaces(query) {
    const service = new google.maps.places.PlacesService(map);
    const request = {
        query: query,
        bounds: new google.maps.LatLngBounds(
            new google.maps.LatLng(CDMX_BOUNDS.south, CDMX_BOUNDS.west),
            new google.maps.LatLng(CDMX_BOUNDS.north, CDMX_BOUNDS.east)
        )
    };
    
    service.textSearch(request, (results, status) => {
        if (status === google.maps.places.PlacesService.OK && results.length > 0) {
            const place = results[0];
            map.setCenter(place.geometry.location);
            map.setZoom(15);
            
            // Agregar marcador temporal
            clearTemporaryMarkers();
            addTemporaryMarker({
                lat: place.geometry.location.lat(),
                lng: place.geometry.location.lng()
            });
        }
    });
}

// Función para actualizar ubicación en el mapa (llamada desde app.js)
window.updateMapLocation = function(lat, lng) {
    if (map) {
        const position = { lat, lng };
        map.setCenter(position);
        map.setZoom(15);
        
        clearTemporaryMarkers();
        addTemporaryMarker(position);
        
        getAddressFromCoordinates(lat, lng);
    }
};

// Función para inicializar mapa simple (sin marcadores)
window.initSimpleMap = function() {
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 11,
        center: CDMX_CENTER,
        restriction: {
            latLngBounds: CDMX_BOUNDS,
            strictBounds: false
        }
    });
    
    infoWindow = new google.maps.InfoWindow();
    map.addListener('click', onMapClick);
};

// Formatear fecha para info windows
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Exportar funciones globales
window.initMap = initMap;
window.loadMarkers = loadMarkers;
window.clearAllMarkers = clearAllMarkers;
window.searchPlaces = searchPlaces;
