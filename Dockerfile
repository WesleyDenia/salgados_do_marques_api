FROM php:8.3-fpm

# 🔹 Instalar dependências do sistema e extensões PHP
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo pdo_mysql gd zip

# 🔹 Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# 🔹 Copiar apenas arquivos necessários para build do Composer
COPY composer.json composer.lock ./

# 🔹 Instalar dependências do Laravel (sem dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 🔹 Copiar todo o código da aplicação
COPY . .

# 🔹 Garantir permissões corretas
RUN chown -R www-data:www-data storage bootstrap/cache

# 🔹 Otimizar o Laravel
RUN php artisan config:clear || true && php artisan cache:clear || true

CMD ["php-fpm"]
