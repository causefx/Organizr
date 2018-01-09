<?php

// ===================================
// Organizr Version
$GLOBALS['installedVersion'] = '2.0.0-alpha.110';
// ===================================
// Set GLOBALS from config file
$GLOBALS['userConfigPath'] = dirname(__DIR__,1).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php';
$GLOBALS['defaultConfigPath'] = dirname(__DIR__,1).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'default.php';
$GLOBALS['currentTime'] = gmdate("Y-m-d\TH:i:s\Z");
