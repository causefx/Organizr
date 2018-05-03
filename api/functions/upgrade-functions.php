<?php

function upgradeCheck()
{
    $updateDB = false;
    $compare = new Composer\Semver\Comparator;
    $config = loadConfig();
    $oldVer = $config['configVersion'];
    // Upgrade check start for version below
    $versionCheck = '2.0.0-alpha-100';
    if (isset($config['dbLocation']) && (!isset($config['configVersion']) || $compare->lessThan($oldVer, $versionCheck))) {
        $updateDB = true;
        $oldVer = $versionCheck;
    }
    // End Upgrade check start for version above
    if ($updateDB == true) {
        //return 'Upgraded Needed - Current Version '.$oldVer.' - New Version: '.$versionCheck;
        // Upgrade database to latest version
        unset($config);
        updateDB($GLOBALS['dbLocation'],$GLOBALS['dbName'],$oldVer);
    }
    return true;
}
