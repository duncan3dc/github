ARG PHP_VERSION=8.0
FROM php:${PHP_VERSION}-cli

ARG COVERAGE
RUN if [ "$COVERAGE" = "pcov" ]; then pecl install pcov && docker-php-ext-enable pcov; fi

RUN apt update && apt install -y git zip
COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY . /app
WORKDIR /app

# Install the library dependencies
ARG DEPENDENCIES
RUN if [ "$DEPENDENCIES" = "update" ]; then composer update --working-dir=/app --no-dev --prefer-dist --optimize-autoloader; fi
