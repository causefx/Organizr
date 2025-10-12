FROM php:8.2-apache-bookworm


ENV PHP_MEMORY_LIMIT=256M \
    UPLOAD_MAX_FILESIZE=50M \
    POST_MAX_SIZE=50M \
    TZ=UTC


RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
    libsqlite3-0 libsqlite3-dev \
    libldap2-dev \
    libzip-dev \
    zlib1g-dev \
    libonig-dev \
    ca-certificates curl \
    ; \
    rm -rf /var/lib/apt/lists/*; \
    deb_multiarch="$(dpkg-architecture --query DEB_BUILD_MULTIARCH)"; \
    docker-php-source extract; \
    docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql; \
    docker-php-ext-install -j"$(nproc)" mbstring opcache zip; \
    docker-php-ext-configure ldap --with-libdir="lib/${deb_multiarch}"; \
    docker-php-ext-install -j"$(nproc)" ldap; \
    docker-php-source delete; \
    a2enmod rewrite headers


RUN printf "<Directory /var/www/html>\n\
    AllowOverride All\n\
</Directory>\n" > /etc/apache2/conf-available/overrides.conf \
    && a2enconf overrides


RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/www/organizr|g' /etc/apache2/sites-available/000-default.conf


RUN { \
      echo "memory_limit=${PHP_MEMORY_LIMIT}"; \
      echo "upload_max_filesize=${UPLOAD_MAX_FILESIZE}"; \
      echo "post_max_size=${POST_MAX_SIZE}"; \
      echo "date.timezone=${TZ}"; \
      echo "opcache.enable=1"; \
      echo "opcache.validate_timestamps=1"; \
      echo "opcache.revalidate_freq=2"; \
    } > /usr/local/etc/php/conf.d/zz-organizr.ini
RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_NOTICE" > /usr/local/etc/php/conf.d/zz-error_reporting.ini

WORKDIR /var/www/html
COPY . /var/www/html
COPY . /usr/src/app


RUN chown -R www-data:www-data /var/www/html


VOLUME ["/config"]


COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -fsS http://localhost/ || exit 1

CMD ["docker-entrypoint.sh"]
