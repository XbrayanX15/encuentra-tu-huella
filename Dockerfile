FROM php:8.1-cli

# Instalar extensiones necesarias
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_pgsql mbstring

# Copiar archivos del proyecto
COPY . /app
WORKDIR /app

# Crear directorios necesarios
RUN mkdir -p uploads logs && chmod 755 uploads logs

# Exponer puerto
EXPOSE $PORT

# Comando de inicio
CMD php -S 0.0.0.0:$PORT -t .
