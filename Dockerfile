# ----------------------------------------------------------
# Stage 1 — PHP base com extensões do Laravel
# ----------------------------------------------------------
FROM php:8.3-fpm

# Instala dependências do sistema e extensões PHP
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd

# ----------------------------------------------------------
# Stage 2 — Composer global
# ----------------------------------------------------------
# Instala o Composer globalmente (última versão estável)
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# ----------------------------------------------------------
# Stage 3 — Setup do Laravel
# ----------------------------------------------------------
WORKDIR /var/www/html

# Copia apenas arquivos de definição de dependências primeiro
COPY composer.json composer.lock ./

# Instala dependências do Laravel sem cache de dev
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copia o restante do código
COPY . .

# Copia configurações extras do PHP (opcional)
COPY docker/php/conf.d/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Define permissões seguras
RUN chown -R www-data:www-data storage bootstrap/cache

# Porta exposta (para depuração local)
EXPOSE 9000

CMD ["php-fpm"]

