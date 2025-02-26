FROM php:8.4-fpm

RUN apt update \
    && apt install -y zlib1g-dev g++ git libicu-dev zip libzip-dev libpq-dev zip  libpng-dev libjpeg-dev \
    && docker-php-ext-install intl opcache pdo pdo_pgsql pgsql \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

RUN docker-php-ext-configure gd \
  --enable-gd \
  --with-jpeg

RUN pecl install redis \
    && docker-php-ext-enable redis

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/20-xdebug.ini \
    && echo "xdebug.log=/var/www/xdebug.log" >> /usr/local/etc/php/conf.d/20-xdebug.ini \
    && echo "xdebug.discover_client_host=true" >> /usr/local/etc/php/conf.d/20-xdebug.ini \
    && echo "xdebug.remote_cookie_expire_time=3600" >> /usr/local/etc/php/conf.d/20-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/20-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/20-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/20-xdebug.ini


