<?php

spl_autoload_register(function ($class) {
    $namespace = 'ParagonIE_Sodium_';
    // Does the class use the namespace prefix?
    $len = strlen($namespace);
    if (strncmp($namespace, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return false;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = dirname(__FILE__) . '/src/' . str_replace('_', '/', $relative_class) . '.php';
    // if the file exists, require it
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});

/* Explicitly, always load the Compat class: */
if (!class_exists('ParagonIE_Sodium_Compat', false)) {
    require_once dirname(__FILE__) . '/src/Compat.php';
}
if (!class_exists('ParagonIE_Sodium_File', false)) {
    require_once dirname(__FILE__) . '/src/File.php';
}

if (!class_exists('SodiumException', false)) {
    require_once dirname(__FILE__) . '/src/SodiumException.php';
}

if (!defined('SODIUM_CRYPTO_AEAD_AEGIS128L_KEYBYTES')) {
    require_once dirname(__FILE__) . '/lib/php84compat_const.php';
}

if (!extension_loaded('sodium')) {
    if (!defined('SODIUM_CRYPTO_SCALARMULT_BYTES')) {
        require_once dirname(__FILE__) . '/lib/php72compat_const.php';
    }
    assert(class_exists('ParagonIE_Sodium_Compat'), 'Possible filesystem/autoloader bug?');
    require_once(dirname(__FILE__) . '/lib/php72compat.php');
} elseif (!function_exists('sodium_crypto_stream_xchacha20_xor')) {
    // Older versions of {PHP, ext/sodium} will not define these
    require_once(dirname(__FILE__) . '/lib/php72compat.php');
}
if (PHP_VERSION_ID < 80400 || !extension_loaded('sodium')) {
    require_once dirname(__FILE__) . '/lib/php84compat.php';
}
require_once(dirname(__FILE__) . '/lib/stream-xchacha20.php');
require_once(dirname(__FILE__) . '/lib/ristretto255.php');
