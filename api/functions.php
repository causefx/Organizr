<?php
// ===================================
// Organizr Version
$GLOBALS['installedVersion'] = '2.0.0-alpha-100';
// ===================================
//Set GLOBALS from config file
$GLOBALS['userConfigPath'] = __DIR__.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php';
$GLOBALS['defaultConfigPath'] = __DIR__.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'default.php';
$GLOBALS['currentTime'] = gmdate("Y-m-d\TH:i:s\Z");
//Add in default and custom settings
configLazy();
//Define Logs and files after db location is set
if(isset($GLOBALS['dbLocation'])){
    $GLOBALS['organizrLog'] = $GLOBALS['dbLocation'].'organizrLog.json';
    $GLOBALS['organizrLoginLog'] = $GLOBALS['dbLocation'].'organizrLoginLog.json';
}
//Set UTC timeZone
date_default_timezone_set("UTC");
// Autoload frameworks
require_once(__DIR__ . '/vendor/autoload.php');
//framework uses
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Parser;
//Validate Token if set and set guest if not - sets GLOBALS
getOrganizrUserToken();
//include all pages files
foreach (glob(__DIR__.DIRECTORY_SEPARATOR.'pages' . DIRECTORY_SEPARATOR . "*.php") as $filename){
    require_once $filename;
}
function jwtParse($token){
    try {
        $result = array();
        $result['valid'] = false;
        //Check Token with JWT
        //Set key
        if(!isset($GLOBALS['organizrHash'])){
            return null;
        }
        $key = $GLOBALS['organizrHash'];
        //HSA256 Encyption
        $signer = new Sha256();
        $jwttoken = (new Parser())->parse((string) $token); // Parses from a string
        $jwttoken->getHeaders(); // Retrieves the token header
        $jwttoken->getClaims(); // Retrieves the token claims
        //Start Validation
        if($jwttoken->verify($signer, $key)){
            $data = new ValidationData(); // It will use the current time to validate (iat, nbf and exp)
            $data->setIssuer('Organizr');
            $data->setAudience('Organizr');
            if($jwttoken->validate($data)){
                $result['valid'] = true;
                $result['username'] = $jwttoken->getClaim('username');
                $result['group'] = $jwttoken->getClaim('group');
                $result['groupID'] = $jwttoken->getClaim('groupID');
                $result['email'] = $jwttoken->getClaim('email');
                $result['image'] = $jwttoken->getClaim('image');
                $result['tokenExpire'] = $jwttoken->getClaim('exp');
                $result['tokenDate'] = $jwttoken->getClaim('iat');
                $result['token'] = $jwttoken->getClaim('exp');
            }
        }
        if($result['valid'] == true){ return $result; }else{ return false; }
    } catch(\RunException $e) {
        return false;
    } catch(\OutOfBoundsException $e) {
        return false;
    } catch(\RunTimeException $e) {
        return false;
    } catch(\InvalidArgumentException $e) {
        return false;
    }
}
function createToken($username,$email,$image,$group,$groupID,$key,$days = 1){
    //Create JWT
    //Set key
    //HSA256 Encyption
    $signer = new Sha256();
    //Start Builder
    $jwttoken = (new Builder())->setIssuer('Organizr') // Configures the issuer (iss claim)
                                ->setAudience('Organizr') // Configures the audience (aud claim)
                                ->setId('4f1g23a12aa', true) // Configures the id (jti claim), replicating as a header item
                                ->setIssuedAt(time()) // Configures the time that the token was issue (iat claim)
                                ->setExpiration(time() + (86400 * $days)) // Configures the expiration time of the token (exp claim)
                                ->set('username', $username) // Configures a new claim, called "username"
                                ->set('group', $group) // Configures a new claim, called "group"
                                ->set('groupID', $groupID) // Configures a new claim, called "groupID"
                                ->set('email', $email) // Configures a new claim, called "email"
                                ->set('image', $image) // Configures a new claim, called "image"
                                ->sign($signer, $key) // creates a signature using "testing" as key
                                ->getToken(); // Retrieves the generated token
    $jwttoken->getHeaders(); // Retrieves the token headers
    $jwttoken->getClaims(); // Retrieves the token claims
    coookie('set','organizrToken',$jwttoken,$days);
    return $jwttoken;
}
function prettyPrint($v) {
	$trace = debug_backtrace()[0];
	echo '<pre style="white-space: pre; text-overflow: ellipsis; overflow: hidden; background-color: #f2f2f2; border: 2px solid black; border-radius: 5px; padding: 5px; margin: 5px;">'.$trace['file'].':'.$trace['line'].' '.gettype($v)."\n\n".print_r($v, 1).'</pre><br/>';
}

// Create config file in the return syntax
function createConfig($array, $path = null, $nest = 0) {
    $path = ($path) ? $path : $GLOBALS['userConfigPath'];
	// Define Initial Value
	$output = array();

	// Sort Items
	ksort($array);

	// Update the current config version
	if (!$nest) {
		// Inject Current Version
		$output[] = "\t'configVersion' => '".(isset($array['apply_CONFIG_VERSION'])?$array['apply_CONFIG_VERSION']:$GLOBALS['installedVersion'])."'";
	}
	unset($array['configVersion']);
	unset($array['apply_CONFIG_VERSION']);

	// Process Settings
	foreach ($array as $k => $v) {
		$allowCommit = true;
		switch (gettype($v)) {
			case 'boolean':
				$item = ($v?true:false);
				break;
			case 'integer':
			case 'double':
			case 'integer':
			case 'NULL':
				$item = $v;
				break;
			case 'string':
				$item = "'".str_replace(array('\\',"'"),array('\\\\',"\'"),$v)."'";
				break;
			case 'array':
				$item = createConfig($v, false, $nest+1);
				break;
			default:
				$allowCommit = false;
		}

		if($allowCommit) {
			$output[] = str_repeat("\t",$nest+1)."'$k' => $item";
		}
	}

	// Build output
	$output = (!$nest?"<?php\nreturn ":'')."array(\n".implode(",\n",$output)."\n".str_repeat("\t",$nest).')'.(!$nest?';':'');

	if (!$nest && $path) {
		$pathDigest = pathinfo($path);

		@mkdir($pathDigest['dirname'], 0770, true);

		if (file_exists($path)) {
			rename($path, $pathDigest['dirname'].'/'.$pathDigest['filename'].'.bak.php');
		}

		$file = fopen($path, 'w');
		fwrite($file, $output);
		fclose($file);
		if (file_exists($path)) {
			return true;
		}
		//writeLog("error", "config was unable to write");
		return false;
	} else {
  		//writeLog("success", "config was updated with new values");
		return $output;
	}
}
// Commit new values to the configuration
function updateConfig($new, $current = false) {
	// Get config if not supplied
	if ($current === false) {
		$current = loadConfig();
	} else if (is_string($current) && is_file($current)) {
		$current = loadConfig($current);
	}
	// Inject Parts
	foreach ($new as $k => $v) {
		$current[$k] = $v;
	}
	// Return Create
	return createConfig($current);
}
function configLazy() {
	// Load config or default
	if (file_exists($GLOBALS['userConfigPath'])) {
		$config = fillDefaultConfig(loadConfig($GLOBALS['userConfigPath']));
	} else {
		$config = loadConfig($GLOBALS['defaultConfigPath']);
	}
	if (is_array($config)) {
		defineConfig($config);
	}
	return $config;
}
function loadConfig($path = null){
    $path = ($path) ? $path : $GLOBALS['userConfigPath'];
    if (!is_file($path)) {
        return null;
    } else {
        return (array) call_user_func(function() use($path) {
            return include($path);
        });
    }
}
function fillDefaultConfig($array) {
    $path = $GLOBALS['defaultConfigPath'];
	if (is_string($path)) {
		$loadedDefaults = loadConfig($path);
	} else {
		$loadedDefaults = $path;
	}
	return (is_array($loadedDefaults) ? fillDefaultConfig_recurse($array, $loadedDefaults) : false);
}
function fillDefaultConfig_recurse($current, $defaults) {
	foreach($defaults as $k => $v) {
		if (!isset($current[$k])) {
			$current[$k] = $v;
		} else if (is_array($current[$k]) && is_array($v)) {
			$current[$k] = fillDefaultConfig_recurse($current[$k], $v);
		}
	}
	return $current;
}
function defineConfig($array, $anyCase = true, $nest_prefix = false) {
	foreach($array as $k => $v) {
		if (is_scalar($v) && !defined($nest_prefix.$k)) {
            $GLOBALS[$nest_prefix.$k] = $v;
		} else if (is_array($v)) {
			defineConfig($v, $anyCase, $nest_prefix.$k.'_');
		}
	}
}
function cleanDirectory($path){
	$path = str_replace(array('/', '\\'), '/', $path);
    if(substr($path, -1) != '/'){
        $path = $path . '/';
    }
    if($path[0] != '/' && $path[1] != ':'){
        $path = '/' . $path;
    }
    return $path;
}
function wizardConfig($array){
    foreach ($array['data'] as $items) {
        foreach ($items as $key => $value) {
            if($key == 'name'){
                $newKey = $value;
            }
            if($key == 'value'){
                $newValue = $value;
            }
            if(isset($newKey) && isset($newValue)){
                $$newKey = $newValue;
            }
        }
    }
    $location = cleanDirectory($location);
    $dbName = $dbName.'.db';
    $configVersion = $GLOBALS['installedVersion'];
    $configArray = array(
        'dbName' => $dbName,
        'dbLocation' => $location,
        'license' => $license,
        'organizrHash' => $hashKey,
        'organizrAPI' => $api,
        'registrationPassword' => $registrationPassword,
    );
    /*
    file_put_contents('config'.DIRECTORY_SEPARATOR.'config.php',
"<?php
return array(
    \"configVersion\" => \"$configVersion\",
    \"dbName\" => \"$dbName\",
    \"dbLocation\" => \"$location\",
    \"license\" => \"$license\",
    \"organizrHash\" => \"$hashKey\",
    \"organizrAPI\" => \"$api\",
    \"registrationPassword\" => \"$registrationPassword\"
);");
*/
    //Create Config
    if(createConfig($configArray)){
        //Call DB Create
        if(createDB($location,$dbName)){
            //Add in first user
            if(createFirstAdmin($location,$dbName,$username,$password,$email)){
                if(createToken($username,$email,gravatar($email),'Admin',0,$hashKey,1)){
                    return true;
                }
            }
        }
    }
    return false;
}
function gravatar($email = '') {
    $email = md5(strtolower(trim($email)));
    $gravurl = "https://www.gravatar.com/avatar/$email?s=100&d=mm";
    return $gravurl;
}
function login($array){
    //Grab username and Password from login form
    foreach ($array['data'] as $items) {
        foreach ($items as $key => $value) {
            if($key == 'name'){
                $newKey = $value;
            }
            if($key == 'value'){
                $newValue = $value;
            }
            if(isset($newKey) && isset($newValue)){
                $$newKey = $newValue;
            }
        }
    }
    $username = strtolower($username);
    $days = (isset($remember)) ? 7 : 1;
    try {
    	$database = new Dibi\Connection([
    		'driver' => 'sqlite3',
    		'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
    	]);
        $result = $database->fetch('SELECT * FROM users WHERE username = ? COLLATE NOCASE OR email = ? COLLATE NOCASE',$username,$username);
        if(password_verify($password, $result['password'])){
            if(createToken($result['username'],$result['email'],$result['image'],$result['group'],$result['group_id'],$GLOBALS['organizrHash'],$days)){
                writeLoginLog($username, 'success');
                writeLog('success', 'Login Function - A User has logged in', $username);
                return true;
            }
        }else{
            writeLoginLog($username, 'error');
            writeLog('error', 'Login Function - Wrong Password', $username);
            return 'mismatch';
        }
    } catch (Dibi\Exception $e) {
    	return 'error';
    }
}
function createDB($path,$filename) {
    if(file_exists($path.$filename)){
        unlink($path.$filename);
    }
    try {
    	$createDB = new Dibi\Connection([
    		'driver' => 'sqlite3',
    		'database' => $path.$filename,
    	]);
        // Create Users
    	$users = $createDB->query('CREATE TABLE `users` (
    		`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
    		`username`	TEXT UNIQUE,
    		`password`	TEXT,
    		`email`	TEXT,
    		`plex_token`	TEXT,
            `group`	TEXT,
            `group_id`	INTEGER,
    		`image`	TEXT,
            `register_date` DATE,
    		`auth_service`	TEXT DEFAULT \'internal\'
    	);');
        $groups = $createDB->query('CREATE TABLE `groups` (
    		`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
    		`group`	TEXT UNIQUE,
            `group_id`	INTEGER,
    		`image`	TEXT,
            `default` INTEGER
    	);');
        $categories = $createDB->query('CREATE TABLE `categories` (
    		`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
            `order`	INTEGER,
    		`category`	TEXT UNIQUE,
            `category_id`	INTEGER,
    		`image`	TEXT,
            `default` INTEGER
    	);');
    	// Create Tabs
    	$tabs = $createDB->query('CREATE TABLE `tabs` (
    		`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
    		`order`	INTEGER,
    		`category_id`	INTEGER,
    		`name`	TEXT,
            `url`	TEXT,
    		`url_local`	TEXT,
    		`default`	INTEGER,
    		`enabled`	INTEGER,
    		`group_id`	INTEGER,
    		`image`	TEXT,
    		`type`	INTEGER,
    		`splash`	INTEGER,
    		`ping`		INTEGER,
    		`ping_url`	TEXT
    	);');
    	// Create Options
    	$options = $createDB->query('CREATE TABLE `options` (
    		`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
    		`users_id`	INTEGER UNIQUE,
    		`title`	TEXT UNIQUE,
    		`topbar`	TEXT,
    		`bottombar`	TEXT,
    		`sidebar`	TEXT,
    		`hoverbg`	TEXT,
    		`topbartext`	TEXT,
    		`activetabBG`	TEXT,
    		`activetabicon`	TEXT,
    		`activetabtext`	TEXT,
    		`inactiveicon`	TEXT,
    		`inactivetext`	TEXT,
    		`loading`	TEXT,
    		`hovertext`	TEXT
    	);');
    	// Create Invites
    	$invites = $createDB->query('CREATE TABLE `invites` (
    		`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
    		`code`	TEXT UNIQUE,
    		`date`	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    		`email`	TEXT,
    		`username`	TEXT,
    		`dateused`	TIMESTAMP,
    		`usedby`	TEXT,
    		`ip`	TEXT,
    		`valid`	TEXT,
            `type` TEXT
    	);');
        return true;
    } catch (Dibi\Exception $e) {
        return false;
    }
}
// Upgrade Database
function updateDB($path,$filename,$oldVerNum = false) {
    try {
        $connect = new Dibi\Connection([
            'driver' => 'sqlite3',
            'database' => $path.$filename,
        ]);
        // Cache current DB
    	$cache = array();
    	foreach($connect->query('SELECT name FROM sqlite_master WHERE type="table";') as $table) {
    		foreach($connect->query('SELECT * FROM '.$table['name'].';') as $key => $row) {
    			foreach($row as $k => $v) {
    				if (is_string($k)) {
    					$cache[$table['name']][$key][$k] = $v;
    				}
    			}
    		}
    	}
        // Remove Current Database
        /*
        $pathDigest = pathinfo($path.$filename);
        if (file_exists($path.$filename)) {
            rename($path.$filename, $pathDigest['dirname'].'/'.$pathDigest['filename'].'['.date('Y-m-d_H-i-s').']'.($oldVerNum?'['.$oldVerNum.']':'').'.bak.db');
        }

        // Create New Database
        $success = createSQLiteDB($path.$filename);

        // Restore Items
        if ($success) {
            foreach($cache as $table => $tableData) {
                if ($tableData) {
                    $queryBase = 'INSERT INTO '.$table.' (`'.implode('`,`',array_keys(current($tableData))).'`) values ';
                    $insertValues = array();
                    reset($tableData);
                    foreach($tableData as $key => $value) {
                        $insertValues[] = '('.implode(',',array_map(function($d) {
                            return (isset($d)?$GLOBALS['file_db']->quote($d):'null');
                        }, $value)).')';
                    }
                    $GLOBALS['file_db']->query($queryBase.implode(',',$insertValues).';');
                }
            }
      //writeLog("success", "database values have been updated");
            return true;
        } else {
      //writeLog("error", "database values unable to be updated");
            return false;
        }
        */
        return $cache;
    } catch (Dibi\Exception $e) {
        return $e;
    }
}
function createFirstAdmin($path,$filename,$username,$password,$email) {
    try {
    	$createDB = new Dibi\Connection([
    		'driver' => 'sqlite3',
    		'database' => $path.$filename,
    	]);
        $userInfo = [
            'username' => $username,
            'password'  => password_hash($password, PASSWORD_BCRYPT),
            'email' => $email,
            'group' => 'Admin',
            'group_id' => 0,
            'image' => gravatar($email),
            'register_date' => $GLOBALS['currentTime'],
        ];
        $groupInfo0 = [
            'group' => 'Admin',
            'group_id' => 0,
            'default' => false,
            'image' => 'plugins/images/groups/admin.png',
        ];
        $groupInfo1 = [
            'group' => 'Co-Admin',
            'group_id' => 1,
            'default' => false,
            'image' => 'plugins/images/groups/coadmin.png',
        ];
        $groupInfo2 = [
            'group' => 'Super User',
            'group_id' => 2,
            'default' => false,
            'image' => 'plugins/images/groups/superuser.png',
        ];
        $groupInfo3 = [
            'group' => 'Power User',
            'group_id' => 3,
            'default' => false,
            'image' => 'plugins/images/groups/poweruser.png',
        ];
        $groupInfo4 = [
            'group' => 'User',
            'group_id' => 4,
            'default' => true,
            'image' => 'plugins/images/groups/user.png',
        ];
        $groupInfoGuest = [
            'group' => 'Guest',
            'group_id' => 999,
            'default' => false,
            'image' => 'plugins/images/groups/guest.png',
        ];
        $settingsInfo = [
            'order' => 1,
            'category_id' => 0,
            'name' => 'Settings',
            'url' => 'api/?v1/settings/page',
            'default' => false,
            'enabled' => true,
            'group_id' => 1,
            'image' => 'fontawesome::cog',
            'type' => 0
        ];
        $homepageInfo = [
            'order' => 2,
            'category_id' => 0,
            'name' => 'Homepage',
            'url' => 'api/?v1/homepage/page',
            'default' => false,
            'enabled' => false,
            'group_id' => 4,
            'image' => 'fontawesome::home',
            'type' => 0
        ];
        $unsortedInfo = [
            'order' => 1,
            'category' => 'Unsorted',
            'category_id' => 0,
			'image' => 'plugins/images/categories/unsorted.png',
            'default' => true
        ];
        $createDB->query('INSERT INTO [users]', $userInfo);
        $createDB->query('INSERT INTO [groups]', $groupInfo0);
        $createDB->query('INSERT INTO [groups]', $groupInfo1);
        $createDB->query('INSERT INTO [groups]', $groupInfo2);
        $createDB->query('INSERT INTO [groups]', $groupInfo3);
        $createDB->query('INSERT INTO [groups]', $groupInfo4);
        $createDB->query('INSERT INTO [groups]', $groupInfoGuest);
        $createDB->query('INSERT INTO [tabs]', $settingsInfo);
        $createDB->query('INSERT INTO [tabs]', $homepageInfo);
        $createDB->query('INSERT INTO [categories]', $unsortedInfo);
        return true;
    } catch (Dibi\Exception $e) {
        return false;
    }
}
function register($array){
    //Grab username and Password from login form
    foreach ($array['data'] as $items) {
        foreach ($items as $key => $value) {
            if($key == 'name'){
                $newKey = $value;
            }
            if($key == 'value'){
                $newValue = $value;
            }
            if(isset($newKey) && isset($newValue)){
                $$newKey = $newValue;
            }
        }
    }
    if($registrationPassword == $GLOBALS['registrationPassword']){
        $defaults = defaultUserGroup();
        writeLog('success', 'Registration Function - Registration Password Verified', $username);
        if(createUser($username,$password,$defaults,$email)){
            writeLog('success', 'Registration Function - A User has registered', $username);
            if(createToken($username,$email,gravatar($email),$defaults['group'],$defaults['group_id'],$GLOBALS['organizrHash'],1)){
                writeLoginLog($username, 'success');
                writeLog('success', 'Login Function - A User has logged in', $username);
                return true;
            }
        }else{
            writeLog('error', 'Registration Function - An error occured', $username);
            return 'username taken';
        }
    }else{
        writeLog('warning', 'Registration Function - Wrong Password', $username);
        return 'mismatch';
    }
}
function defaultUserGroup(){
    try {
    	$connect = new Dibi\Connection([
    		'driver' => 'sqlite3',
    		'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
    	]);
        $all = $connect->fetch('SELECT * FROM groups WHERE `default` = 1');
        return $all;
    } catch (Dibi\Exception $e) {
        return false;
    }
}
function defaulTabCategory(){
    try {
    	$connect = new Dibi\Connection([
    		'driver' => 'sqlite3',
    		'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
    	]);
        $all = $connect->fetch('SELECT * FROM categories WHERE `default` = 1');
        return $all;
    } catch (Dibi\Exception $e) {
        return false;
    }
}
function getGuest(){
    if(isset($GLOBALS['dbLocation'])){
        try {
        	$connect = new Dibi\Connection([
        		'driver' => 'sqlite3',
        		'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
        	]);
            $all = $connect->fetch('SELECT * FROM groups WHERE `group` = "Guest"');
            return $all;
        } catch (Dibi\Exception $e) {
            return false;
        }
    }else{
        return array(
            'group' => 'Guest',
            'group_id' => 999,
            'image' => 'plugins/images/groups/guest.png'
        );
    }
}
function adminEditGroup($array){
    switch ($array['data']['action']) {
        case 'changeDefaultGroup':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $connect->query('UPDATE groups SET `default` = 0');
                $connect->query('
                	UPDATE groups SET', [
                		'default' => 1
                	], '
                	WHERE id=?', $array['data']['id']);
                    writeLog('success', 'Group Management Function -  Changed Default Group from ['.$array['data']['oldGroupName'].'] to ['.$array['data']['newGroupName'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        case 'deleteUserGroup':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $connect->query('DELETE FROM groups WHERE id = ?', $array['data']['id']);
                    writeLog('success', 'Group Management Function -  Deleted Group ['.$array['data']['groupName'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        case 'addUserGroup':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $newGroup = [
                    'group' => $array['data']['newGroupName'],
                    'group_id' => $array['data']['newGroupID'],
                    'default' => false,
                    'image' => $array['data']['newGroupImage'],
                ];
                $connect->query('INSERT INTO [groups]', $newGroup);
                    writeLog('success', 'Group Management Function -  Added Group ['.$array['data']['newGroupName'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        case 'editUserGroup':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $connect->query('
                	UPDATE groups SET', [
                        'group' => $array['data']['groupName'],
                		'image' => $array['data']['groupImage'],
                	], '
                	WHERE id=?', $array['data']['id']);
                    writeLog('success', 'Group Management Function -  Edited Group Info for ['.$array['data']['oldGroupName'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        default:
            # code...
            break;
    }
}
function adminEditUser($array){
    switch ($array['data']['action']) {
        case 'changeGroup':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $connect->query('
                	UPDATE users SET', [
                		'group' => $array['data']['newGroupName'],
                		'group_id' => $array['data']['newGroupID'],
                	], '
                	WHERE id=?', $array['data']['id']);
                    writeLog('success', 'User Management Function - User: '.$array['data']['username'].'\'s group was changed from ['.$array['data']['oldGroup'].'] to ['.$array['data']['newGroupName'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                writeLog('error', 'User Management Function - Error - User: '.$array['data']['username'].'\'s group was changed from ['.$array['data']['oldGroup'].'] to ['.$array['data']['newGroupName'].']', $GLOBALS['organizrUser']['username']);
                return false;
            }
            break;
        case 'addNewUser':
            $defaults = defaultUserGroup();
            if(createUser($array['data']['username'],$array['data']['password'],$defaults,$array['data']['email'])){
                writeLog('success', 'Create User Function - Acount created for ['.$array['data']['username'].']', $GLOBALS['organizrUser']['username']);
                return true;
            }else{
                writeLog('error', 'Registration Function - An error occured', $GLOBALS['organizrUser']['username']);
                return 'username taken';
            }
            break;
        case 'deleteUser':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $connect->query('DELETE FROM users WHERE id = ?', $array['data']['id']);
                    writeLog('success', 'User Management Function -  Deleted User ['.$array['data']['username'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        default:
            # code...
            break;
    }
}
function editTabs($array){
    switch ($array['data']['action']) {
        case 'changeGroup':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $connect->query('
                	UPDATE tabs SET', [
                		'group_id' => $array['data']['newGroupID'],
                	], '
                	WHERE id=?', $array['data']['id']);
                    writeLog('success', 'Tab Editor Function - Tab: '.$array['data']['tab'].'\'s group was changed to ['.$array['data']['newGroupName'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        case 'changeCategory':
                try {
                    $connect = new Dibi\Connection([
                        'driver' => 'sqlite3',
                        'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                    ]);
                    $connect->query('
                        UPDATE tabs SET', [
                            'category_id' => $array['data']['newCategoryID'],
                        ], '
                        WHERE id=?', $array['data']['id']);
                        writeLog('success', 'Tab Editor Function - Tab: '.$array['data']['tab'].'\'s category was changed to ['.$array['data']['newCategoryName'].']', $GLOBALS['organizrUser']['username']);
                    return true;
                } catch (Dibi\Exception $e) {
                    return false;
                }
                break;
        case 'changeType':
                try {
                    $connect = new Dibi\Connection([
                        'driver' => 'sqlite3',
                        'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                    ]);
                    $connect->query('
                        UPDATE tabs SET', [
                            'type' => $array['data']['newTypeID'],
                        ], '
                        WHERE id=?', $array['data']['id']);
                        writeLog('success', 'Tab Editor Function - Tab: '.$array['data']['tab'].'\'s type was changed to ['.$array['data']['newTypeName'].']', $GLOBALS['organizrUser']['username']);
                    return true;
                } catch (Dibi\Exception $e) {
                    return false;
                }
                break;
        case 'changeEnabled':
                try {
                    $connect = new Dibi\Connection([
                        'driver' => 'sqlite3',
                        'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                    ]);
                    $connect->query('
                        UPDATE tabs SET', [
                            'enabled' => $array['data']['tabEnabled'],
                        ], '
                        WHERE id=?', $array['data']['id']);
                        writeLog('success', 'Tab Editor Function - Tab: '.$array['data']['tab'].'\'s enable status was changed to ['.$array['data']['tabEnabledWord'].']', $GLOBALS['organizrUser']['username']);
                    return true;
                } catch (Dibi\Exception $e) {
                    return false;
                }
                break;
        case 'changeSplash':
                try {
                    $connect = new Dibi\Connection([
                        'driver' => 'sqlite3',
                        'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                    ]);
                    $connect->query('
                        UPDATE tabs SET', [
                            'splash' => $array['data']['tabSplash'],
                        ], '
                        WHERE id=?', $array['data']['id']);
                        writeLog('success', 'Tab Editor Function - Tab: '.$array['data']['tab'].'\'s splash status was changed to ['.$array['data']['tabSplashWord'].']', $GLOBALS['organizrUser']['username']);
                    return true;
                } catch (Dibi\Exception $e) {
                    return false;
                }
                break;
        case 'changeDefault':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $connect->query('UPDATE tabs SET `default` = 0');
                $connect->query('
                    UPDATE tabs SET', [
                        'default' => 1
                    ], '
                    WHERE id=?', $array['data']['id']);
                    writeLog('success', 'Tab Editor Function -  Changed Default Tab to ['.$array['data']['tab'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        case 'deleteTab':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $connect->query('DELETE FROM tabs WHERE id = ?', $array['data']['id']);
                    writeLog('success', 'Tab Editor Function -  Deleted Tab ['.$array['data']['tab'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        case 'editTab':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $connect->query('
                    UPDATE tabs SET', [
                        'name' => $array['data']['tabName'],
                        'url' => $array['data']['tabURL'],
                        'image' => $array['data']['tabImage'],
                    ], '
                    WHERE id=?', $array['data']['id']);
                    writeLog('success', 'Tab Editor Function -  Edited Tab Info for ['.$array['data']['tabName'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
        case 'changeOrder':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                foreach ($array['data']['tabs']['tab'] as $key => $value) {
                    if($value['order'] != $value['originalOrder']){
                        $connect->query('
                            UPDATE tabs SET', [
                                'order' => $value['order'],
                            ], '
                            WHERE id=?', $value['id']);
                            writeLog('success', 'Tab Editor Function - '.$value['name'].' Order Changed From '.$value['order'].' to '.$value['originalOrder'], $GLOBALS['organizrUser']['username']);
                    }
                }
                writeLog('success', 'Tab Editor Function - Tab Order Changed', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        case 'addNewTab':
            try {
                $default = defaulTabCategory()['category_id'];
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $newTab = [
                    'order' => $array['data']['tabOrder'],
                    'category_id' => $default,
                    'name' => $array['data']['tabName'],
                    'url' => $array['data']['tabURL'],
                    'default' => $array['data']['tabDefault'],
                    'enabled' => 1,
                    'group_id' => $array['data']['tabGroupID'],
                    'image' => $array['data']['tabImage'],
                    'type' => $array['data']['tabType']
                ];
                $connect->query('INSERT INTO [tabs]', $newTab);
                writeLog('success', 'Tab Editor Function - Created Tab for: '.$array['data']['tabName'], $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        case 'deleteTab':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $connect->query('DELETE FROM tabs WHERE id = ?', $array['data']['id']);
                    writeLog('success', 'Tab Editor Function -  Deleted Tab ['.$array['data']['name'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        default:
            # code...
            break;
    }
}
function editCategories($array){
    switch ($array['data']['action']) {
        case 'changeDefault':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $connect->query('UPDATE categories SET `default` = 0');
                $connect->query('
                	UPDATE categories SET', [
                		'default' => 1
                	], '
                	WHERE id=?', $array['data']['id']);
                    writeLog('success', 'Category Editor Function -  Changed Default Category from ['.$array['data']['oldCategoryName'].'] to ['.$array['data']['newCategoryName'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        case 'deleteCategory':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $connect->query('DELETE FROM categories WHERE id = ?', $array['data']['id']);
                    writeLog('success', 'Category Editor Function -  Deleted Category ['.$array['data']['category'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        case 'addNewCategory':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $newCategory = [
                    'category' => $array['data']['categoryName'],
                    'order' => $array['data']['categoryOrder'],
                    'category_id' => $array['data']['categoryID'],
                    'default' => false,
                    'image' => $array['data']['categoryImage'],
                ];
                $connect->query('INSERT INTO [categories]', $newCategory);
                    writeLog('success', 'Category Editor Function -  Added Category ['.$array['data']['categoryName'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return $e;
            }
            break;
        case 'editCategory':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                $connect->query('
                	UPDATE categories SET', [
                        'category' => $array['data']['name'],
                		'image' => $array['data']['image'],
                	], '
                	WHERE id=?', $array['data']['id']);
                    writeLog('success', 'Category Editor Function -  Edited Category Info for ['.$array['data']['name'].']', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        case 'changeOrder':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                foreach ($array['data']['categories']['category'] as $key => $value) {
                    if($value['order'] != $value['originalOrder']){
                        $connect->query('
                            UPDATE categories SET', [
                                'order' => $value['order'],
                            ], '
                            WHERE id=?', $value['id']);
                            writeLog('success', 'Category Editor Function - '.$value['name'].' Order Changed From '.$value['order'].' to '.$value['originalOrder'], $GLOBALS['organizrUser']['username']);
                    }
                }
                writeLog('success', 'Category Editor Function - Category Order Changed', $GLOBALS['organizrUser']['username']);
                return true;
            } catch (Dibi\Exception $e) {
                return false;
            }
            break;
        default:
            # code...
            break;
    }
}
function editUser($array){
    return $array;
}
function allUsers(){
    try {
    	$connect = new Dibi\Connection([
    		'driver' => 'sqlite3',
    		'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
    	]);
        $users = $connect->fetchAll('SELECT * FROM users');
        $groups = $connect->fetchAll('SELECT * FROM groups ORDER BY group_id ASC');
        foreach ($users as $k => $v) {
            //clear password from array
            unset($users[$k]['password']);
        }
        $all['users'] = $users;
        $all['groups'] = $groups;
        return $all;
    } catch (Dibi\Exception $e) {
        return false;
    }
}
function usernameTaken($username,$email){
    try {
    	$connect = new Dibi\Connection([
    		'driver' => 'sqlite3',
    		'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
    	]);
        $all = $connect->fetch('SELECT * FROM users WHERE username = ? COLLATE NOCASE OR email = ? COLLATE NOCASE',$username,$email);
        return ($all) ? true : false;
    } catch (Dibi\Exception $e) {
        return false;
    }
}
function createUser($username,$password,$defaults,$email=null) {
    $email = ($email) ? $email : random_ascii_string(10).'@placeholder.eml';
    try {
        if(!usernameTaken($username,$email)){
            $createDB = new Dibi\Connection([
        		'driver' => 'sqlite3',
        		'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
        	]);
            $userInfo = [
                'username' => $username,
                'password'  => password_hash($password, PASSWORD_BCRYPT),
                'email' => $email,
                'group' => $defaults['group'],
                'group_id' => $defaults['group_id'],
                'image' => gravatar($email),
                'register_date' => $GLOBALS['currentTime'],
            ];
            $createDB->query('INSERT INTO [users]', $userInfo);
            return true;
        }else{
            return false;
        }

    } catch (Dibi\Exception $e) {
        return false;
    }
}
//Cookie Function
function coookie($type, $name, $value = '', $days = -1, $http = true){
	if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https"){
		$Secure = true;
 	   	$HTTPOnly = true;
	}elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
		$Secure = true;
 	   	$HTTPOnly = true;
	} else {
		$Secure = false;
 	   	$HTTPOnly = false;
   }
   if(!$http){ $HTTPOnly = false; }
	$Path = '/';
	$Domain = $_SERVER['HTTP_HOST'];
	$Port = strpos($Domain, ':');
	if ($Port !== false)  $Domain = substr($Domain, 0, $Port);
	$Port = strpos($Domain, ':');
	$check = substr_count($Domain, '.');
	if($check >= 3){
		if(is_numeric($Domain[0])){
			$Domain = '';
		}else{
			$Domain = '.'.explode('.',$Domain)[1].'.'.explode('.',$Domain)[2].'.'.explode('.',$Domain)[3];
		}
	}elseif($check == 2){
		$Domain = '.'.explode('.',$Domain)[1].'.'.explode('.',$Domain)[2];
	}elseif($check == 1){
		$Domain = '.' . $Domain;
	}else{
		$Domain = '';
	}
	if($type = 'set'){
		$_COOKIE[$name] = $value;
		header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
							. (empty($days) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() + (86400 * $days)) . ' GMT')
							. (empty($Path) ? '' : '; path=' . $Path)
							. (empty($Domain) ? '' : '; domain=' . $Domain)
							. (!$Secure ? '' : '; secure')
							. (!$HTTPOnly ? '' : '; HttpOnly'), false);
	}elseif($type = 'delete'){
		unset($_COOKIE[$name]);
		header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
							. (empty($days) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', time() - 3600) . ' GMT')
							. (empty($Path) ? '' : '; path=' . $Path)
							. (empty($Domain) ? '' : '; domain=' . $Domain)
							. (!$Secure ? '' : '; secure')
							. (!$HTTPOnly ? '' : '; HttpOnly'), false);
	}
}
function validateToken($token,$global=false){
    //validate script
    $userInfo = jwtParse($token);
    $validated = $userInfo ? true : false;
    if($validated == true){
        if($global == true){
            $GLOBALS['organizrUser'] = array(
                "token"=>$token,
                "tokenDate"=>$userInfo['tokenDate'],
                "tokenExpire"=>$userInfo['tokenExpire'],
                "username"=>$userInfo['username'],
                "group"=>$userInfo['group'],
                "groupID"=>$userInfo['groupID'],
                "email"=>$userInfo['email'],
                "image"=>$userInfo['image'],
                "loggedin"=>true,
            );
        }
    }else{
        //delete cookie & reload page
        coookie('delete','organizrToken');
        $GLOBALS['organizrUser'] = false;
    }
}
function logout(){
    coookie('delete','organizrToken');
    $GLOBALS['organizrUser'] = false;
    return true;
}
function getOrganizrUserToken(){
    if(isset($_COOKIE['organizrToken'])){
        //get token form cookie and validate
        validateToken($_COOKIE['organizrToken'],true);
    }else{
        $GLOBALS['organizrUser'] = array(
            "token"=>null,
            "tokenDate"=>null,
            "tokenExpire"=>null,
            "username"=>"Guest",
            "group"=>getGuest()['group'],
            "groupID"=>getGuest()['group_id'],
            "email"=>null,
            "image"=>getGuest()['image'],
            "loggedin"=>false
        );
    }
}
function qualifyRequest($accessLevelNeeded){
    if(getUserLevel() <= $accessLevelNeeded){
        return true;
    }else{
        return false;
    }
}
function getUserLevel(){
    $requesterToken = isset(getallheaders()['Token']) ? getallheaders()['Token'] : false;
    //check token or API key
    //If API key, return 0 for admin
    if(strlen($requesterToken) == 20 && $requesterToken == $GLOBALS['organizrAPI']){
        //DO API CHECK
        return 0;
    }elseif(isset($GLOBALS['organizrUser'])){
        return $GLOBALS['organizrUser']['groupID'];
    }
    //all else fails?  return guest id
    return 999;
}
function getOS(){
	if(PHP_SHLIB_SUFFIX == "dll"){
		return "win";
	}else{
		return "*nix";
	}
}
function organizrStatus(){
    $status = array();
    $dependenciesActive = array();
    $dependenciesInactive = array();
    $extensions = array("PDO_SQLITE", "PDO", "SQLITE3", "zip", "cURL", "openssl", "simplexml", "json", "session");
    $functions = array("hash", "fopen", "fsockopen", "fwrite", "fclose", "readfile");
    foreach($extensions as $check){
        if(extension_loaded($check)){
            array_push($dependenciesActive,$check);
        }else{
            array_push($dependenciesInactive,$check);
        }
    }
    foreach($functions as $check){
        if(function_exists($check)){
            array_push($dependenciesActive,$check);
        }else{
            array_push($dependenciesInactive,$check);
        }
    }
    if(!file_exists('config'.DIRECTORY_SEPARATOR.'config.php')){
        $status['status'] = "wizard";//wizard - ok for test
    }
    if(count($dependenciesInactive)>0 || !is_writable(dirname(__DIR__,2))){
        $status['status'] = "dependencies";
    }
    $status['status'] = (!empty($status['status'])) ? $status['status'] : $status['status'] = "ok";
    $status['writable'] = is_writable(dirname(__DIR__,2)) ? 'yes' : 'no';
    $status['dependenciesActive'] = $dependenciesActive;
    $status['dependenciesInactive'] = $dependenciesInactive;
    $status['version'] = $GLOBALS['installedVersion'];
    $status['os'] = getOS();
    $status['php'] = phpversion();
    return $status;
}
function allTabs(){
    if(file_exists('config'.DIRECTORY_SEPARATOR.'config.php')){
        try {
        	$connect = new Dibi\Connection([
        		'driver' => 'sqlite3',
        		'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
        	]);
            $all['tabs'] = $connect->fetchAll('SELECT * FROM tabs ORDER BY `order` ASC');
            $all['categories'] = $connect->fetchAll('SELECT * FROM categories ORDER BY `order` ASC');
            $all['groups'] = $connect->fetchAll('SELECT * FROM groups ORDER BY `group_id` ASC');
            return $all;
        } catch (Dibi\Exception $e) {
            return false;
        }
    }
}
function loadTabs(){
    if(file_exists('config'.DIRECTORY_SEPARATOR.'config.php')){
        try {
        	$connect = new Dibi\Connection([
        		'driver' => 'sqlite3',
        		'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
        	]);
            $tabs = $connect->fetchAll('SELECT * FROM tabs WHERE `group_id` >= ? AND `enabled` = 1 ORDER BY `order` DESC',$GLOBALS['organizrUser']['groupID']);
            $categories = $connect->fetchAll('SELECT * FROM categories ORDER BY `order` ASC');
            $all['tabs'] = $tabs;
            foreach ($tabs as $k => $v) {
                $v['access_url'] = isset($v['url_local']) && $_SERVER['SERVER_ADDR'] == userIP() ? $v['url_local'] : $v['url'];
            }
            $count = array_map(function($element){
                return $element['category_id'];
            }, $tabs);
            $count = (array_count_values($count));
            foreach ($categories as $k => $v) {
                $v['count'] = isset($count[$v['category_id']]) ?  $count[$v['category_id']] : 0;
            }
            $all['categories'] = $categories;
            return $all;
        } catch (Dibi\Exception $e) {
            return false;
        }
    }
}
if(!function_exists('getallheaders')){
    function getallheaders(){
        $headers = array ();
        foreach ($_SERVER as $name => $value){
            if (substr($name, 0, 5) == 'HTTP_'){
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
function writeLoginLog($username, $authType) {
    if(file_exists($GLOBALS['organizrLoginLog'])) {
        $getLog = str_replace("\r\ndate", "date", file_get_contents($GLOBALS['organizrLoginLog']));
        $gotLog = json_decode($getLog, true);
    }
    $logEntryFirst = array('logType' => 'login_log', 'auth' => array(array('date' => date("Y-m-d H:i:s"), 'utc_date' => $GLOBALS['currentTime'], 'username' => $username, 'ip' => userIP(), 'auth_type' => $authType)));
    $logEntry = array('date' => date("Y-m-d H:i:s"), 'utc_date' => $GLOBALS['currentTime'], 'username' => $username, 'ip' => userIP(), 'auth_type' => $authType);
    if(isset($gotLog)) {
        array_push($gotLog["auth"], $logEntry);
        $writeFailLog = str_replace("date", "\r\ndate", json_encode($gotLog));
    } else {
        $writeFailLog = str_replace("date", "\r\ndate", json_encode($logEntryFirst));
    }
    file_put_contents($GLOBALS['organizrLoginLog'], $writeFailLog);
};
function writeLog($type='error', $message, $username=null) {
    $username = ($username) ? $username : $GLOBALS['organizrUser']['username'];
    if(file_exists($GLOBALS['organizrLog'])) {
        $getLog = str_replace("\r\ndate", "date", file_get_contents($GLOBALS['organizrLog']));
        $gotLog = json_decode($getLog, true);
    }
    $logEntryFirst = array('logType' => 'organizr_log', 'log_items' => array(array('date' => date("Y-m-d H:i:s"), 'utc_date' => $GLOBALS['currentTime'], 'type' => $type, 'username' => $username, 'ip' => userIP(), 'message' => $message)));
    $logEntry = array('date' => date("Y-m-d H:i:s"), 'utc_date' => $GLOBALS['currentTime'], 'type' => $type, 'username' => $username, 'ip' => userIP(), 'message' => $message);
    if(isset($gotLog)) {
        array_push($gotLog["log_items"], $logEntry);
        $writeFailLog = str_replace("date", "\r\ndate", json_encode($gotLog));
    } else {
        $writeFailLog = str_replace("date", "\r\ndate", json_encode($logEntryFirst));
    }
    file_put_contents($GLOBALS['organizrLog'], $writeFailLog);
};
function getLog($type,$reverse=true){
    switch ($type) {
        case 'login':
        case 'loginLog':
            $file = $GLOBALS['organizrLoginLog'];
            $parent = 'auth';
            break;
        case 'org':
        case 'organizrLog':
            $file = $GLOBALS['organizrLog'];
            $parent = 'log_items';
        default:
            break;
    }
    if(!file_exists($file)){
        return false;
    }
    $getLog = str_replace("\r\ndate", "date", file_get_contents($file));
    $gotLog = json_decode($getLog, true);
    return ($reverse) ? array_reverse($gotLog[$parent]) : $gotLog[$parent];
}
function random_ascii_string($len){
	$string = "";
	$max = strlen($this->ascii)-1;
	while($len-->0) { $string .= $this->ascii[mt_rand(0, $max)]; }
	return $string;
}
function encrypt($password, $key = null) {
    $key = (isset($GLOBALS['organizrHash'])) ? $GLOBALS['organizrHash'] : $key;
    return openssl_encrypt($password, 'AES-256-CBC', $key, 0, fillString($key,16));
}
function decrypt($password, $key = null) {
    $key = (isset($GLOBALS['organizrHash'])) ? $GLOBALS['organizrHash'] : $key;
    return openssl_decrypt($password, 'AES-256-CBC', $key, 0, fillString($key,16));
}
function fillString($string, $length){
    $filler = '0123456789abcdefghijklmnopqrstuvwxyz!@#$%^&*';
    if(strlen($string) < $length){
        $diff = $length - strlen($string);
        $filler = substr($filler,0,$diff);
        return $string.$filler;
    }elseif(strlen($string) > $length){
        return substr($string,0,$length);
    }else{
        return $string;
    }
    return $diff;
}
function userIP() {
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    if (strpos($ipaddress, ',') !== false) {
        list($first, $last) = explode(",", $ipaddress);
        return $first;
    }else{
        return $ipaddress;
    }

}
function arrayIP($string){
    if (strpos($string, ',') !== false) {
        $result = explode(",", $string);
    }else{
        $result = array($string);
    }
    foreach($result as &$ip){
        $ip = is_numeric(substr($ip, 0, 1)) ? $ip : gethostbyname($ip);
    }
    return $result;
}
function auth(){
    $debug = false; //CAREFUL WHEN SETTING TO TRUE AS THIS OPENS AUTH UP
    $ban = isset($_GET['ban']) ? strtoupper($_GET['ban']) : "";
    $whitelist = isset($_GET['whitelist']) ? $_GET['whitelist'] : false;
    $blacklist = isset($_GET['blacklist']) ? $_GET['blacklist'] : false;
    $group = isset($_GET['group']) ? $_GET['group'] : 0;
    $currentIP = userIP();
    $currentUser = $GLOBALS['organizrUser']['username'];
    if ($whitelist) {
        if(in_array($currentIP, arrayIP($whitelist))) {
           !$debug ? exit(http_response_code(200)) : die("$currentIP Whitelist Authorized");
    	}
    }
    if ($blacklist) {
        if(in_array($currentIP, arrayIP($blacklist))) {
           !$debug ? exit(http_response_code(401)) : die("$currentIP Blacklisted");
    	}
    }
    if($group !== null){
        if(qualifyRequest($group)){
            !$debug ? exit(http_response_code(200)) : die("$currentUser on $currentIP Authorized");
        }else{
            !$debug ? exit(http_response_code(401)) : die("$currentUser on $currentIP Not Authorized");
        }
    }else{
        !$debug ? exit(http_response_code(401)) : die("Not Authorized Due To No Parameters Set");
    }
}
