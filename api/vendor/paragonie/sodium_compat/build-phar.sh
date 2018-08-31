#!/usr/bin/env bash

curl -LSs https://box-project.github.io/box2/installer.php | php
mkdir ~/box
mv box.phar ~/box/box
PATH=$PATH:~/box/ make -C dist/ build-phar
# PATH=$PATH:~/box/ make -C dist/ sign-phar

