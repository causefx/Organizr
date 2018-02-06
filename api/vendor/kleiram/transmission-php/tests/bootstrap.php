<?php
if (!$loader = @include __DIR__.'/../vendor/autoload.php') {
    echo <<<EOM
    Install dependencies using Composer:

        curl -s https://getcomposer.org/installer | php
        php composer.phar install

EOM;

    exit(1);
}

$loader->add('Transmission\Mock', __DIR__);
$loader->add('Transmission\Tests', __DIR__);
