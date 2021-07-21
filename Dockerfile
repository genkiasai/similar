FROM php:8.0.7-apache

RUN apt-get update \
    && apt-get install -y zlib1g-dev libzip-dev poppler-utils poppler-data \
    && docker-php-ext-install zip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY ./php.ini /usr/local/etc/php/php.ini

CMD bash -c "chmod 777 /var/www/html && /usr/local/bin/apache2-foreground"
