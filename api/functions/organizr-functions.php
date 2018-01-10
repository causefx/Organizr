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
                }
            }
        }
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
function loadAppearance(){
    $appearance = array();
    $appearance['logo'] = $GLOBALS['logo'];
    $appearance['title'] = $GLOBALS['title'];
	$appearance['useLogo'] = $GLOBALS['useLogo'];
    $appearance['headerColor'] = $GLOBALS['headerColor'];
    return $appearance;
}
function getCustomizeAppearance(){
    if(file_exists('config'.DIRECTORY_SEPARATOR.'config.php')){
        return array(
            'config' => array(/*
                array(
                    'type' => 'select',
                    'name' => 'branch',
                    'label' => 'Organizr Branch',
                    'value' => $GLOBALS['branch'],
                    'options' => array(
                        'Master' => 'v2-master',
                        'Develop' => 'v2-develop'
                    )
                ),*/
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
                ),
                array(
                    'type' => 'input',
                    'name' => 'headerColor',
                    'label' => 'Nav Bar Color',
                    'value' => $GLOBALS['headerColor'],
                    'class' => 'colorpicker',
                    'disabled' => true
                )
            ),
            'database' => array(

            )
        );
    }
}
function auth(){
    $debug = false; // CAREFUL WHEN SETTING TO TRUE AS THIS OPENS AUTH UP
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
    $ignore = Array(".", "..", "._.DS_Store", ".DS_Store");
    $allIcons = array();
    foreach($images as $image){
        if(!in_array($image, $ignore)) {
            $allIcons[] = $path.$image;
        }
    }
    return $allIcons;
}
