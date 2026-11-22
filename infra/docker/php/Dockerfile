
FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    libsqlite3-dev \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        bcmath \
        intl \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 \
    /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/backend

EXPOSE 9000

CMD ["php-fpm"]
