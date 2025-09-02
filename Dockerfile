# Используем официальный PHP образ
FROM php:8.2-fpm

# Устанавливаем зависимости для Symfony и расширений PHP
RUN apt-get update && apt-get install -y \
    git unzip curl wget gnupg2 libicu-dev libpq-dev libzip-dev zlib1g-dev libpng-dev libjpeg-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

# Устанавливаем Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Устанавливаем Symfony CLI
RUN wget https://get.symfony.com/cli/installer -O - | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Настраиваем рабочую директорию
WORKDIR /var/www/html

# Устанавливаем права
RUN chown -R www-data:www-data /var/www/html

