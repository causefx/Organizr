<?php

// PLUGIN INFORMATION
$GLOBALS['plugins'][]['SpeedTest'] = array( // Plugin Name
    'name'=>'SpeedTest', // Plugin Name
    'author'=>'CauseFX', // Who wrote the plugin
    'category'=>'Utilities', // One to Two Word Description
    'link'=>'https://github.com/PHPMailer/PHPMailer', // Link to plugin info
    'license'=>'personal,business', // License Type use , for multiple
    //'fileName'=>'php-mailer.php',
    //'configFile'=>'php-mailer.php',
    //'apiFile'=>'php-mailer.php',
    'idPrefix'=>'SPEEDTEST', // html element id prefix
    'configPrefix'=>'SPEEDTEST', // config file prefix for array items without the hypen
    'version'=>'1.0.0', // SemVer of plugin
    'image'=>'plugins/images/speedtest.png', // 1:1 non transparent image for plugin
    'settings'=>true, // does plugin need a settings page? true or false
    'homepage'=>false // Is plugin for use on homepage? true or false
);
// INCLUDE/REQUIRE FILES

// PLUGIN FUNCTIONS

/* GET PHPMAILER SETTINGS */
function speedTestGetSettings()
{
    return array(
        'Options' => array(
            array(
                'type' => 'select',
                'name' => 'SPEEDTEST-Auth-include',
                'label' => 'Minimum Authentication',
                'value' => $GLOBALS['SPEEDTEST-Auth-include'],
                'options' => groupSelect()
            )
        )
    );
}
