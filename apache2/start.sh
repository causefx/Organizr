#!/usr/bin/env bash
chown www-data: /config /var/www/Organizr /var/www/Organizr/tmp

for dir in data; do
    if [ ! -d /config/${dir} ]; then
        install --owner www-data --group www-data --directory /config/${dir}
    fi

    if [ -d /var/www/Organizr/${dir} ]; then
        cp --recursive --update --force /var/www/Organizr/${dir}/. /config/${dir}
        rm --recursive --force /var/www/Organizr/${dir}
    fi

    ln --symbolic --force /config/${dir} /var/www/Organizr/${dir}
done

if [ ! -d /config/httpd/ssl ]; then
    install --directory /config/httpd/ssl
    ln --symbolic --force /etc/ssl/certs/ssl-cert-snakeoil.pem /config/httpd/ssl/organizr.crt
    ln --symbolic --force /etc/ssl/private/ssl-cert-snakeoil.key /config/httpd/ssl/organizr.key
fi

pidfile=/var/run/apache2/apache2.pid

if [ -f ${pidfile} ]; then
    pid=$(cat ${pidfile})

    if [ ! -d /proc/${pid} ] || [ -d /proc/${pid} -a $(basename $(readlink /proc/${pid}/exe)) != 'apache2' ]; then
        rm ${pidfile}
    fi
fi

exec $(which apache2ctl) \
    -D FOREGROUND \
    -D ${HTTPD_SSL:-SSL} \
    -D ${HTTPD_REDIRECT:-REDIRECT}
