FROM php:8.0-alpine

ARG UID

# Create a dev user with same UID as the user on the host machine for easy permission management.
RUN adduser -D -u $UID -h /home/dev dev

# Install dependencies.
RUN apk add --no-cache git openssh-client openssh g++ make autoconf icu-dev

# Install PHP extensions.
RUN docker-php-source extract \
    && { \
        echo 'xdebug.mode=debug'; \
        echo 'xdebug.discover_client_host=0'; \
        echo 'xdebug.max_nesting_level=1200'; \
        echo 'xdebug.idekey = PHPSTORM'; \
    } > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-install -j$(getconf _NPROCESSORS_ONLN) intl \
    && docker-php-source delete \
    && rm -rf /tmp/*

# Set timezone.
RUN echo "UTC" > /etc/timezone

# Install composer globally.
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

COPY php.ini /usr/local/etc/php/php.ini

USER dev

# Update PATH to include project binaries
ENV PATH="/src/vendor/bin:${PATH}"

WORKDIR /src
