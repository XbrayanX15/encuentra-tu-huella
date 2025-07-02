FROM php:8.1-cli-alpine

# Instalar dependencias y extensiones de PHP
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_pgsql mbstring

# Establecer directorio de trabajo
WORKDIR /app

# Copiar todos los archivos
COPY . .

# Crear directorios necesarios con permisos
RUN mkdir -p uploads logs \
    && chmod 755 uploads logs \
    && chown -R www-data:www-data uploads logs

# Exponer puerto
EXPOSE 8080

# Comando de inicio con PHP built-in server
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t ."]
