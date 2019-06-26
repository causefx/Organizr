<?php
function checkPlexAdminFilled()
{
	if ($GLOBALS['plexAdmin'] == '') {
		return false;
	} else {
		if ((strpos($GLOBALS['plexAdmin'], '@') !== false)) {
			return 'email';
		} else {
			return 'username';
		}
	}
}

function organizrSpecialSettings()
{
	$refreshSearch = "Refresh";
	$tautulliSearch = "tautulli_token";
	$tautulli = array_filter($_COOKIE, function ($k) use ($tautulliSearch) {
		return stripos($k, $tautulliSearch) !== false;
	}, ARRAY_FILTER_USE_KEY);
	return array(
		'homepage' => array(
			'refresh' => array_filter($GLOBALS, function ($k) use ($refreshSearch) {
				return stripos($k, $refreshSearch) !== false;
			}, ARRAY_FILTER_USE_KEY),
			'search' => array(
				'enabled' => (qualifyRequest($GLOBALS['mediaSearchAuth']) && $GLOBALS['mediaSearch'] == true && $GLOBALS['plexToken']) ? true : false,
				'type' => $GLOBALS['mediaSearchType'],
			),
			'ombi' => array(
				'enabled' => (qualifyRequest($GLOBALS['homepageOmbiAuth']) && qualifyRequest($GLOBALS['homepageOmbiRequestAuth']) && $GLOBALS['homepageOmbiEnabled'] == true && $GLOBALS['ssoOmbi'] && isset($_COOKIE['Auth'])) ? true : false,
				'authView' => (qualifyRequest($GLOBALS['homepageOmbiAuth'])) ? true : false,
				'authRequest' => (qualifyRequest($GLOBALS['homepageOmbiRequestAuth'])) ? true : false,
				'sso' => ($GLOBALS['ssoOmbi']) ? true : false,
				'cookie' => (isset($_COOKIE['Auth'])) ? true : false,
				'alias' => ($GLOBALS['ombiAlias']) ? true : false,
			),
			'options' => array(
				'alternateHomepageHeaders' => $GLOBALS['alternateHomepageHeaders'],
				'healthChecksTags' => $GLOBALS['healthChecksTags'],
			)
		),
		'sso' => array(
			'misc' => array(
				'oAuthLogin' => isset($_COOKIE['oAuth']) ? true : false,
				'rememberMe' => $GLOBALS['rememberMe'],
				'rememberMeDays' => $GLOBALS['rememberMeDays']
			),
			'plex' => array(
				'enabled' => ($GLOBALS['ssoPlex']) ? true : false,
				'cookie' => isset($_COOKIE['mpt']) ? true : false,
				'machineID' => (strlen($GLOBALS['plexID']) == 40) ? true : false,
				'token' => ($GLOBALS['plexToken'] !== '') ? true : false,
				'plexAdmin' => checkPlexAdminFilled(),
				'strict' => ($GLOBALS['plexStrictFriends']) ? true : false,
				'oAuthEnabled' => ($GLOBALS['plexoAuth']) ? true : false,
				'backend' => ($GLOBALS['authBackend'] == 'plex') ? true : false,
			),
			'ombi' => array(
				'enabled' => ($GLOBALS['ssoOmbi']) ? true : false,
				'cookie' => isset($_COOKIE['Auth']) ? true : false,
				'url' => ($GLOBALS['ombiURL'] !== '') ? $GLOBALS['ombiURL'] : false,
				'api' => ($GLOBALS['ombiToken'] !== '') ? true : false,
			),
			'tautulli' => array(
				'enabled' => ($GLOBALS['ssoTautulli']) ? true : false,
				'cookie' => !empty($tautulli) ? true : false,
				'url' => ($GLOBALS['tautulliURL'] !== '') ? $GLOBALS['tautulliURL'] : false,
			),
		),
		'ping' => array(
			'onlineSound' => $GLOBALS['pingOnlineSound'],
			'offlineSound' => $GLOBALS['pingOfflineSound'],
			'statusSounds' => $GLOBALS['statusSounds'],
			'auth' => $GLOBALS['pingAuth'],
			'authMessage' => $GLOBALS['pingAuthMessage'],
			'authMs' => $GLOBALS['pingAuthMs'],
			'ms' => $GLOBALS['pingMs'],
			'adminRefresh' => $GLOBALS['adminPingRefresh'],
			'everyoneRefresh' => $GLOBALS['otherPingRefresh'],
		),
		'notifications' => array(
			'backbone' => $GLOBALS['notificationBackbone'],
			'position' => $GLOBALS['notificationPosition']
		),
		'lockout' => array(
			'enabled' => $GLOBALS['lockoutSystem'],
			'timer' => $GLOBALS['lockoutTimeout'],
			'minGroup' => $GLOBALS['lockoutMinAuth'],
			'maxGroup' => $GLOBALS['lockoutMaxAuth']
		),
		'user' => array(
			'agent' => isset($_SERVER ['HTTP_USER_AGENT']) ? $_SERVER ['HTTP_USER_AGENT'] : null,
			'oAuthLogin' => isset($_COOKIE['oAuth']) ? true : false,
			'local' => (isLocal()) ? true : false,
			'ip' => userIP()
		),
		'login' => array(
			'rememberMe' => $GLOBALS['rememberMe'],
			'rememberMeDays' => $GLOBALS['rememberMeDays'],
		),
		'misc' => array(
			'installedPlugins' => qualifyRequest(1) ? $GLOBALS['installedPlugins'] : '',
			'installedThemes' => qualifyRequest(1) ? $GLOBALS['installedThemes'] : '',
			'return' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false,
			'authDebug' => $GLOBALS['authDebug'],
			'minimalLoginScreen' => $GLOBALS['minimalLoginScreen'],
			'unsortedTabs' => $GLOBALS['unsortedTabs'],
			'authType' => $GLOBALS['authType'],
			'authBackend' => $GLOBALS['authBackend'],
			'newMessageSound' => (isset($GLOBALS['CHAT-newMessageSound-include'])) ? $GLOBALS['CHAT-newMessageSound-include'] : '',
			'uuid' => $GLOBALS['uuid'],
			'docker' => qualifyRequest(1) ? $GLOBALS['docker'] : '',
			'githubCommit' => qualifyRequest(1) ? $GLOBALS['commit'] : '',
			'schema' => qualifyRequest(1) ? getSchema() : '',
			'debugArea' => qualifyRequest($GLOBALS['debugAreaAuth']),
			'debugErrors' => $GLOBALS['debugErrors'],
			'sandbox' => $GLOBALS['sandbox'],
		)
	);
}

function wizardConfig($array)
{
	foreach ($array['data'] as $items) {
		foreach ($items as $key => $value) {
			if ($key == 'name') {
				$newKey = $value;
			}
			if ($key == 'value') {
				$newValue = $value;
			}
			if (isset($newKey) && isset($newValue)) {
				$$newKey = $newValue;
			}
		}
	}
	$location = cleanDirectory($location);
	$dbName = dbExtension($dbName);
	$configVersion = $GLOBALS['installedVersion'];
	$configArray = array(
		'dbName' => $dbName,
		'dbLocation' => $location,
		'license' => $license,
		'organizrHash' => $hashKey,
		'organizrAPI' => $api,
		'registrationPassword' => $registrationPassword,
	);
	// Create Config
	$GLOBALS['dbLocation'] = $location;
	$GLOBALS['dbName'] = $dbName;
	if (createConfig($configArray)) {
		// Call DB Create
		if (createDB($location, $dbName)) {
			// Add in first user
			if (createFirstAdmin($location, $dbName, $username, $password, $email)) {
				if (createToken($username, $email, gravatar($email), 'Admin', 0, $hashKey, 1)) {
					return true;
				} else {
					return 'token';
				}
			} else {
				return 'admin';
			}
		} else {
			return 'db';
		}
	} else {
		return 'config';
	}
	return false;
}

function register($array)
{
	// Grab username and password from login form
	foreach ($array['data'] as $items) {
		foreach ($items as $key => $value) {
			if ($key == 'name') {
				$newKey = $value;
			}
			if ($key == 'value') {
				$newValue = $value;
			}
			if (isset($newKey) && isset($newValue)) {
				$$newKey = $newValue;
			}
		}
	}
	if ($registrationPassword == $GLOBALS['registrationPassword']) {
		$defaults = defaultUserGroup();
		writeLog('success', 'Registration Function - Registration Password Verified', $username);
		if (createUser($username, $password, $defaults, $email)) {
			writeLog('success', 'Registration Function - A User has registered', $username);
			if (createToken($username, $email, gravatar($email), $defaults['group'], $defaults['group_id'], $GLOBALS['organizrHash'], $GLOBALS['rememberMeDays'])) {
				writeLoginLog($username, 'success');
				writeLog('success', 'Login Function - A User has logged in', $username);
				return true;
			}
		} else {
			writeLog('error', 'Registration Function - An error occured', $username);
			return 'username taken';
		}
	} else {
		writeLog('warning', 'Registration Function - Wrong Password', $username);
		return 'mismatch';
	}
}

function removeFile($array)
{
	$filePath = $array['data']['path'];
	$fileName = $array['data']['name'];
	if (file_exists($filePath)) {
		if (unlink($filePath)) {
			writeLog('success', 'Log Management Function - Log: ' . $fileName . ' has been purged/deleted', 'SYSTEM');
			return true;
		} else {
			writeLog('error', 'Log Management Function - Log: ' . $fileName . ' - Error Occured', 'SYSTEM');
			return false;
		}
	} else {
		writeLog('error', 'Log Management Function - Log: ' . $fileName . ' does not exist', 'SYSTEM');
		return false;
	}
}

function recover($array)
{
	$email = $array['data']['email'];
	$newPassword = randString(10);
	try {
		$connect = new Dibi\Connection([
			'driver' => 'sqlite3',
			'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
		]);
		$isUser = $connect->fetch('SELECT * FROM users WHERE email = ? COLLATE NOCASE', $email);
		if ($isUser) {
			$connect->query('
                UPDATE users SET', [
				'password' => password_hash($newPassword, PASSWORD_BCRYPT)
			], '
                WHERE email=? COLLATE NOCASE', $email);
			if ($GLOBALS['PHPMAILER-enabled']) {
				$emailTemplate = array(
					'type' => 'reset',
					'body' => $GLOBALS['PHPMAILER-emailTemplateResetPassword'],
					'subject' => $GLOBALS['PHPMAILER-emailTemplateResetPasswordSubject'],
					'user' => $isUser['username'],
					'password' => $newPassword,
					'inviteCode' => null,
				);
				$emailTemplate = phpmEmailTemplate($emailTemplate);
				$sendEmail = array(
					'to' => $email,
					'user' => $isUser['username'],
					'subject' => $emailTemplate['subject'],
					'body' => phpmBuildEmail($emailTemplate),
				);
				phpmSendEmail($sendEmail);
			}
			writeLog('success', 'User Management Function - User: ' . $isUser['username'] . '\'s password was reset', $isUser['username']);
			return true;
		} else {
			writeLog('error', 'User Management Function - Error - User: ' . $email . ' An error Occured', $email);
			return 'an error occured';
		}
	} catch (Dibi\Exception $e) {
		writeLog('error', 'User Management Function - Error - User: ' . $email . ' An error Occured', $email);
		return 'an error occured';
	}
}

function unlock($array)
{
	if ($array['data']['password'] == '') {
		return 'Password Not Set';
	}
	try {
		$connect = new Dibi\Connection([
			'driver' => 'sqlite3',
			'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
		]);
		$result = $connect->fetch('SELECT * FROM users WHERE id = ?', $GLOBALS['organizrUser']['userID']);
		if (!password_verify($array['data']['password'], $result['password'])) {
			return 'Password Incorrect';
		}
		$connect->query('
            UPDATE users SET', [
			'locked' => ''
		], '
            WHERE id=?', $GLOBALS['organizrUser']['userID']);
		writeLog('success', 'User Lockout Function - User: ' . $GLOBALS['organizrUser']['username'] . '\'s account unlocked', $GLOBALS['organizrUser']['username']);
		return true;
	} catch (Dibi\Exception $e) {
		writeLog('error', 'User Management Function - Error - User: ' . $GLOBALS['organizrUser']['username'] . ' An error Occured', $GLOBALS['organizrUser']['username']);
		return 'an error occured';
	}
}

function lock()
{
	if ($GLOBALS['organizrUser']['userID'] == '999') {
		return 'Not Allowed on Guest';
	}
	try {
		$connect = new Dibi\Connection([
			'driver' => 'sqlite3',
			'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
		]);
		$connect->query('
            UPDATE users SET', [
			'locked' => '1'
		], '
            WHERE id=?', $GLOBALS['organizrUser']['userID']);
		writeLog('success', 'User Lockout Function - User: ' . $GLOBALS['organizrUser']['username'] . '\'s account unlocked', $GLOBALS['organizrUser']['username']);
		return true;
	} catch (Dibi\Exception $e) {
		writeLog('error', 'User Management Function - Error - User: ' . $GLOBALS['organizrUser']['username'] . ' An error Occured', $GLOBALS['organizrUser']['username']);
		return 'an error occured';
	}
}

function editUser($array)
{
	if ($array['data']['username'] == '' && $array['data']['username'] == '') {
		return 'Username/email not set';
	}
	try {
		$connect = new Dibi\Connection([
			'driver' => 'sqlite3',
			'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
		]);
		if (!usernameTakenExcept($array['data']['username'], $array['data']['email'], $GLOBALS['organizrUser']['userID'])) {
			$connect->query('
                UPDATE users SET', [
				'username' => $array['data']['username'],
				'email' => $array['data']['email'],
				'image' => gravatar($array['data']['email']),
			], '
                WHERE id=?', $GLOBALS['organizrUser']['userID']);
			if (!empty($array['data']['password'])) {
				$connect->query('
                    UPDATE users SET', [
					'password' => password_hash($array['data']['password'], PASSWORD_BCRYPT)
				], '
                    WHERE id=?', $GLOBALS['organizrUser']['userID']);
			}
			writeLog('success', 'User Management Function - User: ' . $array['data']['username'] . '\'s info was changed', $GLOBALS['organizrUser']['username']);
			return true;
		} else {
			return 'Username/Email Already Taken';
		}
	} catch (Dibi\Exception $e) {
		writeLog('error', 'User Management Function - Error - User: ' . $array['data']['username'] . ' An error Occured', $GLOBALS['organizrUser']['username']);
		return 'an error occured';
	}
}

function clearTautulliTokens()
{
	foreach (array_keys($_COOKIE) as $k => $v) {
		if (strpos($v, 'tautulli') !== false) {
			coookie('delete', $v);
		}
	}
}

function logout()
{
	coookie('delete', $GLOBALS['cookieName']);
	coookie('delete', 'mpt');
	coookie('delete', 'Auth');
	coookie('delete', 'oAuth');
	clearTautulliTokens();
	revokeToken(array('data' => array('token' => $GLOBALS['organizrUser']['token'])));
	$GLOBALS['organizrUser'] = false;
	return true;
}

function qualifyRequest($accessLevelNeeded)
{
	if (getUserLevel() <= $accessLevelNeeded && getUserLevel() !== null) {
		return true;
	} else {
		return false;
	}
}

function isApprovedRequest($method)
{
	$requesterToken = isset(getallheaders()['Token']) ? getallheaders()['Token'] : (isset($_GET['apikey']) ? $_GET['apikey'] : false);
	if (isset($_POST['data']['formKey'])) {
		$formKey = $_POST['data']['formKey'];
	} elseif (isset(getallheaders()['Formkey'])) {
		$formKey = getallheaders()['Formkey'];
	} elseif (isset(getallheaders()['formkey'])) {
		$formKey = getallheaders()['formkey'];
	} elseif (isset(getallheaders()['formKey'])) {
		$formKey = getallheaders()['formKey'];
	} elseif (isset(getallheaders()['FormKey'])) {
		$formKey = getallheaders()['FormKey'];
	} else {
		$formKey = false;
	}
	// Check token or API key
	// If API key, return 0 for admin
	if (strlen($requesterToken) == 20 && $requesterToken == $GLOBALS['organizrAPI']) {
		//DO API CHECK
		return true;
	} elseif ($method == 'POST') {
		if (checkFormKey($formKey)) {
			return true;
		} else {
			writeLog('error', 'API ERROR: Unable to authenticate Form Key: ' . $formKey, $GLOBALS['organizrUser']['username']);
		}
	} else {
		return true;
	}
	return false;
}

function getUserLevel()
{
	// Grab token
	//$requesterToken = isset(getallheaders()['Token']) ? getallheaders()['Token'] : false;
	$requesterToken = isset(getallheaders()['Token']) ? getallheaders()['Token'] : (isset($_GET['apikey']) ? $_GET['apikey'] : false);
	// Check token or API key
	// If API key, return 0 for admin
	if (strlen($requesterToken) == 20 && $requesterToken == $GLOBALS['organizrAPI']) {
		//DO API CHECK
		return 0;
	} elseif (isset($GLOBALS['organizrUser'])) {
		return $GLOBALS['organizrUser']['groupID'];
	}
	// All else fails?  return guest id
	return 999;
}

function organizrStatus()
{
	$status = array();
	$dependenciesActive = array();
	$dependenciesInactive = array();
	$extensions = array("PDO_SQLITE", "PDO", "SQLITE3", "zip", "cURL", "openssl", "simplexml", "json", "session", "filter");
	$functions = array("hash", "fopen", "fsockopen", "fwrite", "fclose", "readfile");
	foreach ($extensions as $check) {
		if (extension_loaded($check)) {
			array_push($dependenciesActive, $check);
		} else {
			array_push($dependenciesInactive, $check);
		}
	}
	foreach ($functions as $check) {
		if (function_exists($check)) {
			array_push($dependenciesActive, $check);
		} else {
			array_push($dependenciesInactive, $check);
		}
	}
	if (!file_exists('config' . DIRECTORY_SEPARATOR . 'config.php')) {
		$status['status'] = "wizard";//wizard - ok for test
	}
	if (count($dependenciesInactive) > 0 || !is_writable(dirname(__DIR__, 2)) || !(version_compare(PHP_VERSION, $GLOBALS['minimumPHP']) >= 0)) {
		$status['status'] = "dependencies";
	}
	$status['status'] = (!empty($status['status'])) ? $status['status'] : $status['status'] = "ok";
	$status['writable'] = is_writable(dirname(__DIR__, 2)) ? 'yes' : 'no';
	$status['minVersion'] = (version_compare(PHP_VERSION, $GLOBALS['minimumPHP']) >= 0) ? 'yes' : 'no';
	$status['dependenciesActive'] = $dependenciesActive;
	$status['dependenciesInactive'] = $dependenciesInactive;
	$status['version'] = $GLOBALS['installedVersion'];
	$status['os'] = getOS();
	$status['php'] = phpversion();
	return $status;
}

function pathsWritable($paths)
{
	$results = array();
	foreach ($paths as $k => $v) {
		$results[$k] = is_writable($v);
	}
	return $results;
}

function getSettingsMain()
{
	return array(
		'Github' => array(
			array(
				'type' => 'select',
				'name' => 'branch',
				'label' => 'Branch',
				'value' => $GLOBALS['branch'],
				'options' => getBranches(),
				'disabled' => $GLOBALS['docker'],
				'help' => ($GLOBALS['docker']) ? 'Since you are using the Official Docker image, Change the image to change the branch' : 'Choose which branch to download from'
			),
			array(
				'type' => 'button',
				'name' => 'force-install-branch',
				'label' => 'Force Install Branch',
				'class' => 'updateNow',
				'icon' => 'fa fa-download',
				'text' => 'Retrieve',
				'attr' => ($GLOBALS['docker']) ? 'title="You can just restart your docker to update"' : '',
				'help' => ($GLOBALS['docker']) ? 'Since you are using the Official Docker image, You can just restart your docker to update' : 'This will re-download all of the source files for Organizr'
			)
		),
		'API' => array(
			array(
				'type' => 'password-alt',
				'name' => 'organizrAPI',
				'label' => 'Organizr API',
				'value' => $GLOBALS['organizrAPI']
			),
			array(
				'type' => 'button',
				'label' => 'Generate New API Key',
				'class' => 'newAPIKey',
				'icon' => 'fa fa-refresh',
				'text' => 'Generate'
			)
		),
		'Authentication' => array(
			array(
				'type' => 'select',
				'name' => 'authType',
				'id' => 'authSelect',
				'label' => 'Authentication Type',
				'value' => $GLOBALS['authType'],
				'options' => getAuthTypes()
			),
			array(
				'type' => 'select',
				'name' => 'authBackend',
				'id' => 'authBackendSelect',
				'label' => 'Authentication Backend',
				'class' => 'backendAuth switchAuth',
				'value' => $GLOBALS['authBackend'],
				'options' => getAuthBackends()
			),
			array(
				'type' => 'password-alt',
				'name' => 'plexToken',
				'class' => 'plexAuth switchAuth',
				'label' => 'Plex Token',
				'value' => $GLOBALS['plexToken'],
				'placeholder' => 'Use Get Token Button'
			),
			array(
				'type' => 'button',
				'label' => 'Get Plex Token',
				'class' => 'popup-with-form getPlexTokenAuth plexAuth switchAuth',
				'icon' => 'fa fa-ticket',
				'text' => 'Retrieve',
				'href' => '#auth-plex-token-form',
				'attr' => 'data-effect="mfp-3d-unfold"'
			),
			array(
				'type' => 'password-alt',
				'name' => 'plexID',
				'class' => 'plexAuth switchAuth',
				'label' => 'Plex Machine',
				'value' => $GLOBALS['plexID'],
				'placeholder' => 'Use Get Plex Machine Button'
			),
			array(
				'type' => 'button',
				'label' => 'Get Plex Machine',
				'class' => 'popup-with-form getPlexMachineAuth plexAuth switchAuth',
				'icon' => 'fa fa-id-badge',
				'text' => 'Retrieve',
				'href' => '#auth-plex-machine-form',
				'attr' => 'data-effect="mfp-3d-unfold"'
			),
			array(
				'type' => 'input',
				'name' => 'plexAdmin',
				'label' => 'Admin Username',
				'class' => 'plexAuth switchAuth',
				'value' => $GLOBALS['plexAdmin'],
				'placeholder' => 'Admin username for Plex'
			),
			array(
				'type' => 'switch',
				'name' => 'plexoAuth',
				'label' => 'Enable Plex oAuth',
				'class' => 'plexAuth switchAuth',
				'value' => $GLOBALS['plexoAuth']
			),
			array(
				'type' => 'switch',
				'name' => 'plexStrictFriends',
				'label' => 'Strict Plex Friends ',
				'class' => 'plexAuth switchAuth',
				'value' => $GLOBALS['plexStrictFriends'],
				'help' => 'Enabling this will only allow Friends that have shares to the Machine ID entered above to login, Having this disabled will allow all Friends on your Friends list to login'
			),
			array(
				'type' => 'input',
				'name' => 'authBackendHost',
				'class' => 'ldapAuth ftpAuth switchAuth',
				'label' => 'Host Address',
				'value' => $GLOBALS['authBackendHost'],
				'placeholder' => 'http{s) | ftp(s) | ldap(s)://hostname:port'
			),
			array(
				'type' => 'input',
				'name' => 'authBaseDN',
				'class' => 'ldapAuth switchAuth',
				'label' => 'Host Base DN',
				'value' => $GLOBALS['authBaseDN'],
				'placeholder' => 'cn=%s,dc=sub,dc=domain,dc=com'
			),
			array(
				'type' => 'select',
				'name' => 'ldapType',
				'id' => 'ldapType',
				'label' => 'LDAP Backend Type',
				'class' => 'ldapAuth switchAuth',
				'value' => $GLOBALS['ldapType'],
				'options' => getLDAPOptions()
			),
			array(
				'type' => 'input',
				'name' => 'authBackendHostPrefix',
				'class' => 'ldapAuth switchAuth',
				'label' => 'Account Prefix',
				'id' => 'authBackendHostPrefix-input',
				'value' => $GLOBALS['authBackendHostPrefix'],
				'placeholder' => 'Account prefix - i.e. Controller\ from Controller\Username for AD - uid= for OpenLDAP'
			),
			array(
				'type' => 'input',
				'name' => 'authBackendHostSuffix',
				'class' => 'ldapAuth switchAuth',
				'label' => 'Account Suffix',
				'id' => 'authBackendHostSuffix-input',
				'value' => $GLOBALS['authBackendHostSuffix'],
				'placeholder' => 'Account suffix - start with comma - ,ou=people,dc=domain,dc=tld'
			),
			array(
				'type' => 'input',
				'name' => 'ldapBindUsername',
				'class' => 'ldapAuth switchAuth',
				'label' => 'Bind Username',
				'value' => $GLOBALS['ldapBindUsername'],
				'placeholder' => ''
			),
			array(
				'type' => 'password',
				'name' => 'ldapBindPassword',
				'class' => 'ldapAuth switchAuth',
				'label' => 'Password',
				'value' => $GLOBALS['ldapBindPassword']
			),
			array(
				'type' => 'html',
				'class' => 'ldapAuth switchAuth',
				'label' => 'Account DN',
				'html' => '<span id="accountDN" class="ldapAuth switchAuth">' . $GLOBALS['authBackendHostPrefix'] . 'TestAcct' . $GLOBALS['authBackendHostSuffix'] . '</span>'
			),
			array(
				'type' => 'button',
				'name' => 'test-button-ldap',
				'label' => 'Test Connection',
				'icon' => 'fa fa-flask',
				'class' => 'ldapAuth switchAuth',
				'text' => 'Test Connection',
				'attr' => 'onclick="testAPIConnection(\'ldap\')"',
				'help' => 'Remember! Please save before using the test button!'
			),
			array(
				'type' => 'button',
				'name' => 'test-button-ldap-login',
				'label' => 'Test Login',
				'icon' => 'fa fa-flask',
				'class' => 'ldapAuth switchAuth',
				'text' => 'Test Login',
				'attr' => 'onclick="showLDAPLoginTest()"'
			),
			array(
				'type' => 'input',
				'name' => 'embyURL',
				'class' => 'embyAuth switchAuth',
				'label' => 'Emby URL',
				'value' => $GLOBALS['embyURL'],
				'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
				'placeholder' => 'http(s)://hostname:port'
			),
			array(
				'type' => 'password-alt',
				'name' => 'embyToken',
				'class' => 'embyAuth switchAuth',
				'label' => 'Emby Token',
				'value' => $GLOBALS['embyToken'],
				'placeholder' => ''
			),
			/*array(
				'type' => 'button',
				'label' => 'Send Test',
				'class' => 'phpmSendTestEmail',
				'icon' => 'fa fa-paper-plane',
				'text' => 'Send'
			)*/
		),
		'Security' => array(
			array(
				'type' => 'number',
				'name' => 'loginAttempts',
				'label' => 'Max Login Attempts',
				'value' => $GLOBALS['loginAttempts'],
				'placeholder' => ''
			),
			array(
				'type' => 'select',
				'name' => 'loginLockout',
				'label' => 'Login Lockout Seconds',
				'value' => $GLOBALS['loginLockout'],
				'options' => optionTime()
			),
			array(
				'type' => 'number',
				'name' => 'lockoutTimeout',
				'label' => 'Inactivity Timer [Minutes]',
				'value' => $GLOBALS['lockoutTimeout'],
				'placeholder' => ''
			),
			array(
				'type' => 'select',
				'name' => 'lockoutMinAuth',
				'label' => 'Lockout Groups From',
				'value' => $GLOBALS['lockoutMinAuth'],
				'options' => groupSelect()
			),
			array(
				'type' => 'select',
				'name' => 'lockoutMaxAuth',
				'label' => 'Lockout Groups To',
				'value' => $GLOBALS['lockoutMaxAuth'],
				'options' => groupSelect()
			),
			array(
				'type' => 'switch',
				'name' => 'lockoutSystem',
				'label' => 'Inactivity Lock',
				'value' => $GLOBALS['lockoutSystem']
			),
			array(
				'type' => 'select',
				'name' => 'debugAreaAuth',
				'label' => 'Minimum Authentication for Debug Area',
				'value' => $GLOBALS['debugAreaAuth'],
				'options' => groupSelect()
			),
			array(
				'type' => 'switch',
				'name' => 'authDebug',
				'label' => 'Nginx Auth Debug',
				'help' => 'Important! Do not keep this enabled for too long as this opens up Authentication while testing.',
				'value' => $GLOBALS['authDebug'],
				'class' => 'authDebug'
			),
			array(
				'type' => 'select2',
				'class' => 'select2-multiple',
				'id' => 'sandbox-select',
				'name' => 'sandbox',
				'label' => 'iFrame Sandbox',
				'value' => $GLOBALS['sandbox'],
				'help' => 'WARNING! This can potentially mess up your iFrames',
				'options' => array(
					array(
						'name' => 'Allow Presentation',
						'value' => 'allow-presentation'
					),
					array(
						'name' => 'Allow Forms',
						'value' => 'allow-forms'
					),
					array(
						'name' => 'Allow Same Origin',
						'value' => 'allow-same-origin'
					),
					array(
						'name' => 'Allow Pointer Lock',
						'value' => 'allow-pointer-lock'
					),
					array(
						'name' => 'Allow Scripts',
						'value' => 'allow-scripts'
					), array(
						'name' => 'Allow Popups',
						'value' => 'allow-popups'
					),
					array(
						'name' => 'Allow Modals',
						'value' => 'allow-modals'
					),
					array(
						'name' => 'Allow Top Navigation',
						'value' => 'allow-top-navigation'
					),
				)
			)
		),
		'Login' => array(
			array(
				'type' => 'password-alt',
				'name' => 'registrationPassword',
				'label' => 'Registration Password',
				'help' => 'Sets the password for the Registration form on the login screen',
				'value' => $GLOBALS['registrationPassword'],
			),
			array(
				'type' => 'switch',
				'name' => 'hideRegistration',
				'label' => 'Hide Registration',
				'help' => 'Enable this to hide the Registration button on the login screen',
				'value' => $GLOBALS['hideRegistration'],
			),
			array(
				'type' => 'number',
				'name' => 'rememberMeDays',
				'label' => 'Remember Me Length',
				'help' => 'Number of days cookies and tokens will be valid for',
				'value' => $GLOBALS['rememberMeDays'],
				'placeholder' => '',
				'attr' => 'min="1"'
			),
			array(
				'type' => 'switch',
				'name' => 'rememberMe',
				'label' => 'Remember Me',
				'help' => 'Default status of Remember Me button on login screen',
				'value' => $GLOBALS['rememberMe'],
			),
			array(
				'type' => 'input',
				'name' => 'localIPFrom',
				'label' => 'Override Local IP From',
				'value' => $GLOBALS['localIPFrom'],
				'placeholder' => 'i.e. 123.123.123.123',
				'help' => 'IPv4 only at the moment - This will set your login as local if your IP falls within the From and To'
			),
			array(
				'type' => 'input',
				'name' => 'localIPTo',
				'label' => 'Override Local IP To',
				'value' => $GLOBALS['localIPTo'],
				'placeholder' => 'i.e. 123.123.123.123',
				'help' => 'IPv4 only at the moment - This will set your login as local if your IP falls within the From and To'
			),
		),
		'Ping' => array(
			array(
				'type' => 'select',
				'name' => 'pingAuth',
				'label' => 'Minimum Authentication',
				'value' => $GLOBALS['pingAuth'],
				'options' => groupSelect()
			),
			array(
				'type' => 'select',
				'name' => 'pingAuthMessage',
				'label' => 'Minimum Authentication for Message and Sound',
				'value' => $GLOBALS['pingAuthMessage'],
				'options' => groupSelect()
			),
			array(
				'type' => 'select',
				'name' => 'pingOnlineSound',
				'label' => 'Online Sound',
				'value' => $GLOBALS['pingOnlineSound'],
				'options' => getSounds()
			),
			array(
				'type' => 'select',
				'name' => 'pingOfflineSound',
				'label' => 'Offline Sound',
				'value' => $GLOBALS['pingOfflineSound'],
				'options' => getSounds()
			),
			array(
				'type' => 'switch',
				'name' => 'pingMs',
				'label' => 'Show Ping Time',
				'value' => $GLOBALS['pingMs']
			),
			array(
				'type' => 'switch',
				'name' => 'statusSounds',
				'label' => 'Enable Notify Sounds',
				'value' => $GLOBALS['statusSounds'],
				'help' => 'Will play a sound if the server goes down and will play sound if comes back up.',
			),
			array(
				'type' => 'select',
				'name' => 'pingAuthMs',
				'label' => 'Minimum Authentication for Time Display',
				'value' => $GLOBALS['pingAuthMs'],
				'options' => groupSelect()
			),
			array(
				'type' => 'select',
				'name' => 'adminPingRefresh',
				'label' => 'Admin Refresh Seconds',
				'value' => $GLOBALS['adminPingRefresh'],
				'options' => optionTime()
			),
			array(
				'type' => 'select',
				'name' => 'otherPingRefresh',
				'label' => 'Everyone Refresh Seconds',
				'value' => $GLOBALS['otherPingRefresh'],
				'options' => optionTime()
			),
		)
	);
}

function getSSO()
{
	return array(
		'FYI' => array(
			array(
				'type' => 'html',
				'label' => 'Important Information',
				'override' => 12,
				'html' => '
				<div class="row">
						    <div class="col-lg-12">
						        <div class="panel panel-info">
						            <div class="panel-heading">
						                <span lang="en">Notice</span>
						            </div>
						            <div class="panel-wrapper collapse in" aria-expanded="true">
						                <div class="panel-body">
						                    <span lang="en">This is not the same as database authentication - i.e. Plex Authentication | Emby Authentication | FTP Authentication<br/>Click Main on the sub-menu above.</span>
						                </div>
						            </div>
						        </div>
						    </div>
						</div>
				'
			)
		),
		'Plex' => array(
			array(
				'type' => 'password-alt',
				'name' => 'plexToken',
				'label' => 'Plex Token',
				'value' => $GLOBALS['plexToken'],
				'placeholder' => 'Use Get Token Button'
			),
			array(
				'type' => 'button',
				'label' => 'Get Plex Token',
				'class' => 'popup-with-form getPlexTokenSSO',
				'icon' => 'fa fa-ticket',
				'text' => 'Retrieve',
				'href' => '#sso-plex-token-form',
				'attr' => 'data-effect="mfp-3d-unfold"'
			),
			array(
				'type' => 'password-alt',
				'name' => 'plexID',
				'label' => 'Plex Machine',
				'value' => $GLOBALS['plexID'],
				'placeholder' => 'Use Get Plex Machine Button'
			),
			array(
				'type' => 'button',
				'label' => 'Get Plex Machine',
				'class' => 'popup-with-form getPlexMachineSSO',
				'icon' => 'fa fa-id-badge',
				'text' => 'Retrieve',
				'href' => '#sso-plex-machine-form',
				'attr' => 'data-effect="mfp-3d-unfold"'
			),
			array(
				'type' => 'input',
				'name' => 'plexAdmin',
				'label' => 'Admin Username',
				'value' => $GLOBALS['plexAdmin'],
				'placeholder' => 'Admin username for Plex'
			),
			array(
				'type' => 'blank',
				'label' => ''
			),
			array(
				'type' => 'html',
				'label' => 'Plex Note',
				'html' => '<span lang="en">Please make sure both Token and Machine are filled in</span>'
			),
			array(
				'type' => 'switch',
				'name' => 'ssoPlex',
				'label' => 'Enable',
				'value' => $GLOBALS['ssoPlex']
			)
		),
		'Tautulli' => array(
			array(
				'type' => 'input',
				'name' => 'tautulliURL',
				'label' => 'Tautulli URL',
				'value' => $GLOBALS['tautulliURL'],
				'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
				'placeholder' => 'http(s)://hostname:port'
			),
			array(
				'type' => 'switch',
				'name' => 'ssoTautulli',
				'label' => 'Enable',
				'value' => $GLOBALS['ssoTautulli']
			)
		),
		'Ombi' => array(
			array(
				'type' => 'input',
				'name' => 'ombiURL',
				'label' => 'Ombi URL',
				'value' => $GLOBALS['ombiURL'],
				'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
				'placeholder' => 'http(s)://hostname:port'
			),
			array(
				'type' => 'password-alt',
				'name' => 'ombiToken',
				'label' => 'Token',
				'value' => $GLOBALS['ombiToken']
			),
			array(
				'type' => 'switch',
				'name' => 'ssoOmbi',
				'label' => 'Enable',
				'value' => $GLOBALS['ssoOmbi']
			)
		)
	);
}

function loadAppearance()
{
	$appearance = array();
	$appearance['logo'] = $GLOBALS['logo'];
	$appearance['title'] = $GLOBALS['title'];
	$appearance['useLogo'] = $GLOBALS['useLogo'];
	$appearance['headerColor'] = $GLOBALS['headerColor'];
	$appearance['headerTextColor'] = $GLOBALS['headerTextColor'];
	$appearance['sidebarColor'] = $GLOBALS['sidebarColor'];
	$appearance['headerTextColor'] = $GLOBALS['headerTextColor'];
	$appearance['sidebarTextColor'] = $GLOBALS['sidebarTextColor'];
	$appearance['accentColor'] = $GLOBALS['accentColor'];
	$appearance['accentTextColor'] = $GLOBALS['accentTextColor'];
	$appearance['buttonColor'] = $GLOBALS['buttonColor'];
	$appearance['buttonTextColor'] = $GLOBALS['buttonTextColor'];
	$appearance['buttonTextHoverColor'] = $GLOBALS['buttonTextHoverColor'];
	$appearance['buttonHoverColor'] = $GLOBALS['buttonHoverColor'];
	$appearance['loginWallpaper'] = $GLOBALS['loginWallpaper'];
	$appearance['customCss'] = $GLOBALS['customCss'];
	$appearance['customThemeCss'] = $GLOBALS['customThemeCss'];
	$appearance['customJava'] = $GLOBALS['customJava'];
	$appearance['customThemeJava'] = $GLOBALS['customThemeJava'];
	return $appearance;
}

function getCustomizeAppearance()
{
	if (file_exists(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php')) {
		return array(
			'Top Bar' => array(
				array(
					'type' => 'input',
					'name' => 'logo',
					'label' => 'Logo',
					'value' => $GLOBALS['logo']
				),
				array(
					'type' => 'input',
					'name' => 'title',
					'label' => 'Title',
					'value' => $GLOBALS['title']
				),
				array(
					'type' => 'switch',
					'name' => 'useLogo',
					'label' => 'Use Logo instead of Title',
					'value' => $GLOBALS['useLogo'],
					'help' => 'Also sets the title of your site'
				),
				array(
					'type' => 'input',
					'name' => 'description',
					'label' => 'Meta Description',
					'value' => $GLOBALS['description'],
					'help' => 'Used to set the description for SEO meta tags'
				),
			),
			'Login Page' => array(
				array(
					'type' => 'input',
					'name' => 'loginWallpaper',
					'label' => 'Login Wallpaper',
					'value' => $GLOBALS['loginWallpaper']
				),
				array(
					'type' => 'switch',
					'name' => 'minimalLoginScreen',
					'label' => 'Minimal Login Screen',
					'value' => $GLOBALS['minimalLoginScreen']
				)
			),
			'Options' => array(
				array(
					'type' => 'switch',
					'name' => 'alternateHomepageHeaders',
					'label' => 'Alternate Homepage Titles',
					'value' => $GLOBALS['alternateHomepageHeaders']
				),
				array(
					'type' => 'switch',
					'name' => 'debugErrors',
					'label' => 'Show Debug Errors',
					'value' => $GLOBALS['debugErrors']
				),
				array(
					'type' => 'select',
					'name' => 'unsortedTabs',
					'label' => 'Unsorted Tab Placement',
					'value' => $GLOBALS['unsortedTabs'],
					'options' => array(
						array(
							'name' => 'Top',
							'value' => 'top'
						),
						array(
							'name' => 'Bottom',
							'value' => 'bottom'
						)
					)
				),
				array(
					'type' => 'input',
					'name' => 'gaTrackingID',
					'label' => 'Google Analytics Tracking ID',
					'placeholder' => 'e.g. UA-XXXXXXXXX-X',
					'value' => $GLOBALS['gaTrackingID']
				)
			),
			'Colors & Themes' => array(
				array(
					'type' => 'html',
					'override' => 12,
					'label' => 'Custom CSS [Can replace colors from above]',
					'html' => '
					<div class="row">
					    <div class="col-lg-12">
					        <div class="panel panel-info">
					            <div class="panel-heading">
					                <span lang="en">Notice</span>
					            </div>
					            <div class="panel-wrapper collapse in" aria-expanded="true">
					                <div class="panel-body">
					                    <span lang="en">The value of #987654 is just a placeholder, you can change to any value you like.</span>
					                </div>
					            </div>
					        </div>
					    </div>
					</div>
					',
				),
				array(
					'type' => 'blank',
					'label' => ''
				),
				array(
					'type' => 'input',
					'name' => 'headerColor',
					'label' => 'Nav Bar Color',
					'value' => $GLOBALS['headerColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $GLOBALS['headerColor'] . '"'
				),
				array(
					'type' => 'input',
					'name' => 'headerTextColor',
					'label' => 'Nav Bar Text Color',
					'value' => $GLOBALS['headerTextColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $GLOBALS['headerTextColor'] . '"'
				),
				array(
					'type' => 'input',
					'name' => 'sidebarColor',
					'label' => 'Side Bar Color',
					'value' => $GLOBALS['sidebarColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $GLOBALS['sidebarColor'] . '"'
				),
				array(
					'type' => 'input',
					'name' => 'sidebarTextColor',
					'label' => 'Side Bar Text Color',
					'value' => $GLOBALS['sidebarTextColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $GLOBALS['sidebarTextColor'] . '"'
				),
				array(
					'type' => 'input',
					'name' => 'accentColor',
					'label' => 'Accent Color',
					'value' => $GLOBALS['accentColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $GLOBALS['accentColor'] . '"'
				),
				array(
					'type' => 'input',
					'name' => 'accentTextColor',
					'label' => 'Accent Text Color',
					'value' => $GLOBALS['accentTextColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $GLOBALS['accentTextColor'] . '"'
				),
				array(
					'type' => 'input',
					'name' => 'buttonColor',
					'label' => 'Button Color',
					'value' => $GLOBALS['buttonColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $GLOBALS['buttonColor'] . '"'
				),
				array(
					'type' => 'input',
					'name' => 'buttonTextColor',
					'label' => 'Button Text Color',
					'value' => $GLOBALS['buttonTextColor'],
					'class' => 'pick-a-color',
					'attr' => 'data-original="' . $GLOBALS['buttonTextColor'] . '"'
				),/*
                array(
                    'type' => 'input',
                    'name' => 'buttonHoverColor',
                    'label' => 'Button Hover Color',
                    'value' => $GLOBALS['buttonHoverColor'],
                    'class' => 'pick-a-color',
                    'disabled' => true
                ),
                array(
                    'type' => 'input',
                    'name' => 'buttonTextHoverColor',
                    'label' => 'Button Hover Text Color',
                    'value' => $GLOBALS['buttonTextHoverColor'],
                    'class' => 'pick-a-color',
                    'disabled' => true
                ),*/
				array(
					'type' => 'select',
					'name' => 'theme',
					'label' => 'Theme',
					'class' => 'themeChanger',
					'value' => $GLOBALS['theme'],
					'options' => getThemes()
				),
				array(
					'type' => 'select',
					'name' => 'style',
					'label' => 'Style',
					'class' => 'styleChanger',
					'value' => $GLOBALS['style'],
					'options' => array(
						array(
							'name' => 'Light',
							'value' => 'light'
						),
						array(
							'name' => 'Dark',
							'value' => 'dark'
						),
						array(
							'name' => 'Horizontal',
							'value' => 'horizontal'
						)
					)
				)
			),
			'Notifications' => array(
				array(
					'type' => 'select',
					'name' => 'notificationBackbone',
					'class' => 'notifyChanger',
					'label' => 'Type',
					'value' => $GLOBALS['notificationBackbone'],
					'options' => optionNotificationTypes()
				),
				array(
					'type' => 'select',
					'name' => 'notificationPosition',
					'class' => 'notifyPositionChanger',
					'label' => 'Position',
					'value' => $GLOBALS['notificationPosition'],
					'options' => optionNotificationPositions()
				),
				array(
					'type' => 'html',
					'label' => 'Test Message',
					'html' => '
					<div class="btn-group m-r-10 dropup">
						<button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-outline dropdown-toggle waves-effect waves-light" type="button">
							<i class="fa fa-comment m-r-5"></i>
							<span>Test </span>
						</button>
						<ul role="menu" class="dropdown-menu">
							<li><a onclick="message(\'Test Message\',\'This is a success Message\',activeInfo.settings.notifications.position,\'#FFF\',\'success\',\'5000\');">Success</a></li>
							<li><a onclick="message(\'Test Message\',\'This is a info Message\',activeInfo.settings.notifications.position,\'#FFF\',\'info\',\'5000\');">Info</a></li>
							<li><a onclick="message(\'Test Message\',\'This is a warning Message\',activeInfo.settings.notifications.position,\'#FFF\',\'warning\',\'5000\');">Warning</a></li>
							<li><a onclick="message(\'Test Message\',\'This is a error Message\',activeInfo.settings.notifications.position,\'#FFF\',\'error\',\'5000\');">Error</a></li>
						</ul>
					</div>
					'
				)
			),
			'FavIcon' => array(
				array(
					'type' => 'textbox',
					'name' => 'favIcon',
					'class' => '',
					'label' => 'Fav Icon Code',
					'value' => $GLOBALS['favIcon'],
					'placeholder' => 'Paste Contents from https://realfavicongenerator.net/',
					'attr' => 'rows="10"',
				),
				array(
					'type' => 'html',
					'label' => 'Instructions',
					'html' => '
					<div class="panel panel-default">
						<div class="panel-heading">
							<a href="https://realfavicongenerator.net/" target="_blank"><span class="label label-info m-l-5">Visit FavIcon Site</span></a>
						</div>
						<div class="panel-wrapper collapse in">
							<div class="panel-body">
								<ul class="list-icons">
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Click [Select your Favicon picture]</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Choose your image to use</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Edit settings to your liking</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> At bottom of page on [Favicon Generator Options] under [Path] choose [I cannot or I do not want to place favicon files at the root of my web site.]</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Enter this path <code>plugins/images/faviconCustom</code></li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Click [Generate your Favicons and HTML code]</li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Download and unzip file and place in <code>plugins/images/faviconCustom</code></li>
									<li lang="en"><i class="fa fa-caret-right text-info"></i> Copy code and paste inside left box</li>
								</ul>
							</div>
						</div>
					</div>
					'
				),
			),
			'Custom CSS' => array(
				array(
					'type' => 'html',
					'override' => 12,
					'label' => 'Custom CSS [Can replace colors from above]',
					'html' => '<button type="button" class="hidden saveCss btn btn-info btn-circle pull-right m-r-5 m-l-10"><i class="fa fa-save"></i> </button><div id="customCSSEditor" style="height:300px">' . htmlentities($GLOBALS['customCss']) . '</div>'
				),
				array(
					'type' => 'textbox',
					'name' => 'customCss',
					'class' => 'hidden cssTextarea',
					'label' => '',
					'value' => $GLOBALS['customCss'],
					'placeholder' => 'No <style> tags needed',
					'attr' => 'rows="10"',
				),
			),
			'Theme CSS' => array(
				array(
					'type' => 'html',
					'override' => 12,
					'label' => 'Theme CSS [Can replace colors from above]',
					'html' => '<button type="button" class="hidden saveCssTheme btn btn-info btn-circle pull-right m-r-5 m-l-10"><i class="fa fa-save"></i> </button><div id="customThemeCSSEditor" style="height:300px">' . htmlentities($GLOBALS['customThemeCss']) . '</div>'
				),
				array(
					'type' => 'textbox',
					'name' => 'customThemeCss',
					'class' => 'hidden cssThemeTextarea',
					'label' => '',
					'value' => $GLOBALS['customThemeCss'],
					'placeholder' => 'No <style> tags needed',
					'attr' => 'rows="10"',
				),
			),
			'Custom Javascript' => array(
				array(
					'type' => 'html',
					'override' => 12,
					'label' => 'Custom Javascript',
					'html' => '<button type="button" class="hidden saveJava btn btn-info btn-circle pull-right m-r-5 m-l-10"><i class="fa fa-save"></i> </button><div id="customJavaEditor" style="height:300px">' . htmlentities($GLOBALS['customJava']) . '</div>'
				),
				array(
					'type' => 'textbox',
					'name' => 'customJava',
					'class' => 'hidden javaTextarea',
					'label' => '',
					'value' => $GLOBALS['customJava'],
					'placeholder' => 'No <script> tags needed',
					'attr' => 'rows="10"',
				),
			),
			'Theme Javascript' => array(
				array(
					'type' => 'html',
					'override' => 12,
					'label' => 'Theme Javascript',
					'html' => '<button type="button" class="hidden saveJavaTheme btn btn-info btn-circle pull-right m-r-5 m-l-10"><i class="fa fa-save"></i> </button><div id="customThemeJavaEditor" style="height:300px">' . htmlentities($GLOBALS['customThemeJava']) . '</div>'
				),
				array(
					'type' => 'textbox',
					'name' => 'customThemeJava',
					'class' => 'hidden javaThemeTextarea',
					'label' => '',
					'value' => $GLOBALS['customThemeJava'],
					'placeholder' => 'No <script> tags needed',
					'attr' => 'rows="10"',
				),
			),
		);
	}
}

function editAppearance($array)
{
	switch ($array['data']['value']) {
		case 'true':
			$array['data']['value'] = (bool)true;
			break;
		case 'false':
			$array['data']['value'] = (bool)false;
			break;
		default:
			$array['data']['value'] = $array['data']['value'];
	}
	//return gettype($array['data']['value']).' - '.$array['data']['value'];
	switch ($array['data']['action']) {
		case 'editCustomizeAppearance':
			$newItem = array(
				$array['data']['name'] => $array['data']['value']
			);
			return (updateConfig($newItem)) ? true : false;
			break;
		default:
			# code...
			break;
	}
}

function updateConfigMultiple($array)
{
	return (updateConfig($array['data']['payload'])) ? true : false;
}

function updateConfigMultipleForm($array)
{
	$newItem = array();
	foreach ($array['data']['payload'] as $k => $v) {
		switch ($v['value']) {
			case 'true':
				$v['value'] = (bool)true;
				break;
			case 'false':
				$v['value'] = (bool)false;
				break;
			default:
				$v['value'] = $v['value'];
		}
		// Hash
		if ($v['type'] == 'password') {
			if (isEncrypted($v['value']) || $v['value'] == '') {
				$v['value'] = $v['value'];
			} else {
				$v['value'] = encrypt($v['value']);
			}
		}
		$newItem[$v['name']] = $v['value'];
	}
	//return $newItem;
	return (updateConfig($newItem)) ? true : false;
}

function updateConfigItem($array)
{
	switch ($array['data']['value']) {
		case 'true':
			$array['data']['value'] = (bool)true;
			break;
		case 'false':
			$array['data']['value'] = (bool)false;
			break;
		default:
			$array['data']['value'] = $array['data']['value'];
	}
	// Hash
	if ($array['data']['type'] == 'password') {
		$array['data']['value'] = encrypt($array['data']['value']);
	}
	$newItem = array(
		$array['data']['name'] => $array['data']['value']
	);
	return (updateConfig($newItem)) ? true : false;
}

function getPlugins()
{
	if (file_exists(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php')) {
		$pluginList = [];
		foreach ($GLOBALS['plugins'] as $plugin) {
			foreach ($plugin as $key => $value) {
				if (strpos($value['license'], $GLOBALS['license']) !== false) {
					$plugin[$key]['enabled'] = $GLOBALS[$value['configPrefix'] . '-enabled'];
					$pluginList[$key] = $plugin[$key];
				}
			}
		}
		return $pluginList;
	}
	return false;
}

function editPlugins($array)
{
	switch ($array['data']['action']) {
		case 'enable':
			$newItem = array(
				$array['data']['configName'] => true
			);
			writeLog('success', 'Plugin Function -  Enabled Plugin [' . $_POST['data']['name'] . ']', $GLOBALS['organizrUser']['username']);
			return (updateConfig($newItem)) ? true : false;
			break;
		case 'disable':
			$newItem = array(
				$array['data']['configName'] => false
			);
			writeLog('success', 'Plugin Function -  Disabled Plugin [' . $_POST['data']['name'] . ']', $GLOBALS['organizrUser']['username']);
			return (updateConfig($newItem)) ? true : false;
			break;
		default:
			# code...
			break;
	}
}

function auth()
{
	$debug = $GLOBALS['authDebug']; // CAREFUL WHEN SETTING TO TRUE AS THIS OPENS AUTH UP
	$ban = isset($_GET['ban']) ? strtoupper($_GET['ban']) : "";
	$whitelist = isset($_GET['whitelist']) ? $_GET['whitelist'] : false;
	$blacklist = isset($_GET['blacklist']) ? $_GET['blacklist'] : false;
	$group = isset($_GET['group']) ? (int)$_GET['group'] : (int)0;
	$currentIP = userIP();
	$unlocked = ($GLOBALS['organizrUser']['locked'] == '1') ? false : true;
	if (isset($GLOBALS['organizrUser'])) {
		$currentUser = $GLOBALS['organizrUser']['username'];
		$currentGroup = $GLOBALS['organizrUser']['groupID'];
	} else {
		$currentUser = 'Guest';
		$currentGroup = getUserLevel();
	}
	$userInfo = "User: $currentUser | Group: $currentGroup | IP: $currentIP | Requesting Access to Group $group | Result: ";
	if ($whitelist) {
		if (in_array($currentIP, arrayIP($whitelist))) {
			!$debug ? exit(http_response_code(200)) : die("$userInfo Whitelist Authorized");
		}
	}
	if ($blacklist) {
		if (in_array($currentIP, arrayIP($blacklist))) {
			!$debug ? exit(http_response_code(401)) : die("$userInfo Blacklisted");
		}
	}
	if ($group !== null) {
		if (qualifyRequest($group) && $unlocked) {
			header("X-Organizr-User: $currentUser");
			!$debug ? exit(http_response_code(200)) : die("$userInfo Authorized");
		} else {
			!$debug ? exit(http_response_code(401)) : die("$userInfo Not Authorized");
		}
	} else {
		!$debug ? exit(http_response_code(401)) : die("Not Authorized Due To No Parameters Set");
	}
}

function logoOrText()
{
	if ($GLOBALS['useLogo'] == false) {
		return '<h1>' . $GLOBALS['title'] . '</h1>';
	} else {
		return '<img class="loginLogo" src="' . $GLOBALS['logo'] . '" alt="Home" />';
	}
}

function showLogin()
{
	if ($GLOBALS['hideRegistration'] == false) {
		return '<p><span lang="en">Don\'t have an account?</span><a href="#" class="text-primary m-l-5 to-register"><b lang="en">Sign Up</b></a></p>';
	}
}

function checkoAuth()
{
	return ($GLOBALS['plexoAuth'] && $GLOBALS['authType'] !== 'internal') ? true : false;
}

function checkoAuthOnly()
{
	return ($GLOBALS['plexoAuth'] && $GLOBALS['authType'] == 'external') ? true : false;
}

function showoAuth()
{
	$buttons = '';
	if ($GLOBALS['plexoAuth'] && $GLOBALS['authType'] !== 'internal') {
		$buttons .= '<a href="javascript:void(0)" onclick="oAuthStart(\'plex\')" class="btn btn-lg btn-block text-uppercase waves-effect waves-light bg-plex text-muted" data-toggle="tooltip" title="" data-original-title="Login with Plex"> <span>Login</span><i aria-hidden="true" class="mdi mdi-plex m-l-5"></i> </a>';
	}
	return ($buttons) ? '
		<div class="panel">
            <div class="panel-heading bg-org" id="plex-login-heading" role="tab">
            	<a class="panel-title" data-toggle="collapse" href="#plex-login-collapse" data-parent="#login-panels" aria-expanded="false" aria-controls="organizr-login-collapse">
	                <img class="lazyload loginTitle" data-src="plugins/images/tabs/plex.png"> &nbsp;
                    <span class="text-uppercase fw300" lang="en">Login with Plex</span>
            	</a>
            </div>
            <div class="panel-collapse collapse in" id="plex-login-collapse" aria-labelledby="plex-login-heading" role="tabpanel">
                <div class="panel-body">
               		<div class="row">
			            <div class="col-xs-12 col-sm-12 col-md-12 text-center">
			                <div class="social m-b-0">' . $buttons . '</div>
			            </div>
			        </div>
               </div>
            </div>
        </div>
	' : '';
}

function getImages()
{
	$dirname = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'tabs' . DIRECTORY_SEPARATOR;
	$path = 'plugins/images/tabs/';
	$images = scandir($dirname);
	$ignore = array(".", "..", "._.DS_Store", ".DS_Store", ".pydio_id");
	$allIcons = array();
	foreach ($images as $image) {
		if (!in_array($image, $ignore)) {
			$allIcons[] = $path . $image;
		}
	}
	return $allIcons;
}

function imageSelect($form)
{
	$i = 1;
	$images = getImages();
	$return = '<select class="form-control tabIconImageList" id="' . $form . '-chooseImage" name="chooseImage"><option lang="en">Select or type Icon</option>';
	foreach ($images as $image) {
		$i++;
		$return .= '<option value="' . $image . '">' . basename($image) . '</option>';
	}
	return $return . '</select>';
}

function editImages()
{
	$array = array();
	$postCheck = array_filter($_POST);
	$filesCheck = array_filter($_FILES);
	$approvedPath = 'plugins/images/tabs/';
	if (!empty($postCheck)) {
		$removeImage = $approvedPath . pathinfo($_POST['data']['imagePath'], PATHINFO_BASENAME);
		if ($_POST['data']['action'] == 'deleteImage' && approvedFileExtension($removeImage)) {
			if (file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $removeImage)) {
				writeLog('success', 'Image Manager Function -  Deleted Image [' . $_POST['data']['imageName'] . ']', $GLOBALS['organizrUser']['username']);
				return (unlink(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $removeImage)) ? true : false;
			}
		}
	}
	if (!empty($filesCheck) && approvedFileExtension($_FILES['file']['name']) && strpos($_FILES['file']['type'], 'image/') !== false) {
		ini_set('upload_max_filesize', '10M');
		ini_set('post_max_size', '10M');
		$tempFile = $_FILES['file']['tmp_name'];
		$targetPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'tabs' . DIRECTORY_SEPARATOR;
		$targetFile = $targetPath . $_FILES['file']['name'];
		return (move_uploaded_file($tempFile, $targetFile)) ? true : false;
	}
	return false;
}

function approvedFileExtension($filename)
{
	$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
	switch ($ext) {
		case 'gif':
		case 'png':
		case 'jpeg':
		case 'jpg':
		case 'svg':
			return true;
			break;
		default:
			return false;
	}
}

function getThemes()
{
	$themes = array();
	foreach (glob(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . "*.css") as $filename) {
		$themes[] = array(
			'name' => preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($filename)),
			'value' => preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($filename))
		);
	}
	return $themes;
}

function getSounds()
{
	$sounds = array();
	foreach (glob(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'sounds' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . "*.mp3") as $filename) {
		$sounds[] = array(
			'name' => preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($filename)),
			'value' => preg_replace('/\\.[^.\\s]{3,4}$/', '', 'plugins/sounds/default/' . basename($filename) . '.mp3')
		);
	}
	foreach (glob(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'sounds' . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . "*.mp3") as $filename) {
		$sounds[] = array(
			'name' => preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($filename)),
			'value' => preg_replace('/\\.[^.\\s]{3,4}$/', '', 'plugins/sounds/custom/' . basename($filename) . '.mp3')
		);
	}
	return $sounds;
}

function getBranches()
{
	return array(
		array(
			'name' => 'Develop',
			'value' => 'v2-develop'
		),
		array(
			'name' => 'Master',
			'value' => 'v2-master'
		)
	);
}

function getAuthTypes()
{
	return array(
		array(
			'name' => 'Organizr DB',
			'value' => 'internal'
		),
		array(
			'name' => 'Organizr DB + Backend',
			'value' => 'both'
		),
		array(
			'name' => 'Backend Only',
			'value' => 'external'
		)
	);
}

function getLDAPOptions()
{
	return array(
		array(
			'name' => 'Active Directory',
			'value' => '1'
		),
		array(
			'name' => 'OpenLDAP',
			'value' => '2'
		),
		array(
			'name' => 'First IPA',
			'value' => '3'
		),
	);
}

function getAuthBackends()
{
	$backendOptions = array();
	$backendOptions[] = array(
		'name' => 'Choose Backend',
		'value' => false,
		'disabled' => true
	);
	foreach (array_filter(get_defined_functions()['user'], function ($v) {
		return strpos($v, 'plugin_auth_') === 0;
	}) as $value) {
		$name = str_replace('plugin_auth_', '', $value);
		if (strpos($name, 'disabled') === false) {
			$backendOptions[] = array(
				'name' => ucwords(str_replace('_', ' ', $name)),
				'value' => $name
			);
		} else {
			$backendOptions[] = array(
				'name' => $value(),
				'value' => 'none',
				'disabled' => true,
			);
		}
	}
	ksort($backendOptions);
	return $backendOptions;
}

function wizardPath($array)
{
	$path = $array['data']['path'];
	if (file_exists($path)) {
		if (is_writable($path)) {
			return true;
		}
	} else {
		if (is_writable(dirname($path, 1))) {
			if (mkdir($path, 0760, true)) {
				return true;
			}
		}
	}
	return 'permissions';
}

function groupSelect()
{
	$groups = allGroups();
	$select = array();
	foreach ($groups as $key => $value) {
		$select[] = array(
			'name' => $value['group'],
			'value' => $value['group_id']
		);
	}
	return $select;
}

function getImage()
{
	$refresh = false;
	$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
	if (!file_exists($cacheDirectory)) {
		mkdir($cacheDirectory, 0777, true);
	}
	@$image_url = $_GET['img'];
	@$key = $_GET['key'];
	@$image_height = $_GET['height'];
	@$image_width = $_GET['width'];
	@$source = $_GET['source'];
	@$itemType = $_GET['type'];
	if (strpos($key, '$') !== false) {
		$key = explode('$', $key)[0];
		$refresh = true;
	}
	switch ($source) {
		case 'plex':
			$plexAddress = qualifyURL($GLOBALS['plexURL']);
			$image_src = $plexAddress . '/photo/:/transcode?height=' . $image_height . '&width=' . $image_width . '&upscale=1&url=' . $image_url . '&X-Plex-Token=' . $GLOBALS['plexToken'];
			break;
		case 'emby':
			$embyAddress = qualifyURL($GLOBALS['embyURL']);
			$imgParams = array();
			if (isset($_GET['height'])) {
				$imgParams['height'] = 'maxHeight=' . $_GET['height'];
			}
			if (isset($_GET['width'])) {
				$imgParams['width'] = 'maxWidth=' . $_GET['width'];
			}
			$image_src = $embyAddress . '/Items/' . $image_url . '/Images/' . $itemType . '?' . implode('&', $imgParams);
			break;
		default:
			# code...
			break;
	}
	if (isset($image_url) && isset($image_height) && isset($image_width) && isset($image_src)) {
		$cachefile = $cacheDirectory . $key . '.jpg';
		$cachetime = 604800;
		// Serve from the cache if it is younger than $cachetime
		if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile) && $refresh == false) {
			header("Content-type: image/jpeg");
			//@readfile($cachefile);
			echo @curl('get', $cachefile)['content'];
			exit;
		}
		ob_start(); // Start the output buffer
		header('Content-type: image/jpeg');
		//@readfile($image_src);
		echo @curl('get', $image_src)['content'];
		// Cache the output to a file
		$fp = fopen($cachefile, 'wb');
		fwrite($fp, ob_get_contents());
		fclose($fp);
		ob_end_flush(); // Send the output to the browser
		die();
	} else {
		die("Invalid Request");
	}
}

function cacheImage($url, $name)
{
	$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
	if (!file_exists($cacheDirectory)) {
		mkdir($cacheDirectory, 0777, true);
	}
	$cachefile = $cacheDirectory . $name . '.jpg';
	@copy($url, $cachefile);
}

function downloader($array)
{
	switch ($array['data']['source']) {
		case 'sabnzbd':
			switch ($array['data']['action']) {
				case 'resume':
				case 'pause':
					sabnzbdAction($array['data']['action'], $array['data']['target']);
					break;
				default:
					# code...
					break;
			}
			break;
		case 'nzbget':
			break;
		default:
			# code...
			break;
	}
}

function sabnzbdAction($action = null, $target = null)
{
	if ($GLOBALS['homepageSabnzbdEnabled'] && !empty($GLOBALS['sabnzbdURL']) && !empty($GLOBALS['sabnzbdToken']) && qualifyRequest($GLOBALS['homepageSabnzbdAuth'])) {
		$url = qualifyURL($GLOBALS['sabnzbdURL']);
		switch ($action) {
			case 'pause':
				$id = ($target !== '' && $target !== 'main' && isset($target)) ? 'mode=queue&name=pause&value=' . $target . '&' : 'mode=pause';
				$url = $url . '/api?' . $id . '&output=json&apikey=' . $GLOBALS['sabnzbdToken'];
				break;
			case 'resume':
				$id = ($target !== '' && $target !== 'main' && isset($target)) ? 'mode=queue&name=resume&value=' . $target . '&' : 'mode=resume';
				$url = $url . '/api?' . $id . '&output=json&apikey=' . $GLOBALS['sabnzbdToken'];
				break;
			default:
				# code...
				break;
		}
		try {
			$options = (localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$api['content'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			writeLog('error', 'SabNZBd Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		return $api;
	}
}

// Deluge API isn't working ATM - will get with dev.
function delugeAction($action = null, $target = null)
{
	if ($GLOBALS['homepageDelugeEnabled'] && !empty($GLOBALS['delugeURL']) && !empty($GLOBALS['delugePassword']) && qualifyRequest($GLOBALS['homepageDelugeAuth'])) {
		$url = qualifyURL($GLOBALS['delugeURL']);
		try {
			$deluge = new deluge($GLOBALS['delugeURL'], decrypt($GLOBALS['delugePassword']));
			switch ($action) {
				case 'pause':
					$torrents = $deluge->pauseTorrent($target);
					break;
				case 'pauseAll':
					$torrents = $deluge->pauseAllTorrents();
					break;
				case 'resume':
					$torrents = $deluge->resumeTorrent($target);
					break;
				case 'resumeAll':
					$torrents = $deluge->resumeAllTorrents();
					break;
				default:
					# code...
					break;
			}
			$api['content'] = $torrents;
		} catch (Excecption $e) {
			writeLog('error', 'Deluge Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		}
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		return $api;
	}
	return false;
}

function getOrgUsers()
{
	$result = allUsers();
	if (is_array($result) || is_object($result)) {
		foreach ($result['users'] as $k => $v) {
			$return[$v['username']] = $v['email'];
		}
		return $return;
	}
}

function convertPlexName($user, $type)
{
	$array = userList('plex');
	switch ($type) {
		case "username":
		case "u":
			$plexUser = array_search($user, $array['users']);
			break;
		case "id":
			if (array_key_exists(strtolower($user), $array['users'])) {
				$plexUser = $array['users'][strtolower($user)];
			}
			break;
		default:
			$plexUser = false;
	}
	return (!empty($plexUser) ? $plexUser : null);
}

function userList($type = null)
{
	switch ($type) {
		case 'plex':
			if (!empty($GLOBALS['plexToken']) && !empty($GLOBALS['plexID'])) {
				$url = 'https://plex.tv/api/servers/' . $GLOBALS['plexID'] . '/shared_servers';
				try {
					$headers = array(
						"Accept" => "application/json",
						"X-Plex-Token" => $GLOBALS['plexToken']
					);
					$response = Requests::get($url, $headers, array());
					libxml_use_internal_errors(true);
					if ($response->success) {
						$libraryList = array();
						$plex = simplexml_load_string($response->body);
						foreach ($plex->SharedServer as $child) {
							if (!empty($child['username'])) {
								$username = (string)strtolower($child['username']);
								$email = (string)strtolower($child['email']);
								$libraryList['users'][$username] = (string)$child['id'];
								$libraryList['emails'][$email] = (string)$child['id'];
								$libraryList['both'][$username] = $email;
							}
						}
						$libraryList = array_change_key_case($libraryList, CASE_LOWER);
						return $libraryList;
					}
				} catch (Requests_Exception $e) {
					writeLog('error', 'Plex Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
				};
			}
			break;
		default:
			# code...
			break;
	}
	return false;
}

function libraryList($type = null)
{
	switch ($type) {
		case 'plex':
			if (!empty($GLOBALS['plexToken']) && !empty($GLOBALS['plexID'])) {
				$url = 'https://plex.tv/api/servers/' . $GLOBALS['plexID'];
				try {
					$headers = array(
						"Accept" => "application/json",
						"X-Plex-Token" => $GLOBALS['plexToken']
					);
					$response = Requests::get($url, $headers, array());
					libxml_use_internal_errors(true);
					if ($response->success) {
						$libraryList = array();
						$plex = simplexml_load_string($response->body);
						foreach ($plex->Server->Section as $child) {
							$libraryList['libraries'][(string)$child['title']] = (string)$child['id'];
						}
						$libraryList = array_change_key_case($libraryList, CASE_LOWER);
						return $libraryList;
					}
				} catch (Requests_Exception $e) {
					writeLog('error', 'Plex Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
				};
			}
			break;
		default:
			# code...
			break;
	}
	return false;
}

function plexJoinAPI($array)
{
	return plexJoin($array['data']['username'], $array['data']['email'], $array['data']['password']);
}

function embyJoinAPI($array)
{
	return embyJoin($array['data']['username'], $array['data']['email'], $array['data']['password']);
}

function plexJoin($username, $email, $password)
{
	try {
		$url = 'https://plex.tv/users.json';
		$headers = array(
			'Accept' => 'application/json',
			'Content-Type' => 'application/x-www-form-urlencoded',
			'X-Plex-Product' => 'Organizr',
			'X-Plex-Version' => '2.0',
			'X-Plex-Client-Identifier' => $GLOBALS['uuid'],
		);
		$data = array(
			'user[email]' => $email,
			'user[username]' => $username,
			'user[password]' => $password,
		);
		$response = Requests::post($url, $headers, $data, array());
		$json = json_decode($response->body, true);
		$errors = (!empty($json['errors']) ? true : false);
		$success = (!empty($json['user']) ? true : false);
		//Use This for later
		$usernameError = (!empty($json['errors']['username']) ? $json['errors']['username'][0] : false);
		$emailError = (!empty($json['errors']['email']) ? $json['errors']['email'][0] : false);
		$passwordError = (!empty($json['errors']['password']) ? $json['errors']['password'][0] : false);
		$errorMessage = "";
		if ($errors) {
			if ($usernameError) {
				$errorMessage .= "[Username Error: " . $usernameError . "]";
			}
			if ($emailError) {
				$errorMessage .= "[Email Error: " . $emailError . "]";
			}
			if ($passwordError) {
				$errorMessage .= "[Password Error: " . $passwordError . "]";
			}
		}
		return (!empty($success) && empty($errors) ? true : $errorMessage);
	} catch (Requests_Exception $e) {
		writeLog('error', 'Plex.TV Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
	};
	return false;
}

function embyJoin($username, $email, $password)
{
	try {
		#create user in emby.
		$headers = array(
			"Accept" => "application/json"
		);
		$data = array();
		$url = $GLOBALS['embyURL'] . '/emby/Users/New?name=' . $username . '&api_key=' . $GLOBALS['embyToken'];
		$response = Requests::Post($url, $headers, json_encode($data), array());
		$response = $response->body;
		//return($response);
		$response = json_decode($response, true);
		//return($response);
		$userID = $response["Id"];
		//return($userID);
		#authenticate as user to update password.
		//randomizer four digits of DeviceId
		// I dont think ther would be security problems with hardcoding deviceID but randomizing it would mitigate any issue.
		$deviceIdSeceret = rand(0, 9) . "" . rand(0, 9) . "" . rand(0, 9) . "" . rand(0, 9);
		//hardcoded device id with the first three digits random 0-9,0-9,0-9,0-9
		$embyAuthHeader = 'MediaBrowser Client="Emby Mobile", Device="Firefox", DeviceId="' . $deviceIdSeceret . 'aWxssS81LgAggFdpbmRvd3MgTlQgMTAuMDsgV2luNjxx7IHf2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzcyLjAuMzYyNi4xMTkgU2FmYXJpLzUzNy4zNnwxNTUxNTczMTAyNDI4", Version="4.0.2.0"';
		$headers = array(
			"Accept" => "application/json",
			"Content-Type" => "application/json",
			"X-Emby-Authorization" => $embyAuthHeader
		);
		$data = array(
			"Pw" => "",
			"Username" => $username
		);
		$url = $GLOBALS['embyURL'] . '/emby/Users/AuthenticateByName';
		$response = Requests::Post($url, $headers, json_encode($data), array());
		$response = $response->body;
		$response = json_decode($response, true);
		$userToken = $response["AccessToken"];
		#update password
		$embyAuthHeader = 'MediaBrowser Client="Emby Mobile", Device="Firefox", Token="' . $userToken . '", DeviceId="' . $deviceIdSeceret . 'aWxssS81LgAggFdpbmRvd3MgTlQgMTAuMDsgV2luNjxx7IHf2NCkgQXBwbGVXZWJLaXQvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lLzcyLjAuMzYyNi4xMTkgU2FmYXJpLzUzNy4zNnwxNTUxNTczMTAyNDI4", Version="4.0.2.0"';
		$headers = array(
			"Accept" => "application/json",
			"Content-Type" => "application/json",
			"X-Emby-Authorization" => $embyAuthHeader
		);
		$data = array(
			"CurrentPw" => "",
			"NewPw" => $password,
			"Id" => $userID
		);
		$url = $GLOBALS['embyURL'] . '/emby/Users/' . $userID . '/Password';
		Requests::Post($url, $headers, json_encode($data), array());
		#update config
		$headers = array(
			"Accept" => "application/json",
			"Content-Type" => "application/json"
		);
		$url = $GLOBALS['embyURL'] . '/emby/Users/' . $userID . '/Policy?api_key=' . $GLOBALS['embyToken'];
		$response = Requests::Post($url, $headers, getEmbyTemplateUserJson(), array());
		#add emby.media
		try {
			#seperate because this is not required
			$headers = array(
				"Accept" => "application/json",
				"X-Emby-Authorization" => $embyAuthHeader
			);
			$data = array(
				"ConnectUsername " => $email
			);
			$url = $GLOBALS['embyURL'] . '/emby/Users/' . $userID . '/Connect/Link';
			Requests::Post($url, $headers, json_encode($data), array());
		} catch (Requests_Exception $e) {
			writeLog('error', 'Emby Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		}
		return (true);
		//return( "USERID:".$userID);
	} catch (Requests_Exception $e) {
		writeLog('error', 'Emby create Function - Error: ' . $e->getMessage(), 'SYSTEM');
	};
	return false;
}

function checkFrame($array, $url)
{
	if (array_key_exists("x-frame-options", $array)) {
		if ($array['x-frame-options'] == "deny") {
			return false;
		} elseif ($array['x-frame-options'] == "sameorgin") {
			$digest = parse_url($url);
			$host = (isset($digest['host']) ? $digest['host'] : '');
			if (getServer() == $host) {
				return true;
			} else {
				return false;
			}
		}
	} else {
		if (!$array) {
			return false;
		}
		return true;
	}
}

/*loads users from emby and returns a correctly formated policy for a new user.
*/
function getEmbyTemplateUserJson()
{
	$headers = array(
		"Accept" => "application/json"
	);
	$data = array();
	$url = $GLOBALS['embyURL'] . '/emby/Users?api_key=' . $GLOBALS['embyToken'];
	$response = Requests::Get($url, $headers, array());
	$response = $response->body;
	$response = json_decode($response, true);
	//error_Log("response ".json_encode($response));
	writeLog('error', 'userList:' . json_encode($response), 'SYSTEM');
	//$correct stores the template users object
	$correct = null;
	foreach ($response as $element) {
		if ($element['Name'] == $GLOBALS['INVITES-EmbyTemplate']) {
			$correct = $element;
		}
	}
	writeLog('error', 'Correct user:' . json_encode($correct), 'SYSTEM');
	if ($correct == null) {
		//return empty JSON if user incorectly configured template
		return "{}";
	}
	//select policy section and remove possibly dangeours rows.
	$policy = $correct['Policy'];
	//writeLog('error', 'policy update'.$policy, 'SYSTEM');
	unset($policy['AuthenticationProviderId']);
	unset($policy['InvalidLoginAttemptCount']);
	unset($policy['DisablePremiumFeatures']);
	unset($policy['DisablePremiumFeatures']);
	return (json_encode($policy));
}

function frameTest($url)
{
	$array = array_change_key_case(get_headers(qualifyURL($url), 1));
	$url = qualifyURL($url);
	if (checkFrame($array, $url)) {
		return true;
	} else {
		return false;
	}
}

function ping($pings)
{
	if (qualifyRequest($GLOBALS['pingAuth'])) {
		$type = gettype($pings);
		$ping = new Ping("");
		$ping->setTtl(128);
		$ping->setTimeout(2);
		switch ($type) {
			case "array":
				$results = [];
				foreach ($pings as $k => $v) {
					if (strpos($v, ':') !== false) {
						$domain = explode(':', $v)[0];
						$port = explode(':', $v)[1];
						$ping->setHost($domain);
						$ping->setPort($port);
						$latency = $ping->ping('fsockopen');
					} else {
						$ping->setHost($v);
						$latency = $ping->ping();
					}
					if ($latency || $latency === 0) {
						$results[$v] = $latency;
					} else {
						$results[$v] = false;
					}
				}
				break;
			case "string":
				if (strpos($pings, ':') !== false) {
					$domain = explode(':', $pings)[0];
					$port = explode(':', $pings)[1];
					$ping->setHost($domain);
					$ping->setPort($port);
					$latency = $ping->ping('fsockopen');
				} else {
					$ping->setHost($pings);
					$latency = $ping->ping();
				}
				if ($latency || $latency === 0) {
					$results = $latency;
				} else {
					$results = false;
				}
				break;
		}
		return $results;
	}
	return false;
}

function guestHash($start, $end)
{
	$ip = $_SERVER['REMOTE_ADDR'];
	$ip = md5($ip);
	return substr($ip, $start, $end);
}

function importUserButtons()
{
	$emptyButtons = '
		<div class="col-md-12">
            <div class="white-box bg-org">
                <h3 class="box-title m-0" lang="en">Currently User import is available for Plex only.</h3> </div>
        </div>
	';
	$buttons = '';
	if (!empty($GLOBALS['plexToken'])) {
		$buttons .= '<button class="btn bg-plex text-muted waves-effect waves-light importUsersButton" onclick="importUsers(\'plex\')" type="button"><span class="btn-label"><i class="mdi mdi-plex"></i></span><span lang="en">Import Plex Users</span></button>';
	}
	return ($buttons !== '') ? $buttons : $emptyButtons;
}

function settingsDocker()
{
	$type = ($GLOBALS['docker']) ? 'Official Docker' : 'Native';
	return '<li><div class="bg-info"><i class="mdi mdi-flag mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">Install Type</span> ' . $type . '</li>';
}

function settingsPathChecks()
{
	$items = '';
	$type = (array_search(false, pathsWritable($GLOBALS['paths']))) ? 'Not Writable' : 'Writable';
	$result = '<li class="mouse" onclick="toggleWritableFolders();"><div class="bg-info"><i class="mdi mdi-folder mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">Organizr Paths</span> ' . $type . '</li>';
	foreach (pathsWritable($GLOBALS['paths']) as $k => $v) {
		$items .= '<li class="folders-writable hidden"><div class="bg-info"><i class="mdi mdi-folder mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">' . $k . '</span> ' . (($v) ? 'Writable' : 'Not Writable') . '</li>';
	}
	return $result . $items;
}

function dockerUpdate()
{
	chdir('/etc/cont-init.d/');
	$dockerUpdate = shell_exec('./30-install');
	return $dockerUpdate;
}

function windowsUpdate()
{
	$branch = ($GLOBALS['branch'] == 'v2-master') ? '-m' : '-d';
	ini_set('max_execution_time', 0);
	set_time_limit(0);
	$logFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'log.txt';
	$windowsScript = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'windows-update.bat ' . $branch . ' > ' . $logFile . ' 2>&1';
	$windowsUpdate = shell_exec($windowsScript);
	return ($windowsUpdate) ? $windowsUpdate : 'Update Complete - check log.txt for output';
}

function checkHostPrefix($s)
{
	if (empty($s)) {
		return $s;
	}
	return (substr($s, -1, 1) == '\\') ? $s : $s . '\\';
}
