FROM php:8.4-fpm

ENV APP_ENV=dev \
    APP_DEBUG=1 \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    zlib1g-dev \
    unzip \
 && docker-php-ext-install -j"$(nproc)" intl opcache pdo pdo_mysql zip \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

#COPY composer.json composer.lock* symfony.lock* ./

#RUN composer install --prefer-dist --no-scripts --no-progress --ansi

#COPY . .

#RUN composer dump-autoload --optimize && \
#    mkdir -p var/log && \
#    chown -R www-data:www-data var

EXPOSE 9000

CMD ["php-fpm"]
