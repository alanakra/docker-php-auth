FROM php:8.4-fpm
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libzip-dev zip unzip git \
  && docker-php-ext-install pdo pdo_mysql mbstring zip gd \
  && rm -rf /var/lib/apt/lists/*


COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer require vlucas/phpdotenv

WORKDIR /var/www/html
COPY . /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
