#!/bin/bash
set -e

# Synchroniser les fichiers d'origine vers /config/www/organizr (écrase les fichiers modifiés sauf /data)
echo "Synchronisation des fichiers d'origine vers /config/www/organizr (hors dossier data)..."
mkdir -p /config/www/organizr
rsync -a --delete --exclude='.git' --exclude='data' /usr/src/app/ /config/www/organizr/
chown -R www-data:www-data /config/www/organizr

# Créer le dossier /var/www/html si absent
mkdir -p /var/www/html
# Créer également le dossier parent requis pour le lien symbolique
mkdir -p /var/www/html/www

# Supprimer /var/www/html/www/organizr si ce n'est pas un lien symbolique
if [ ! -L /var/www/html/www/organizr ]; then
    rm -rf /var/www/html/www/organizr
    ln -s /config/www/organizr /var/www/html/www/organizr
fi

exec apache2-foreground
