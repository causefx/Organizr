<?php

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
    // Create Config
    if(createConfig($configArray)){
        // Call DB Create
        if(createDB($location,$dbName)){
            // Add in first user
            if(createFirstAdmin($location,$dbName,$username,$password,$email)){
                if(createToken($username,$email,gravatar($email),'Admin',0,$hashKey,1)){
                    return true;
                }else{
                    return 'token';
                }
            }else{
                return 'admin';
            }
        }else{
            return 'db';
        }
    }else{
        return 'config';
    }
    return false;
}
function register($array){
    // Grab username and password from login form
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
function editUser($array){
    return $array;
}
function logout(){
    coookie('delete','organizrToken');
    $GLOBALS['organizrUser'] = false;
    return true;
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
    // Check token or API key
    // If API key, return 0 for admin
    if(strlen($requesterToken) == 20 && $requesterToken == $GLOBALS['organizrAPI']){
        //DO API CHECK
        return 0;
    }elseif(isset($GLOBALS['organizrUser'])){
        return $GLOBALS['organizrUser']['groupID'];
    }
    // All else fails?  return guest id
    return 999;
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
function getSettingsMain(){
	return array(
        'Github' => array(
    		array(
    			'type' => 'select',
    			'name' => 'branch',
    			'label' => 'Branch',
    			'value' => $GLOBALS['branch'],
    			'options' => getBranches()
    		),
    		array(
    			'type' => 'button',
    			'label' => 'Force Install Branch',
    			'class' => 'updateNow',
    			'icon' => 'fa fa-paper-plane',
    			'text' => 'Retrieve'
    		)
        ),
        'API' => array(
            array(
    			'type' => 'input',
    			'name' => 'organizrAPI',
    			'label' => 'Organizr API',
    			'value' => $GLOBALS['organizrAPI']
    		),
    		array(
    			'type' => 'button',
    			'label' => 'Generate New API Key',
    			'class' => 'newAPIKey',
    			'icon' => 'fa fa-paper-plane',
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
    			'type' => 'input',
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
    			'icon' => 'fa fa-paper-plane',
    			'text' => 'Retrieve',
    			'href' => '#auth-plex-token-form',
    			'attr' => 'data-effect="mfp-3d-unfold"'
    		),
    		array(
    			'type' => 'input',
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
    			'icon' => 'fa fa-paper-plane',
    			'text' => 'Retrieve',
    			'href' => '#auth-plex-machine-form',
    			'attr' => 'data-effect="mfp-3d-unfold"'
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
    			'type' => 'input',
    			'name' => 'embyURL',
                'class' => 'embyAuth switchAuth',
    			'label' => 'Emby URL',
    			'value' => $GLOBALS['embyURL'],
    			'placeholder' => 'http(s)://hostname:port'
    		),
            array(
    			'type' => 'input',
    			'name' => 'embyToken',
                'class' => 'embyAuth switchAuth',
    			'label' => 'Emby Token',
    			'value' => $GLOBALS['embyToken'],
    			'placeholder' => ''
    		)
    		/*array(
    			'type' => 'button',
    			'label' => 'Send Test',
    			'class' => 'phpmSendTestEmail',
    			'icon' => 'fa fa-paper-plane',
    			'text' => 'Send'
    		)*/
        )
	);
}
function getSSO(){
	return array(
        'Plex' => array(
    		array(
    			'type' => 'input',
    			'name' => 'plexToken',
    			'label' => 'Plex Token',
    			'value' => $GLOBALS['plexToken'],
    			'placeholder' => 'Use Get Token Button'
    		),
    		array(
    			'type' => 'button',
    			'label' => 'Get Plex Token',
    			'class' => 'popup-with-form getPlexTokenSSO',
    			'icon' => 'fa fa-paper-plane',
    			'text' => 'Retrieve',
    			'href' => '#sso-plex-token-form',
    			'attr' => 'data-effect="mfp-3d-unfold"'
    		),
    		array(
    			'type' => 'input',
    			'name' => 'plexID',
    			'label' => 'Plex Machine',
    			'value' => $GLOBALS['plexID'],
    			'placeholder' => 'Use Get Plex Machine Button'
    		),
    		array(
    			'type' => 'button',
    			'label' => 'Get Plex Machine',
    			'class' => 'popup-with-form getPlexMachineSSO',
    			'icon' => 'fa fa-paper-plane',
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
        'Ombi' => array(
    		array(
    			'type' => 'input',
    			'name' => 'ombiURL',
    			'label' => 'Ombi URL',
    			'value' => $GLOBALS['ombiURL'],
    			'placeholder' => 'http(s)://hostname:port'
    		),
    		array(
    			'type' => 'switch',
    			'name' => 'ssoOmbi',
    			'label' => 'Enable',
    			'value' => $GLOBALS['ssoOmbi']
    		)
        ),
        'Tautulli' => array(
    		array(
    			'type' => 'input',
    			'name' => 'tautulliURL',
    			'label' => 'Tautulli URL',
    			'value' => $GLOBALS['tautulliURL'],
    			'placeholder' => 'http(s)://hostname:port'
    		),
    		array(
    			'type' => 'switch',
    			'name' => 'ssoTautulli',
    			'label' => 'Enable',

            	'value' => $GLOBALS['ssoTautulli']
    		)
        )
	);
}
function loadAppearance(){
    $appearance = array();
    $appearance['logo'] = $GLOBALS['logo'];
    $appearance['title'] = $GLOBALS['title'];
	$appearance['useLogo'] = $GLOBALS['useLogo'];
    $appearance['headerColor'] = $GLOBALS['headerColor'];
    $appearance['headerTextColor'] = $GLOBALS['headerTextColor'];
    $appearance['sidebarColor'] = $GLOBALS['sidebarColor'];
    $appearance['headerTextColor'] = $GLOBALS['headerTextColor'];
    $appearance['sidebarTextColor'] = $GLOBALS['sidebarTextColor'];
    $appearance['customCss'] = $GLOBALS['customCss'];
    return $appearance;
}
function getCustomizeAppearance(){
    if(file_exists(dirname(__DIR__,1).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php')){
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
                    'value' => $GLOBALS['useLogo']
                )
            ),
            'Login Page' => array(
                array(
                    'type' => 'input',
                    'name' => 'loginWallpaper',
                    'label' => 'Login Wallpaper',
                    'value' => $GLOBALS['loginWallpaper']
                )
            ),
            'Colors & Themes' => array(
                array(
                    'type' => 'input',
                    'name' => 'headerColor',
                    'label' => 'Nav Bar Color',
                    'value' => $GLOBALS['headerColor'],
                    'class' => 'colorpicker',
                    'disabled' => true
                ),
                array(
                    'type' => 'input',
                    'name' => 'headerTextColor',
                    'label' => 'Nav Bar Text Color',
                    'value' => $GLOBALS['headerTextColor'],
                    'class' => 'colorpicker',
                    'disabled' => true
                ),
                array(
                    'type' => 'input',
                    'name' => 'sidebarColor',
                    'label' => 'Side Bar Color',
                    'value' => $GLOBALS['sidebarColor'],
                    'class' => 'colorpicker',
                    'disabled' => true
                ),
                array(
                    'type' => 'input',
                    'name' => 'sidebarTextColor',
                    'label' => 'Side Bar Text Color',
                    'value' => $GLOBALS['sidebarTextColor'],
                    'class' => 'colorpicker',
                    'disabled' => true
                ),
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
                array(
        			'type' => 'html',
                    'override' => 12,
        			'label' => 'Custom CSS [Can replace colors from above]',
        			'html' => '<button type="button" class="hidden saveCss btn btn-info btn-circle pull-right m-r-5 m-l-10"><i class="fa fa-save"></i> </button><div id="customCSSEditor" style="height:300px">'.$GLOBALS['customCss'].'</div>'
        		),
            )
        );
    }
}
function editAppearance($array){
    switch ($array['data']['value']) {
        case 'true':
            $array['data']['value'] = (bool) true;
            break;
        case 'false':
            $array['data']['value'] = (bool) false;
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
function updateConfigItem($array){
    switch ($array['data']['value']) {
        case 'true':
            $array['data']['value'] = (bool) true;
            break;
        case 'false':
            $array['data']['value'] = (bool) false;
            break;
        default:
            $array['data']['value'] = $array['data']['value'];
    }
	// Hash
	if($array['data']['type'] == 'password'){
		$array['data']['value'] = encrypt($array['data']['value']);
	}
    //return gettype($array['data']['value']).' - '.$array['data']['value'];
    $newItem = array(
        $array['data']['name'] => $array['data']['value']
    );
    return (updateConfig($newItem)) ? true : false;
}
function getPlugins(){
    if(file_exists(dirname(__DIR__,1).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php')){
		$pluginList = array();
        foreach($GLOBALS['plugins'] as $plugin){
			foreach ($plugin as $key => $value) {
				$plugin[$key]['enabled'] = $GLOBALS[$value['configPrefix'].'-enabled'];
			}
			$pluginList = array_merge($pluginList, $plugin);
		}
		return $pluginList;
    }
	return false;
}
function editPlugins($array){
	switch ($array['data']['action']) {
        case 'enable':
            $newItem = array(
                $array['data']['configName'] => true
            );
			writeLog('success', 'Plugin Function -  Enabled Plugin ['.$_POST['data']['name'].']', $GLOBALS['organizrUser']['username']);
            return (updateConfig($newItem)) ? true : false;
            break;
		case 'disable':
			$newItem = array(
				$array['data']['configName'] => false
			);
			writeLog('success', 'Plugin Function -  Disabled Plugin ['.$_POST['data']['name'].']', $GLOBALS['organizrUser']['username']);
			return (updateConfig($newItem)) ? true : false;
			break;
        default:
            # code...
            break;
    }
}
function auth(){
    $debug = false; // CAREFUL WHEN SETTING TO TRUE AS THIS OPENS AUTH UP
    $ban = isset($_GET['ban']) ? strtoupper($_GET['ban']) : "";
    $whitelist = isset($_GET['whitelist']) ? $_GET['whitelist'] : false;
    $blacklist = isset($_GET['blacklist']) ? $_GET['blacklist'] : false;
    $group = isset($_GET['group']) ? (int)$_GET['group'] : (int)0;
    $currentIP = userIP();
	if(isset($GLOBALS['organizrUser'])){
		$currentUser = $GLOBALS['organizrUser']['username'];
        $currentGroup = $GLOBALS['organizrUser']['groupID'];
    }else{
		$currentUser = 'Guest';
		$currentGroup = getUserLevel();
	}
	$userInfo = "User: $currentUser | Group: $currentGroup | IP: $currentIP | Requesting Access to Group $group | Result: ";
    if ($whitelist) {
        if(in_array($currentIP, arrayIP($whitelist))) {
           !$debug ? exit(http_response_code(200)) : die("$userInfo Whitelist Authorized");
    	}
    }
    if ($blacklist) {
        if(in_array($currentIP, arrayIP($blacklist))) {
           !$debug ? exit(http_response_code(401)) : die("$userInfo Blacklisted");
    	}
    }
    if($group !== null){
        if(qualifyRequest($group)){
            !$debug ? exit(http_response_code(200)) : die("$userInfo Authorized");
        }else{
            !$debug ? exit(http_response_code(401)) : die("$userInfo Not Authorized");
        }
    }else{
        !$debug ? exit(http_response_code(401)) : die("Not Authorized Due To No Parameters Set");
    }
}
function logoOrText(){
    if($GLOBALS['useLogo'] == false){
        return '<h1>'.$GLOBALS['title'].'</h1>';
    }else{
        return '<img style="max-width: 350px;" src="'.$GLOBALS['logo'].'" alt="Home" />';
    }
}
function getImages(){
    $dirname = dirname(__DIR__,2).DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'tabs'.DIRECTORY_SEPARATOR;
    $path = 'plugins/images/tabs/';
    $images = scandir($dirname);
    $ignore = Array(".", "..", "._.DS_Store", ".DS_Store", ".pydio_id");
    $allIcons = array();
    foreach($images as $image){
        if(!in_array($image, $ignore)) {
            $allIcons[] = $path.$image;
        }
    }
    return $allIcons;
}
function editImages(){
    $array = array();
    $postCheck = array_filter($_POST);
    $filesCheck = array_filter($_FILES);
    if(!empty($postCheck)){
        if($_POST['data']['action'] == 'deleteImage'){
            if(file_exists(dirname(__DIR__,2).DIRECTORY_SEPARATOR.$_POST['data']['imagePath'])){
                writeLog('success', 'Image Manager Function -  Deleted Image ['.$_POST['data']['imageName'].']', $GLOBALS['organizrUser']['username']);
                return (unlink(dirname(__DIR__,2).DIRECTORY_SEPARATOR.$_POST['data']['imagePath'])) ? true : false;
            }
        }
    }
    if(!empty($filesCheck)){
        ini_set('upload_max_filesize', '10M');
        ini_set('post_max_size', '10M');
        $tempFile = $_FILES['file']['tmp_name'];
        $targetPath = dirname(__DIR__,2).DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'tabs'.DIRECTORY_SEPARATOR;
        $targetFile =  $targetPath. $_FILES['file']['name'];
        return (move_uploaded_file($tempFile,$targetFile)) ? true : false;
    }
    return false;
}
function getThemes(){
	$themes = array();
	foreach (glob(dirname(__DIR__,2).DIRECTORY_SEPARATOR.'css' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . "*.css") as $filename){

		$themes[] = array(
			'name' => preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($filename)),
			'value' => preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($filename))
		);
	}
	return $themes;
}
function getBranches(){
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
function getAuthTypes(){
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
function getAuthBackends(){
    $backendOptions = array();
    $backendOptions[] = array(
        'name' => 'Choose Backend',
        'value' => false,
        'disabled' => true
    );
    foreach (array_filter(get_defined_functions()['user'],function($v) { return strpos($v, 'plugin_auth_') === 0; }) as $value) {
    	$name = str_replace('plugin_auth_','',$value);
    	if (strpos($name, 'disabled') === false) {
    		$backendOptions[] = array(
    			'name' => ucwords(str_replace('_',' ',$name)),
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
function wizardPath($array){
    $path = $array['data']['path'];
    if(file_exists($path)){
        if(is_writable($path)){
            return true;
        }
    }else{
        if(is_writable(dirname($path, 1))){
            if(mkdir($path, 0760, true)) {
                return true;
            }
        }
    }
    return 'permissions';
}
function groupSelect(){
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
function getImage() {
	$refresh = false;
    $cacheDirectory = dirname(__DIR__,2).DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
    if (!file_exists($cacheDirectory)) {
        mkdir($cacheDirectory, 0777, true);
    }
	@$image_url = $_GET['img'];
	@$key = $_GET['key'];
    @$image_height = $_GET['height'];
    @$image_width = $_GET['width'];
	@$source = $_GET['source'];
    @$itemType = $_GET['type'];
	if(strpos($key, '$') !== false){
		$key = explode('$', $key)[0];
		$refresh = true;
	}
	switch ($source) {
        case 'plex':
            $plexAddress = qualifyURL($GLOBALS['plexURL']);
            $image_src = $plexAddress . '/photo/:/transcode?height='.$image_height.'&width='.$image_width.'&upscale=1&url=' . $image_url . '&X-Plex-Token=' . $GLOBALS['plexToken'];
            break;
        case 'emby':
            $embyAddress = qualifyURL($GLOBALS['embyURL']);
        	$imgParams = array();
        	if (isset($_GET['height'])) { $imgParams['height'] = 'maxHeight='.$_GET['height']; }
        	if (isset($_GET['width'])) { $imgParams['width'] = 'maxWidth='.$_GET['width']; }
            $image_src = $embyAddress . '/Items/'.$image_url.'/Images/'.$itemType.'?'.implode('&', $imgParams);
            break;
        default:
            # code...
            break;
    }

	if(isset($image_url) && isset($image_height) && isset($image_width) && isset($image_src)) {

        $cachefile = $cacheDirectory.$key.'.jpg';
        $cachetime = 604800;
        // Serve from the cache if it is younger than $cachetime
        if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile) && $refresh == false) {
            header("Content-type: image/jpeg");
            //@readfile($cachefile);
            echo @curl('get',$cachefile)['content'];
            exit;
        }
		ob_start(); // Start the output buffer
        header('Content-type: image/jpeg');
		//@readfile($image_src);
		echo @curl('get',$image_src)['content'];
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
function cacheImage($url,$name){
	$cacheDirectory = dirname(__DIR__,2).DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
    if (!file_exists($cacheDirectory)) {
        mkdir($cacheDirectory, 0777, true);
    }
	$cachefile = $cacheDirectory.$name.'.jpg';
	copy($url, $cachefile);
}
function downloader($array){
	switch ($array['data']['source']) {
        case 'sabnzbd':
			switch ($array['data']['action']) {
                case 'resume':
                case 'pause':
                    sabnzbdAction($array['data']['action'],$array['data']['target']);
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
function sabnzbdAction($action=null, $target=null) {
    if($GLOBALS['homepageSabnzbdEnabled'] && !empty($GLOBALS['sabnzbdURL']) && !empty($GLOBALS['sabnzbdToken']) && qualifyRequest($GLOBALS['homepageSabnzbdAuth'])){
        $url = qualifyURL($GLOBALS['sabnzbdURL']);
        switch ($action) {
            case 'pause':
                $id = ($target !== '' && $target !== 'main' && isset($target)) ? 'mode=queue&name=pause&value='.$target.'&' : 'mode=pause';
                $url = $url.'/api?'.$id.'&output=json&apikey='.$GLOBALS['sabnzbdToken'];
                break;
            case 'resume':
                $id = ($target !== '' && $target !== 'main' && isset($target)) ? 'mode=queue&name=resume&value='.$target.'&' : 'mode=resume';
                $url = $url.'/api?'.$id.'&output=json&apikey='.$GLOBALS['sabnzbdToken'];
                break;
            default:
                # code...
                break;
        }

        try{
			$options = (localURL($url)) ? array('verify' => false ) : array();
			$response = Requests::get($url, array(), $options);
			if($response->success){
				$api['content'] = json_decode($response->body, true);
			}
		}catch( Requests_Exception $e ) {
			writeLog('error', 'SabNZBd Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
		};
        $api['content'] = isset($api['content']) ? $api['content'] : false;
        return $api;
    }
}

/*
function sendEmail($email = null, $username = "Organizr User", $subject, $body, $cc = null, $bcc = null){
	try {
		$mail = new PHPMailer(true);
		$mail->isSMTP();
		$mail->Host = $GLOBALS['smtpHost'];
		$mail->SMTPAuth = $GLOBALS['smtpHostAuth'];
		$mail->Username = $GLOBALS['smtpHostUsername'];
		$mail->Password = $GLOBALS['smtpHostPassword'];
		$mail->SMTPSecure = $GLOBALS['smtpHostType'];
		$mail->Port = $GLOBALS['smtpHostPort'];
		$mail->setFrom($GLOBALS['smtpHostSenderEmail'], $GLOBALS['smtpHostSenderName']);
		$mail->addReplyTo($GLOBALS['smtpHostSenderEmail'], $GLOBALS['smtpHostSenderName']);
		$mail->isHTML(true);
		if($email){
			$mail->addAddress($email, $username);
		}
		if($cc){
			$mail->addCC($cc);
		}
		if($bcc){
			if(strpos($bcc , ',') === false){
				$mail->addBCC($bcc);
			}else{
				$allEmails = explode(",",$bcc);
				foreach($allEmails as $gotEmail){
					$mail->addBCC($gotEmail);
				}
			}
		}
		$mail->Subject = $subject;
		$mail->Body    = $body;
		$mail->send();
		writeLog('success', 'Mail Function -  E-Mail Sent', $GLOBALS['organizrUser']['username']);
		return true;
	} catch (Exception $e) {
		writeLog('error', 'Mail Function -  E-Mail Failed['.$mail->ErrorInfo.']', $GLOBALS['organizrUser']['username']);
		return false;
	}
	return false;
}
//EMAIL SHIT
function sendTestEmail($to, $from, $host, $auth, $username, $password, $type, $port, $sendername){
	try {
		$mail = new PHPMailer(true);
		$mail->isSMTP();
		$mail->Host = $host;
		$mail->SMTPAuth = $auth;
		$mail->Username = $username;
		$mail->Password = $password;
		$mail->SMTPSecure = $type;
		$mail->Port = $port;
		$mail->setFrom($from, $sendername);
		$mail->addReplyTo($from, $sendername);
		$mail->isHTML(true);
		$mail->addAddress($to, "Organizr Admin");
		$mail->Subject = "Organizr Test E-Mail";
		$mail->Body    = "This was just a test!";
		$mail->send();
		writeLog('success', 'Mail Function -  E-Mail Test Sent', $GLOBALS['organizrUser']['username']);
		return true;
	} catch (Exception $e) {
		writeLog('error', 'Mail Function -  E-Mail Test Failed['.$mail->ErrorInfo.']', $GLOBALS['organizrUser']['username']);
		return false;
	}
	return false;
}
*/
