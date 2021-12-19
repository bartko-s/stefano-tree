FROM php:8.1-cli

RUN apt-get update

RUN docker-php-ext-install -j$(nproc) pdo_mysql

RUN apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo_pgsql pgsql

RUN  apt-get install libzip-dev -y \
    && docker-php-ext-install zip

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN cd ~ \
    && curl -sS https://getcomposer.org/installer -o composer-setup.php \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer

COPY php.ini /usr/local/etc/php/

RUN mkdir /var/src

WORKDIR /var/src/
