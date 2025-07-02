FROM php:8.1-cli

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP una por una para mejor debugging
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_pgsql  
RUN docker-php-ext-install mbstring
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

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
