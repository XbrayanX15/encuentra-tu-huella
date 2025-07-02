#!/bin/bash

echo "=== Iniciando configuración para Encuentra Tu Huella ==="

# Verificar si PHP está instalado
if ! command -v php &> /dev/null; then
    echo "PHP no encontrado. Instalando PHP 8.1..."
    
    # Actualizar repositorios
    apt-get update -y
    
    # Instalar PHP y extensiones necesarias
    apt-get install -y \
        php8.1 \
        php8.1-cli \
        php8.1-pgsql \
        php8.1-pdo \
        php8.1-gd \
        php8.1-mbstring \
        php8.1-json \
        php8.1-curl
    
    # Crear symlink si es necesario
    if [ ! -f /usr/bin/php ]; then
        ln -sf /usr/bin/php8.1 /usr/bin/php
    fi
    
    echo "PHP instalado correctamente"
else
    echo "PHP ya está disponible"
fi

# Verificar versión de PHP
php --version

# Crear directorios necesarios
mkdir -p uploads logs
chmod 755 uploads logs

echo "=== Configuración completada ==="
