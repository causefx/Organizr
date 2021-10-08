FROM php:7.3.2-cli-alpine3.9

RUN set -x && \
  apk update && \
  apk add --no-cache libxml2 libxml2-dev curl curl-dev autoconf $PHPIZE_DEPS && \
  docker-php-ext-install mysqli pdo pdo_mysql xml mbstring curl session tokenizer json && \
  pecl install xdebug-2.7.0beta1 && \
  docker-php-ext-enable xdebug && \
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer && \
  composer global require hirak/prestissimo

COPY ./docker/config/php.ini /usr/local/etc/php/php.ini
COPY ./docker/config/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

ADD . /app/php-json-logger/
WORKDIR /app/php-json-logger

ENTRYPOINT ["/bin/sh", "-c", "while true; do echo hello world; sleep 1; done"]
