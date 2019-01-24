<?php
function upgradeCheck()
{
	if (file_exists('config' . DIRECTORY_SEPARATOR . 'config.php')) {
		$tempLock = $GLOBALS['dbLocation'] . 'DBLOCK.txt';
		$updateComplete = $GLOBALS['dbLocation'] . 'completed.txt';
		$cleanup = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "upgrade" . DIRECTORY_SEPARATOR;
		if (file_exists($updateComplete)) {
			@unlink($updateComplete);
			@rrmdir($cleanup);
		}
		if (file_exists($tempLock)) {
			die('upgrading');
		}
		$updateDB = false;
		$updateSuccess = true;
		$compare = new Composer\Semver\Comparator;
		$oldVer = $GLOBALS['configVersion'];
		// Upgrade check start for version below
		$versionCheck = '2.0.0-beta-200';
		if ($compare->lessThan($oldVer, $versionCheck)) {
			$updateDB = true;
			$oldVer = $versionCheck;
		}
		// End Upgrade check start for version above
		// Upgrade check start for version below
		$versionCheck = '2.0.0-beta-500';
		if ($compare->lessThan($oldVer, $versionCheck)) {
			$updateDB = true;
			$oldVer = $versionCheck;
		}
		// End Upgrade check start for version above
		$versionCheck = '2.0.0-beta-800';
		if ($compare->lessThan($oldVer, $versionCheck)) {
			$updateDB = true;
			$oldVer = $versionCheck;
		}
		// End Upgrade check start for version above
		if ($updateDB == true) {
			//return 'Upgraded Needed - Current Version '.$oldVer.' - New Version: '.$versionCheck;
			// Upgrade database to latest version
			$updateSuccess = (updateDB($oldVer)) ? true : false;
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
}
