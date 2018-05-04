<?php

function upgradeCheck()
{
    $updateDB = false;
    $compare = new Composer\Semver\Comparator;
    $oldVer = $GLOBALS['installedVersion'];
    // Upgrade check start for version below
    $versionCheck = '2.0.0-alpha-100';
    if ($compare->lessThan($oldVer, $versionCheck)) {
        $updateDB = true;
        $oldVer = $versionCheck;
    }
    // End Upgrade check start for version above
    if ($updateDB == true) {
        //return 'Upgraded Needed - Current Version '.$oldVer.' - New Version: '.$versionCheck;
        // Upgrade database to latest version
        updateDB($GLOBALS['dbLocation'],$GLOBALS['dbName'],$oldVer);
    }
    return true;
}
