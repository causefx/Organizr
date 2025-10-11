FROM php:8.2-apache-bookworm

# Config par défaut
ENV PHP_MEMORY_LIMIT=256M \
    UPLOAD_MAX_FILESIZE=50M \
    POST_MAX_SIZE=50M \
    TZ=UTC

# Paquets système et extensions PHP nécessaires
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
    # L’extension sqlite3 est déjà incluse dans l’image officielle, inutile de la recompiler
    docker-php-ext-configure ldap --with-libdir="lib/${deb_multiarch}"; \
    docker-php-ext-install -j"$(nproc)" ldap; \
    docker-php-source delete; \
    a2enmod rewrite headers

# Autoriser .htaccess (rewrite)
RUN printf "<Directory /var/www/html>\n\
    AllowOverride All\n\
</Directory>\n" > /etc/apache2/conf-available/overrides.conf \
    && a2enconf overrides

# Réglages PHP
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

# Déployer l’app
WORKDIR /var/www/html
COPY . /var/www/html

# Droits runtime
RUN chown -R www-data:www-data /var/www/html

# Persistance des données/config (montage recommandé)
VOLUME ["/var/www/html/data"]

# (Optionnel) Composer pour reconstruire vendor
# FROM composer:2 AS vendor
# WORKDIR /app
# COPY api/composer.json api/composer.lock ./api/
# RUN --mount=type=cache,target=/tmp/composer-cache \
#     cd api && composer install --no-dev --prefer-dist --no-interaction --no-progress
# FROM php:8.2-apache AS final
# # répéter l’installation des extensions ci-dessus
# WORKDIR /var/www/html
# COPY . /var/www/html
# COPY --from=vendor /app/api/vendor /var/www/html/api/vendor
# RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -fsS http://localhost/ || exit 1

CMD ["apache2-foreground"]
