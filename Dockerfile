ARG PHP_VERSION=8.2.7-apache-bullseye
ARG PRIVATE_REGISTRY_PULL=private.nexus-regs.docker:8086/
FROM ${PRIVATE_REGISTRY_PULL}php:${PHP_VERSION} as php_laravel

# install dependencies for laravel 8
RUN apt-get update && apt-get install -y \
  curl \
  git \
  libicu-dev \
  libpq-dev \
  libmcrypt-dev \
  openssl \
  unzip \
  vim \
  zip \
  zlib1g-dev \
  libpng-dev \
  mariadb-client \
  libzip-dev && \
rm -r /var/lib/apt/lists/*

# install extension for lumen
RUN pecl install mcrypt-1.0.6 && \
  docker-php-ext-install fileinfo exif pcntl bcmath gd mysqli pdo pdo_mysql && \
  docker-php-ext-enable mcrypt && \
  a2enmod rewrite

# install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer

FROM php_laravel as executeable

ENV APP_SOURCE /var/www/php
ENV APP_DEBUG=false
ENV APP_URL=""
ENV APP_ENV=production

# Set working directory
WORKDIR $APP_SOURCE

# set DocumentRoot to lavavel framework uploaded
RUN sed -i "s|DocumentRoot /var/www/html|DocumentRoot ${APP_SOURCE}/public|g" /etc/apache2/sites-enabled/000-default.conf

# copy source laravel
COPY . .

# give full access
RUN mkdir -p public/storage && \
    chmod -R 777 storage/* && \
    chmod -R 777 public/storage

# install dependency laravel
RUN php -r "file_exists('.env') || copy('.env.example', '.env');" && \
    composer install --no-interaction --optimize-autoloader --no-dev

VOLUME ${APP_SOURCE}/storage

# expose port default 80
EXPOSE 80/tcp
