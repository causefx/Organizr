#!/usr/bin/env bash

#export XDEBUG_MODE=debug XDEBUG_SESSION=1

# If no arguments, run all tests.
if [ -z "$1" ]
then
  ./vendor/bin/phpunit --stop-on-failure -c phpunit.xml.dist
else
  ./vendor/bin/phpunit --stop-on-failure -c phpunit.xml.dist --filter "$1"
fi
