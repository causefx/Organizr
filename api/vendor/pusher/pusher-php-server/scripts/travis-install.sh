#!/usr/bin/env bash

set -e

curl -sSfL -o ~/.phpenv/versions/hhvm/bin/phpunit https://phar.phpunit.de/phpunit-7.5.9.phar
composer install --no-interaction --prefer-source

if [ $INSTALL_LIBSODIUM = true ]; then
  sudo add-apt-repository ppa:ondrej/php -y
  sudo apt-get update && sudo apt-get install libsodium-dev -y
  pecl install libsodium-2.0.11
fi
