FROM php:apache

RUN apt-get update && apt-get install -y \
        libssl-dev
RUN pecl install mongodb && docker-php-ext-enable mongodb

RUN a2enmod rewrite
