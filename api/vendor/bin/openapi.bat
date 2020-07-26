@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../zircote/swagger-php/bin/openapi
php "%BIN_TARGET%" %*
