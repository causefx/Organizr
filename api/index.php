<?php
$generationTime = -microtime(true);
//include functions
require_once 'functions.php';
//Set result array
$result = array();
//Get request method
$method = $_SERVER['REQUEST_METHOD'];
reset($_GET);
$function = (key($_GET) ? str_replace("/","_",key($_GET)) : false);
//Exit if $function is blank
if($function === false){
    $result['status'] = "error";
    $result['statusText'] = "No API Path Supplied";
    exit(json_encode($result));
}
$result['request'] = key($_GET);
switch ($function) {
    case 'v1_settings_page':
        switch ($method) {
            case 'GET':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = $pageSettings;
                    writeLog('success', 'Admin Function -  Accessed Settings Page', $GLOBALS['organizrUser']['username']);
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                    writeLog('error', 'Admin Function -  Tried to access Settings Page', $GLOBALS['organizrUser']['username']);
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_settings_settings_logs':
        switch ($method) {
            case 'GET':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = $pageSettingsSettingsLogs;
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_settings_customize_appearance':
        switch ($method) {
            case 'GET':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = $pageSettingsCustomizeAppearance;
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            case 'POST':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = editAppearance($_POST);
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_settings_tab_editor_tabs':
        switch ($method) {
            case 'GET':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = $pageSettingsTabEditorTabs;
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            case 'POST':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = editTabs($_POST);
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_settings_tab_editor_categories':
        switch ($method) {
            case 'GET':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = $pageSettingsTabEditorCategories;
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            case 'POST':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = editCategories($_POST);
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_settings_user_manage_users':
        switch ($method) {
            case 'GET':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = $pageSettingsUserManageUsers;
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            case 'POST':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = adminEditUser($_POST);
                }elseif(qualifyRequest(998)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = editUser($_POST);
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_settings_user_manage_groups':
        switch ($method) {
            case 'GET':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = $pageSettingsUserManageGroups;
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            case 'POST':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = adminEditGroup($_POST);
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_wizard_page':
        switch ($method) {
            case 'GET':
                if(!file_exists('config'.DIRECTORY_SEPARATOR.'config.php')){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = $pageWizard;
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'Wizard has already been run';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_dependencies_page':
        switch ($method) {
            case 'GET':
                $result['status'] = 'success';
                $result['statusText'] = 'success';
                $result['data'] = $pageDependencies;
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_wizard_config':
        switch ($method) {
            case 'POST':
                if(!file_exists('config'.DIRECTORY_SEPARATOR.'config.php')){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = wizardConfig($_POST);
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'Wizard has already been run';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_login':
        switch ($method) {
            case 'POST':
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = login($_POST);
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_register':
        switch ($method) {
            case 'POST':
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = register($_POST);
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_login_page':
        switch ($method) {
            case 'GET':
                $result['status'] = 'success';
                $result['statusText'] = 'success';
                $result['data'] = $pageLogin;
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_lockscreen':
        switch ($method) {
            case 'GET':
                $result['status'] = 'success';
                $result['statusText'] = 'success';
                $result['data'] = $pageLockScreen;
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_login_log':
        switch ($method) {
            case 'GET':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = getLog('loginLog');
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
	case 'v1_organizr_log':
        switch ($method) {
            case 'GET':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = getLog('org');
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_user_list':
        switch ($method) {
            case 'GET':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = allUsers();
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_tab_list':
        switch ($method) {
            case 'GET':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = allTabs();
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_customize_appearance':
        switch ($method) {
            case 'GET':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = getCustomizeAppearance();
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_user_edit':
        switch ($method) {
            case 'POST':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = adminEditUser($_POST);
                }elseif(qualifyRequest(998)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = editUser($_POST);
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_logout':
        switch ($method) {
            case 'GET':
                $result['status'] = 'success';
                $result['statusText'] = 'success';
                $result['data'] = logout();
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_launch_organizr':
        switch ($method) {
            case 'GET':
                $status = array();
                $result['status'] = 'success';
                $result['statusText'] = 'success';
                $status['status'] = organizrStatus();
                $result['appearance'] = loadAppearance();
                $status['user'] = $GLOBALS['organizrUser'];
                $status['categories'] = loadTabs()['categories'];
                $status['tabs'] = loadTabs()['tabs'];
                $result['data'] = $status;
				$result['branch'] = $GLOBALS['branch'];
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
	case 'v1_auth':
        switch ($method) {
            case 'GET':
                auth();
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    case 'v1_plugin':
        switch ($method) {
            case 'GET':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = 'plugin admin';
                }elseif(qualifyRequest(998)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = 'plugin logged in';
                }elseif(qualifyRequest(999)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = 'plugin guest';
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            case 'POST':
                if(qualifyRequest(1)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = 'plugin admin';
                }elseif(qualifyRequest(998)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = 'plugin logged in';
                }elseif(qualifyRequest(999)){
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = 'plugin guest';
                }else{
                    $result['status'] = 'error';
                    $result['statusText'] = 'API/Token invalid or not set';
                    $result['data'] = null;
                }
                break;
            default:
                $result['status'] = 'error';
                $result['statusText'] = 'The function requested is not defined for method: '.$method;
                break;
        }
        break;
    default:
        //No Function Available
        $result['status'] = 'error';
        $result['statusText'] = 'function requested is not defined';
        break;
}
//Set Default Result
if(!$result){
    $result['status'] = "error";
    $result['error'] = "An error has occurred";
}
$result['generationDate'] = $GLOBALS['currentTime'];
$generationTime += microtime(true);
$result['generationTime'] = (sprintf('%f', $generationTime)*1000).'ms';
//return JSON array
exit(json_encode($result, JSON_HEX_QUOT | JSON_HEX_TAG));
