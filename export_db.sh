#!/bin/bash

# Script para exportar la base de datos PostgreSQL
# Ejecutar en tu computadora local antes del deploy

echo "=== Exportando Base de Datos PostgreSQL ==="

# Configuración de tu base de datos local
DB_HOST="localhost"
DB_PORT="1573"
DB_NAME="EncuentraTuHuella"
DB_USER="postgres"

# Crear directorio de respaldo si no existe
mkdir -p database_export

# Exportar esquema (estructura de tablas)
echo "Exportando esquema..."
pg_dump -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME --schema-only --no-owner --no-privileges > database_export/schema.sql

# Exportar datos
echo "Exportando datos..."
pg_dump -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME --data-only --no-owner --no-privileges > database_export/data.sql

# Exportar todo junto (alternativa)
echo "Exportando base de datos completa..."
pg_dump -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME --no-owner --no-privileges > database_export/full_backup.sql

echo "=== Exportación completada ==="
echo "Archivos generados en: database_export/"
echo "- schema.sql: Solo estructura de tablas"
echo "- data.sql: Solo datos"
echo "- full_backup.sql: Estructura + datos"
