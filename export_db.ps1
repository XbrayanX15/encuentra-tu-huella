# Script PowerShell para exportar la base de datos PostgreSQL
# Ejecutar en PowerShell antes del deploy

Write-Host "=== Exportando Base de Datos PostgreSQL ===" -ForegroundColor Green

# Configuración de tu base de datos local
$DB_HOST = "localhost"
$DB_PORT = "1573"
$DB_NAME = "EncuentraTuHuella"
$DB_USER = "postgres"

# Crear directorio de respaldo si no existe
if (!(Test-Path "database_export")) {
    New-Item -ItemType Directory -Path "database_export"
}

Write-Host "Exportando esquema..." -ForegroundColor Yellow
& pg_dump -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME --schema-only --no-owner --no-privileges | Out-File -FilePath "database_export\schema.sql" -Encoding UTF8

Write-Host "Exportando datos..." -ForegroundColor Yellow
& pg_dump -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME --data-only --no-owner --no-privileges | Out-File -FilePath "database_export\data.sql" -Encoding UTF8

Write-Host "Exportando base de datos completa..." -ForegroundColor Yellow
& pg_dump -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME --no-owner --no-privileges | Out-File -FilePath "database_export\full_backup.sql" -Encoding UTF8

Write-Host "=== Exportación completada ===" -ForegroundColor Green
Write-Host "Archivos generados en: database_export/" -ForegroundColor Cyan
Write-Host "- schema.sql: Solo estructura de tablas" -ForegroundColor Cyan
Write-Host "- data.sql: Solo datos" -ForegroundColor Cyan
Write-Host "- full_backup.sql: Estructura + datos" -ForegroundColor Cyan

# Mostrar tamaño de archivos
Get-ChildItem "database_export\*.sql" | ForEach-Object {
    Write-Host "$($_.Name): $([math]::Round($_.Length/1KB, 2)) KB" -ForegroundColor White
}
