# ==============================================================================
# Multi-stage Dockerfile for pdf2word (All-in-one PDF Workspace)
# Optimized for Production with LibreOffice, Ghostscript, and Thai Tesseract OCR
# ==============================================================================

# ------------------------------------------------------------------------------
# Stage 1: Build Frontend Assets
# ------------------------------------------------------------------------------
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ------------------------------------------------------------------------------
# Stage 2: Production PHP Application
# ------------------------------------------------------------------------------
FROM php:8.4-fpm-bookworm AS production

ENV DEBIAN_FRONTEND=noninteractive
WORKDIR /var/www/html

# Install System Dependencies & PDF Processing Engines
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    sqlite3 \
    libsqlite3-dev \
    nginx \
    supervisor \
    # PDF & Office Processing Tools
    libreoffice-nogui \
    ghostscript \
    # Thai & English OCR
    tesseract-ocr \
    tesseract-ocr-tha \
    tesseract-ocr-eng \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        zip \
        gd \
        xml \
        bcmath \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy Project Files
COPY . /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

# Install PHP Dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy Docker Configuration Files
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
