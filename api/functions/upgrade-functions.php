<?php

function upgradeCheck() {
	$updateDB = false;
    $compare = new Composer\Semver\Comparator;
	// Upgrade check start for vserion below
	$versionCheck = '2.0.0-alpha-120';
	$config = loadConfig();
	$oldVer = $config['configVersion'];
	echo 'Doing check if version '.$oldVer.' is less than '.$versionCheck.'<br />';
	if (isset($config['dbLocation']) && (!isset($config['configVersion']) || $compare->lessThan($config['configVersion'], $versionCheck))) {
        $updateDB = true;
	}




	if($updateDB == true){
		return 'Upgraded Needed - Current Version '.$oldVer.' - New Version: '.$versionCheck;
		// Upgrade database to latest version
		//updateDB($GLOBALS['dbLocation'],$GLOBALS['dbName'],$oldVer);
		// Update Version and Commit
		//$config['configVersion'] = $versionCheck;
		//copy('config/config.php', 'config/config['.date('Y-m-d_H-i-s').'][1.40].bak.php');
		//$createConfigSuccess = createConfig($config);
		//unset($config);
	}else{
		return 'No Upgraded Needed - Current Version Above: '.$versionCheck;
	}
	return true;
}
