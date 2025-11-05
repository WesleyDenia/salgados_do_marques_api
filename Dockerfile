FROM php:8.3-fpm

# Dependências do Laravel
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libjpeg-dev libfreetype6-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql gd

WORKDIR /var/www/html

COPY . .

COPY docker/php/conf.d/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

CMD ["php-fpm"]
