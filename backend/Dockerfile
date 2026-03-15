FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    zlib1g-dev \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql intl zip opcache gd \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /srv/app

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock /srv/app/
RUN composer install --no-interaction --prefer-dist --no-scripts --no-progress

# Copy the rest of the application
COPY . /srv/app

# Ensure writable directories exist
RUN mkdir -p var/cache var/log && chown -R www-data:www-data var

EXPOSE 9000

CMD ["php-fpm"]
