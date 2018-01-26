<?php

function login($array){
    // Grab username and Password from login form
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
        $authSuccess = false;
    	$function = 'plugin_auth_'.$GLOBALS['authBackend'];
        $result = $database->fetch('SELECT * FROM users WHERE username = ? COLLATE NOCASE OR email = ? COLLATE NOCASE',$username,$username);
    	switch ($GLOBALS['authType']) {
    		case 'external':
    			if (function_exists($function)) {
    				$authSuccess = $function($username, $password);
    			}
    			break;
    		case 'both':
    			if (function_exists($function)) {
    				$authSuccess = $function($username, $password);
    			}
    		default: // Internal
    			if (!$authSuccess) {
    				// perform the internal authentication step
    				if(password_verify($password, $result['password'])){
                        $authSuccess = true;
                    }
    			}
    	}
        if ($authSuccess) {
			// Make sure user exists in database
			$userExists = false;
            $token = (is_array($authSuccess) && isset($authSuccess['token']) ? $authSuccess['token'] : '');
            if($result['username']){
                $userExists = true;
				$username = $result['username'];
                $passwordMatches = (password_verify($password, $result['password'])) ? true : false;
            }
			if ($userExists) {
                //does org password need to be updated
                if(!$passwordMatches){
                    $database->query('
                    	UPDATE users SET', [
                    		'password' => password_hash($password, PASSWORD_BCRYPT)
                    	], '
                    	WHERE id=?', $result['id']);
                    writeLog('success', 'Login Function - User Password updated from backend', $username);
                }
				// authentication passed - 1) mark active and update token
                if(createToken($result['username'],$result['email'],$result['image'],$result['group'],$result['group_id'],$GLOBALS['organizrHash'],$days)){
                    writeLoginLog($username, 'success');
                    writeLog('success', 'Login Function - A User has logged in', $username);
                    ssoCheck($username, $password, $token); //need to work on this
                    return true;
                }else{
                    return 'error';
                }
			} else {
				// Create User
                ssoCheck($username, $password, $token);
                return authRegister((is_array($authSuccess) && isset($authSuccess['username']) ? $authSuccess['username'] : $username),$password,'',(is_array($authSuccess) && isset($authSuccess['email']) ? $authSuccess['email'] : ''));
			}
		} else {
			// authentication failed
            writeLoginLog($username, 'error');
            writeLog('error', 'Login Function - Wrong Password', $username);
			return 'mismatch';
		}
    } catch (Dibi\Exception $e) {
    	return 'error';
    }
}
function createDB($path,$filename) {
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
            `locked`	INTEGER,
    		`image`	TEXT,
            `register_date` DATE,
    		`auth_service`	TEXT DEFAULT \'internal\'
    	);');
        // Create Tokens
        $jwt = $createDB->query('CREATE TABLE `tokens` (
    		`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
    		`token`	TEXT UNIQUE,
    		`user_id`	INTEGER,
            `created` DATE,
            `expires` DATE
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
        $connect->disconnect();
    } catch (Dibi\Exception $e) {
        return $e;
    }
    // Remove Current Database
    $pathDigest = pathinfo($path.$filename);
    if (file_exists($path.$filename)) {
        copy($path.$filename, $pathDigest['dirname'].'/'.$pathDigest['filename'].'['.date('Y-m-d_H-i-s').']'.($oldVerNum?'['.$oldVerNum.']':'').'.bak.db');
        unlink($path.$filename);
    }
    // Create New Database
    $success = createDB($path,$filename);
    try {
        $GLOBALS['connect'] = new Dibi\Connection([
            'driver' => 'sqlite3',
            'database' => $path.$filename,
        ]);

        // Restore Items
        if ($success) {
            foreach($cache as $table => $tableData) {
                if ($tableData) {
                    $queryBase = 'INSERT INTO '.$table.' (`'.implode('`,`',array_keys(current($tableData))).'`) values ';
                    $insertValues = array();
                    reset($tableData);
                    foreach($tableData as $key => $value) {
                        $insertValues[] = '('.implode(',',array_map(function($d) {
                            return (isset($d)?str_replace('\/', '/',json_encode($d)):'null');
                        }, $value)).')';
                    }
                    $GLOBALS['connect']->query($queryBase.implode(',',$insertValues).';');
                }
            }
        }
        return true;
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
        writeLog('error', 'Wizard Function -  Error ['.$e.']', 'Wizard');
        return false;
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
        case 'editUser':
            try {
                $connect = new Dibi\Connection([
                    'driver' => 'sqlite3',
                    'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
                ]);
                if(!usernameTakenExcept($array['data']['username'],$array['data']['email'],$array['data']['id'])){
                    $connect->query('
                        UPDATE users SET', [
                            'username' => $array['data']['username'],
                            'email' => $array['data']['email'],
                        ], '
                        WHERE id=?', $array['data']['id']);
                    if(!empty($array['data']['password'])){
                        $connect->query('
                            UPDATE users SET', [
                                'password' => password_hash($array['data']['password'], PASSWORD_BCRYPT)
                            ], '
                            WHERE id=?', $array['data']['id']);
                    }
                    writeLog('success', 'User Management Function - User: '.$array['data']['username'].'\'s info was changed', $GLOBALS['organizrUser']['username']);
                    return true;
                }else{
                    return false;
                }
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
function allUsers(){
    try {
    	$connect = new Dibi\Connection([
    		'driver' => 'sqlite3',
    		'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
    	]);
        $users = $connect->fetchAll('SELECT * FROM users');
        $groups = $connect->fetchAll('SELECT * FROM groups ORDER BY group_id ASC');
        foreach ($users as $k => $v) {
            // clear password from array
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
function usernameTakenExcept($username,$email,$id){
    try {
    	$connect = new Dibi\Connection([
    		'driver' => 'sqlite3',
    		'database' => $GLOBALS['dbLocation'].$GLOBALS['dbName'],
    	]);
        $all = $connect->fetch('SELECT * FROM users WHERE id IS NOT ? AND username = ? COLLATE NOCASE OR id IS NOT ? AND email = ? COLLATE NOCASE',$id,$username,$id,$email);
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
