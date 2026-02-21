FROM php:8.1-fpm

# System deps
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Composer (for PHPMailer if you want to use composer install)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# (Optional) If you have a composer.json at root and want auto install:
# COPY composer.json composer.lock ./
# RUN composer install --no-dev --prefer-dist --no-interaction || true

# Permissions (safe baseline; adjust if you write uploads/cache)
RUN chown -R www-data:www-data /var/www/html