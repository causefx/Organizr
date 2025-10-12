#!/bin/bash
set -e

# Synchroniser tout le code dans /config/www/organizr sauf le dossier data
mkdir -p /config/www/organizr
rsync -a --delete --exclude='data' /usr/src/app/ /config/www/organizr/
mkdir -p /config/www/organizr/data

# Appliquer les droits sur tous les dossiers parents et le code
chown -R www-data:www-data /config
chmod 755 /config
chmod 755 /config/www
chmod -R 755 /config/www/organizr

exec apache2-foreground
