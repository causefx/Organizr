<?php
//include functions
require_once 'functions.php';
//Set result array
$result = array();
//Get request method
$method = $_SERVER['REQUEST_METHOD'];
$pretty = isset($_GET['pretty']) ? true : false;
reset($_GET);
$function = (key($_GET) ? str_replace("/", "_", key($_GET)) : false);
//Exit if $function is blank
if ($function === false) {
	$result['status'] = "error";
	$result['statusText'] = "No API Path Supplied";
	exit(json_encode($result));
}
$approvedFunctionsBypass = array(
	'v1_upgrade',
	'v1_update',
	'v1_force',
	'v1_auth',
	'v1_wizard_config',
	'v1_login',
	'v1_wizard_path',
	'v1_login_api'
);
if (!in_array($function, $approvedFunctionsBypass)) {
	if (isApprovedRequest($method) === false) {
		$result['status'] = "error";
		$result['statusText'] = "Not Authorized";
		writeLog('success', 'Killed Attack From [' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'No Referer') . ']', $GLOBALS['organizrUser']['username']);
		exit(json_encode($result));
	}
}
$result['request'] = key($_GET);
$result['params'] = $_POST;

//Custom Page Check
if(strpos($function,'v1_custom_page_') !== false){
	$endpoint = explode('v1_custom_page_', $function)[1];
	$function = 'v1_custom_page';
}
switch ($function) {
	case 'v1_settings_page':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = $pageSettings;
					writeLog('success', 'Admin Function -  Accessed Settings Page', $GLOBALS['organizrUser']['username']);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
					writeLog('error', 'Admin Function -  Tried to access Settings Page', $GLOBALS['organizrUser']['username']);
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_homepage_page':
		switch ($method) {
			case 'GET':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = $pageHomepage;
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_plugins':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = $pageSettingsPlugins;
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_tab_editor_homepage':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = $pageSettingsTabEditorHomepage;
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_tab_editor_homepage_order':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = $pageSettingsTabEditorHomepageOrder;
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_homepage_list':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = getHomepageList();
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = editPlugins($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_plugins_list':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = getPlugins();
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = editPlugins($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_settings_logs':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = $pageSettingsSettingsLogs;
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_settings_sso':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = $pageSettingsSettingsSSO;
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_settings_main':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = $pageSettingsSettingsMain;
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_customize_appearance':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = $pageSettingsCustomizeAppearance;
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = editAppearance($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_remove_file':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = removeFile($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_update_config':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = updateConfigItem($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_update_config_multiple':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = updateConfigMultiple($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_update_config_multiple_form':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = updateConfigMultipleForm($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_homepage_connect':
		switch ($method) {
			case 'POST':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = homepageConnect($_POST);
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_ping_list':
		switch ($method) {
			case 'POST':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = ping($_POST['data']['pingList']);
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_test_api_connection':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = testAPIConnection($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_tab_editor_tabs':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = $pageSettingsTabEditorTabs;
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = editTabs($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_tab_editor_categories':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = $pageSettingsTabEditorCategories;
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = editCategories($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_user_manage_users':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = $pageSettingsUserManageUsers;
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = adminEditUser($_POST);
				} elseif (qualifyRequest(998)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = editUser($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_manage_user':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(998)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = editUser($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_user_manage_groups':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = $pageSettingsUserManageGroups;
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = adminEditGroup($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_image_manager_view':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = $pageSettingsImageManager;
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = editImages();
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_wizard_page':
		switch ($method) {
			case 'GET':
				if (!file_exists('config' . DIRECTORY_SEPARATOR . 'config.php')) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = $pageWizard;
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'Wizard has already been run';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
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
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_wizard_config':
		switch ($method) {
			case 'POST':
				if (!file_exists('config' . DIRECTORY_SEPARATOR . 'config.php')) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = wizardConfig($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'Wizard has already been run';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_wizard_path':
		switch ($method) {
			case 'POST':
				if (!file_exists('config' . DIRECTORY_SEPARATOR . 'config.php')) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = wizardPath($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'Wizard has already been run';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
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
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_login_api':
		switch ($method) {
			case 'POST':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = apiLogin();
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
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
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_recover':
		switch ($method) {
			case 'POST':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = recover($_POST);
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_unlock':
		switch ($method) {
			case 'POST':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = unlock($_POST);
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_lock':
		switch ($method) {
			case 'POST':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = lock();
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_test_iframe':
		switch ($method) {
			case 'POST':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = frameTest($_POST['data']['url']);
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_upgrade':
	case 'v1_update':
	case 'v1_force':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = upgradeInstall($_POST['data']['branch'], $_POST['data']['stage']);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
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
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
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
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_login_log':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = getLog('loginLog');
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_organizr_log':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = getLog('org');
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_user_list':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = allUsers();
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_tab_list':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = allTabs();
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_image_list':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = getImages();
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_customize_appearance':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = getCustomizeAppearance();
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_sso':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = getSSO();
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_main':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = getSettingsMain();
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_plugin_install':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = installPlugin($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_plugin_remove':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = removePlugin($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_theme_install':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = installTheme($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_theme_remove':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = removeTheme($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_user_edit':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = adminEditUser($_POST);
				} elseif (qualifyRequest(998)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = editUser($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_2fa_create':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(998)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = create2FA($_POST['data']['type']);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_2fa_save':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(998)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = save2FA($_POST['data']['secret'], $_POST['data']['type']);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_2fa_verify':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(998)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = verify2FA($_POST['data']['secret'], $_POST['data']['code'], $_POST['data']['type']);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_2fa_remove':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(998)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = remove2FA();
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
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
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_launch_organizr':
		switch ($method) {
			case 'GET':
				$pluginSearch = '-enabled';
				$pluginInclude = '-include';
				$status = array();
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$status['status'] = organizrStatus();
				$result['appearance'] = loadAppearance();
				$status['user'] = $GLOBALS['organizrUser'];
				$status['categories'] = loadTabs()['categories'];
				$status['tabs'] = loadTabs()['tabs'];
				$status['plugins'] = array_filter($GLOBALS, function ($k) use ($pluginSearch) {
					return stripos($k, $pluginSearch) !== false;
				}, ARRAY_FILTER_USE_KEY);
				$status['plugins']['includes'] = array_filter($GLOBALS, function ($k) use ($pluginInclude) {
					return stripos($k, $pluginInclude) !== false;
				}, ARRAY_FILTER_USE_KEY);
				$result['data'] = $status;
				$result['branch'] = $GLOBALS['branch'];
				$result['theme'] = $GLOBALS['theme'];
				$result['style'] = $GLOBALS['style'];
				$result['version'] = $GLOBALS['installedVersion'];
				$result['sso'] = array(
					'myPlexAccessToken' => isset($_COOKIE['mpt']) ? $_COOKIE['mpt'] : false,
					'id_token' => isset($_COOKIE['Auth']) ? $_COOKIE['Auth'] : false
				);
				$result['settings'] = organizrSpecialSettings();
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_auth':
		switch ($method) {
			case 'GET':
				auth();
				break;
			default:
				//exit(http_response_code(401));
				auth();
				break;
		}
		break;
	case 'v1_plugin':
		switch ($method) {
			case 'POST':
			case 'GET':
				// Include all plugin api Calls
				foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . "*.php") as $filename) {
					require_once $filename;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_image':
		switch ($method) {
			case 'GET':
				getImage();
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_downloader':
		switch ($method) {
			case 'POST':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = downloader($_POST);
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_import_users':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = importUsersType($_POST);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_ombi':
		switch ($method) {
			case 'POST':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = ombiAPI($_POST);
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_plex_join':
		switch ($method) {
			case 'POST':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = plexJoinAPI($_POST);
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_emby_join':
		switch ($method) {
			case 'POST':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = embyJoinAPI($_POST);
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_token_revoke':
		switch ($method) {
			case 'POST':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = revokeToken($_POST);
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_update_db_manual':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = updateDB($GLOBALS['installedVersion']);
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_version':
		switch ($method) {
			case 'GET':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = $GLOBALS['installedVersion'];
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_ping':
		switch ($method) {
			case 'GET':
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = 'pong';
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_docker_update':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = dockerUpdate();
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_windows_update':
		switch ($method) {
			case 'GET':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = windowsUpdate();
				} else {
					$result['status'] = 'error';
					$result['statusText'] = 'API/Token invalid or not set';
					$result['data'] = null;
				}
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_custom_page':
		switch ($method) {
			case 'GET':
				$customPage = 'customPage'.ucwords($endpoint);
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = $$customPage;
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
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
if (!$result) {
	$result['status'] = "error";
	$result['error'] = "An error has occurred";
}
$result['generationDate'] = $GLOBALS['currentTime'];
$result['generationTime'] = formatSeconds(timeExecution());
//return JSON array
if ($pretty) {
	echo '<pre>' . safe_json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
} else {
	exit(safe_json_encode($result, JSON_HEX_QUOT | JSON_HEX_TAG));
}
