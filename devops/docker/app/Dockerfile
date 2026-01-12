FROM php:8.4-fpm

ENV APP_ENV=dev \
    APP_DEBUG=1 \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Install Node.js 20.x
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash -

# Install system dependencies + Node.js
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    zlib1g-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libmagickwand-dev \
    ghostscript \
    unzip \
    nodejs \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" intl opcache pdo pdo_mysql zip gd \
 && pecl install imagick \
 && docker-php-ext-enable imagick \
 && npm install -g npm@latest \
 && rm -rf /var/lib/apt/lists/*

# Copy ImageMagick policy configuration
COPY devops/docker/app/imagemagick-policy.xml /etc/ImageMagick-6/policy.xml

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

#COPY composer.json composer.lock* symfony.lock* ./

#RUN composer install --prefer-dist --no-scripts --no-progress --ansi

#COPY . .

#RUN composer dump-autoload --optimize && \
#    mkdir -p var/log && \
#    chown -R www-data:www-data var

EXPOSE 9000

CMD ["php-fpm"]
