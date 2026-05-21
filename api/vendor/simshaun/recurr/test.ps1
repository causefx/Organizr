#$env:XDEBUG_MODE = "debug"
#$env:XDEBUG_SESSION = 1

# If no arguments, run all tests.
if (-not $args)
{
    ./vendor/bin/phpunit --stop-on-failure -c phpunit.xml.dist
}
else
{
    ./vendor/bin/phpunit --stop-on-failure -c phpunit.xml.dist --filter $args[0]
}
