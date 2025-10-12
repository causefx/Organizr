#!/bin/bash
set -e

# Initialiser /config/www/organizr si vide
if [ ! -d /config/www/organizr ] || [ -z "$(ls -A /config/www/organizr 2>/dev/null)" ]; then
    echo "Initialisation de /config/www/organizr avec les fichiers d'origine..."
    mkdir -p /config/www/organizr
    cp -r /usr/src/app/. /config/www/organizr/
    chown -R www-data:www-data /config/www/organizr
fi

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
