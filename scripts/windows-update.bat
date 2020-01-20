@ECHO off
SET ou_v=v2.6
TITLE Organizr v2 Updater
COLOR 03
ECHO      ___           ___
ECHO     /  /\         /  /\           ___
ECHO    /  /::\       /  /:/_         /__/\
ECHO   /  /:/\:\     /  /:/ /\        \__\:\
ECHO  /  /:/  \:\   /  /:/ /:/_       /  /::\
ECHO /__/:/ \__\:\ /__/:/ /:/ /\   __/  /:/\/
ECHO \  \:\ /  /:/ \  \:\/:/ /:/  /__/\/:/
ECHO  \  \:\  /:/   \  \::/ /:/   \  \::/
ECHO   \  \:\/:/     \  \:\/:/     \  \:\
ECHO    \  \::/       \  \::/       \__\/
ECHO     \__\/         \__\/             ~~ %ou_v%
ECHO.
ECHO Organizr v2 Updater
ECHO.
@ECHO Started: %date% %time%
ECHO Running from: %~dp0
ECHO.
CD /d %~dp0

IF "%*"=="" GOTO :master_vars
IF "%*"=="-m" GOTO :master_vars
IF "%*"=="-d" GOTO :dev_vars

:master_vars
ECHO Master Branch
SET branch=Master
SET org_url=https://github.com/causefx/Organizr/archive/v2-master.zip
SET orgzip_extract_name=Organizr-2-master
GOTO :STARTUPDATE

:dev_vars
ECHO Dev Branch
SET branch=Dev
SET org_url=https://github.com/causefx/Organizr/archive/v2-develop.zip
SET orgzip_extract_name=Organizr-2-develop
GOTO :STARTUPDATE

:STARTUPDATE
REM CD /d %~dp0
ECHO.
IF NOT EXIST "%~dp0organizr" GOTO UPDATE
ECHO ##############################
ECHO Cleanup in progress
ECHO ##############################
RMDIR /s /q %~dp0organizr
ECHO.
ECHO Deleted
ECHO.

:UPDATE
ECHO #############################
ECHO Updating OrganizrV2-(%branch%)
ECHO #############################
ECHO.
ECHO.
ECHO Download In Progress...
powershell -command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $clnt = new-object System.Net.WebClient; $clnt.DownloadFile(\"%org_url%\", \"organizr.zip\")"
ECHO.

ECHO Extraction In Progress...
ECHO.
powershell.exe -nologo -noprofile -command "& { Add-Type -A 'System.IO.Compression.FileSystem'; [IO.Compression.ZipFile]::ExtractToDirectory('organizr.zip', '.'); }"

ECHO Applying Update...
ECHO.
MOVE %~dp0%orgzip_extract_name% organizr >nul 2>&1
DEL /s /q %~dp0organizr.zip
ROBOCOPY organizr ..\ /E /MOVE /NFL /NDL /NJH /nc /ns /np

IF NOT EXIST "%~dp0organizr" GOTO END
ECHO ##############################
ECHO Cleanup in progress
ECHO ##############################
RMDIR /s /q %~dp0organizr
ECHO.
ECHO Deleted

:END
ECHO.
ECHO %branch% Update Completed...

ECHO.
@ECHO ENDED: %date% %time%
ECHO.
REM pause