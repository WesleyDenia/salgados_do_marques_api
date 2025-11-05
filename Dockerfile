FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo pdo_mysql gd zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Etapa 1: cache do composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Etapa 2: copiar o código
COPY . .

# Etapa 3: agora roda os scripts que dependem do artisan
RUN composer run-script post-autoload-dump

RUN chown -R www-data:www-data storage bootstrap/cache

CMD ["php-fpm"]
