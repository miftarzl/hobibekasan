FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        zip \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo_mysql gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html
WORKDIR /var/www/html
EXPOSE 80
CMD ["sh", "-c", "mkdir -p /var/www/html/assets/img/products /var/www/html/assets/img/category /var/www/html/assets/img/profiles /var/www/html/assets/uploads && chmod -R 777 /var/www/html/assets && apache2-foreground"]
