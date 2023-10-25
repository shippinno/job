FROM php:8.2-fpm-alpine

RUN apk update && apk upgrade && \
    apk add --no-cache \
    git curl libmcrypt libmcrypt-dev openssh-client \
    libxml2-dev freetype-dev libpng-dev libjpeg-turbo-dev g++ make openssl-dev autoconf

RUN apk add tzdata && \
    cp /usr/share/zoneinfo/Asia/Tokyo /etc/localtime && \
    apk del tzdata

RUN apk add --update linux-headers
RUN pecl install xdebug-3.2.2
RUN docker-php-ext-enable xdebug

RUN docker-php-ext-install \
    pcntl

RUN curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/bin/ --filename=composer \

RUN mkdir /code
WORKDIR /code