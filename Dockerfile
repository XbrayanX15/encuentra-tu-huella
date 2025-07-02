FROM php:8.1-cli

# Instalar dependencias del sistema para PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar solo las extensiones necesarias
RUN docker-php-ext-install pdo pdo_pgsql

# Establecer directorio de trabajo
WORKDIR /app

# Copiar todos los archivos
COPY . .

# Crear directorios necesarios con permisos
RUN mkdir -p uploads logs \
    && chmod 755 uploads logs \
    && ls -la config/ \
    && ls -la includes/

# Exponer puerto
EXPOSE 8080

# Comando de inicio con PHP built-in server
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t ."]
