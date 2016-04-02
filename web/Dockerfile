FROM php:apache

RUN apt-get update && apt-get install -y \
        libssl-dev
RUN pecl install mongodb && docker-php-ext-enable mongodb

RUN a2enmod rewrite

RUN curl -sL https://deb.nodesource.com/setup_5.x | bash -
RUN apt-get install -y nodejs git
RUN npm install bower -g
COPY admin/assets/bower.json /var/www/html/admin/assets/bower.json
WORKDIR /var/www/html/admin/assets
RUN bower install --allow-root
RUN npm uninstall bower
RUN apt-get purge -y nodejs git && apt-get autoremove -y && apt-get clean all

COPY . /var/www/html/
WORKDIR /var/www/html

ENV MONGO_HOSTNAME mongodb
ENV MONGO_HOSTPORT 27017