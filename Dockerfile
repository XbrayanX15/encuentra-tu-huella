FROM php:8.1-cli

# Instalar extensiones necesarias
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_pgsql mbstring

# Establecer directorio de trabajo
WORKDIR /app

# Copiar archivos del proyecto
COPY . .

# Crear directorios necesarios
RUN mkdir -p uploads logs && chmod 755 uploads logs

# Exponer puerto (Render usa variable $PORT)
EXPOSE 8080

# Comando de inicio usando variable de entorno PORT
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t ."]
