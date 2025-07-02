FROM php:8.1-cli

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Configurar e instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_pgsql mbstring

# Establecer directorio de trabajo
WORKDIR /app

# Copiar todos los archivos
COPY . .

# Crear directorios necesarios con permisos
RUN mkdir -p uploads logs \
    && chmod 755 uploads logs

# Exponer puerto
EXPOSE 8080

# Comando de inicio con PHP built-in server
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t ."]
