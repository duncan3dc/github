ARG PHP_VERSION=7.2
FROM php:${PHP_VERSION}-cli

ARG COVERAGE
RUN if [ "$COVERAGE" = "pcov" ]; then pecl install pcov && docker-php-ext-enable pcov; fi

# Install composer to manage PHP dependencies
RUN apt-get update && apt-get install -y git zip
RUN curl https://getcomposer.org/download/1.9.0/composer.phar -o /usr/local/sbin/composer
RUN chmod +x /usr/local/sbin/composer
RUN composer self-update

COPY . /app
WORKDIR /app

# Install the library dependencies
ARG DEPENDENCIES
RUN if [ "$DEPENDENCIES" = "update" ]; then composer update --working-dir=/app --no-dev --prefer-dist --optimize-autoloader; fi
