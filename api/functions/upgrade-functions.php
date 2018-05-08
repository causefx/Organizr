<?php
function upgradeCheck()
{
	$updateDB = false;
	$updateSuccess = true;
	$compare = new Composer\Semver\Comparator;
	$oldVer = $GLOBALS['configVersion'];
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
		$updateSuccess = (updateDB($GLOBALS['dbLocation'], $GLOBALS['dbName'], $oldVer)) ? true : false;
	}
	// Update config.php version if different to the installed version
	if ($updateSuccess && $GLOBALS['installedVersion'] !== $GLOBALS['configVersion']) {
		updateConfig(array('apply_CONFIG_VERSION' => $GLOBALS['installedVersion']));
	}
	if ($updateSuccess == false) {
		die('Database update failed - Please manually check logs and fix - Then reload this page');
	}
	return true;
}
