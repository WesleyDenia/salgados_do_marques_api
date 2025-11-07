FROM php:8.3-fpm

# Instala dependências PHP
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo pdo_mysql mysqli gd zip \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Copia o código fonte
COPY . .

# Instala dependências Laravel
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copia script para carregar secrets antes do PHP-FPM
COPY docker/load-secrets.sh /usr/local/bin/load-secrets.sh
RUN chmod +x /usr/local/bin/load-secrets.sh

# Ajusta permissões de storage/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Usa script de entrypoint
ENTRYPOINT ["sh", "/usr/local/bin/load-secrets.sh"]

CMD ["php-fpm"]
