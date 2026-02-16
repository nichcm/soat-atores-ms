FROM php:8.4-fpm-alpine3.22

WORKDIR /var/www/html

RUN apk update && apk upgrade

RUN apk add --no-cache \
    curl \
    zip \
    unzip \
    linux-headers \
    # supervisor \
    autoconf \
    g++ \
    make \
    libpq-dev \
    # postgresql-dev \
    # postgresql-libs \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=coverage,develop" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-enable pdo_pgsql \
    # && apk del autoconf g++ make linux-headers postgresql-dev \
    && rm -rf /var/lib/apk/lists/* \
    && rm -rf /var/cache/apk/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./containers/php/setup.sh /usr/local/bin/setup.sh

# COPY ./containers/php/supervisord.conf /etc/supervisord.conf

RUN chmod +x /usr/local/bin/setup.sh

COPY ./application .

ENTRYPOINT ["/usr/local/bin/setup.sh"]
