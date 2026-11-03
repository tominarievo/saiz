FROM php:8.4-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libonig-dev \
        unzip \
        curl \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j2 mbstring pcntl
RUN docker-php-ext-install -j2 pdo_mysql \
    && a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
