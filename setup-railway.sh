#!/bin/bash

# Script de configuración para Railway
echo "🚀 Configurando Encuentra Tu Huella para Railway..."

# Crear directorios necesarios si no existen
mkdir -p uploads/mascotas
mkdir -p uploads/reportes
mkdir -p logs

# Configurar permisos
chmod 755 -R .
chmod 777 -R uploads/
chmod 777 -R logs/

# Crear archivos .gitkeep para mantener directorios vacíos
touch uploads/.gitkeep
touch uploads/mascotas/.gitkeep
touch uploads/reportes/.gitkeep
touch logs/.gitkeep

echo "✅ Configuración completada para Railway"
echo "📋 Base de datos PostgreSQL requerida"
echo "🔧 Variables de entorno configuradas automáticamente"
