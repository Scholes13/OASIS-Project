# ==============================================
# Stage 1: Build frontend assets
# ==============================================
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources/ resources/

RUN npm run build

# ==============================================
# Stage 2: Install PHP dependencies
# ==============================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
COPY database/ database/

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# ==============================================
# Stage 3: Production image
# ==============================================
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        zip \
        bcmath \
        pcntl \
        mbstring \
        intl \
        opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Cleanup build deps
RUN apk del $PHPIZE_DEPS linux-headers

# Create log directory
RUN mkdir -p /var/log/php && chown www-data:www-data /var/log/php

# Copy PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY --chown=www-data:www-data . .

# Copy vendor from stage 2
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor

# Copy built frontend from stage 1
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build

# Create storage directories
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Generate optimized autoload
RUN php artisan package:discover --ansi || true

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD php artisan --version || exit 1

EXPOSE 9000

USER www-data

CMD ["php-fpm"]
