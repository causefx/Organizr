<?php
/**
 * @apiDefine       UserNotAuthorizedError
 *
 * @apiError        UserNotAuthorized The user is not authorized or Token not valid
 *
 * @apiErrorExample Error-Response:
 *      HTTP/1.1 401 Not Authorized
 *      {
 *          "status": "error",
 *          "statusText": "API/Token invalid or not set",
 *          "data": null
 *      }
 */
/**
 * @apiDefine         DataBooleanSuccess
 * @apiSuccess {Boolean} data Output Boolean.
 * @apiSuccessExample Success-Response:
 *      HTTP/1.1 200 OK
 *      {
 *          "status": "success",
 *          "statusText": "success",
 *          "data": true
 *      }
 *
 */
/**
 * @apiDefine         DataJSONSuccess
 * @apiSuccess {JSON} data Output JSON.
 * @apiSuccessExample Success-Response:
 *      HTTP/1.1 200 OK
 *      {
 *          "status": "success",
 *          "statusText": "success",
 *          "data": { **JSON** }
 *      }
 *
 */
/**
 * @apiDefine         DataHTMLSuccess
 * @apiSuccess {String} data Output of Page.
 *
 * @apiSuccessExample Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "status": "success",
 *       "statusText": "success",
 *       "data": "<html>html encoded elements</html>"
 *     }
 *
 */
/**
 * @apiDefine admin Admin or API Key Access Only
 * Only the Admin/Co-Admin and API Key have access to this endpoint
 */
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
		http_response_code(401);
		writeLog('success', 'Killed Attack From [' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'No Referer') . ']', $GLOBALS['organizrUser']['username']);
		exit(json_encode($result));
	}
}
$result['request'] = key($_GET);
$result['params'] = $_POST;
//Custom Page Check
if (strpos($function, 'v1_custom_page_') !== false) {
	$endpoint = explode('v1_custom_page_', $function)[1];
	$function = 'v1_custom_page';
}
switch ($function) {
	case 'v1_settings_page':
		switch ($method) {
			/**
			 * @api               {get} v1/settings/page Get Admin Settings
			 * @apiVersion        1.0.0
			 * @apiName           GetSettingsPage
			 * @apiGroup          Pages
			 * @apiUse            DataBooleanSuccess
			 * @apiUse            UserNotAuthorizedError
			 */
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
			/**
			 * @api               {get} v1/homepage/page Get Homepage
			 * @apiVersion        1.0.0
			 * @apiName           GetHomepagePage
			 * @apiGroup          Pages
			 * @apiUse            DataHTMLSuccess
			 * @apiUse            UserNotAuthorizedError
			 */
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
			/**
			 * @api               {get} v1/settings/plugins Get Plugins
			 * @apiVersion        1.0.0
			 * @apiName           GetPluginsPage
			 * @apiGroup          Pages
			 * @apiUse            DataHTMLSuccess
			 * @apiUse            UserNotAuthorizedError
			 */
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
			/**
			 * @api               {get} v1/settings/tab/editor/homepage Get Homepage Settings
			 * @apiVersion        1.0.0
			 * @apiName           GetSettingsTabEditorHomepagePage
			 * @apiGroup          Pages
			 * @apiUse            DataHTMLSuccess
			 * @apiUse            UserNotAuthorizedError
			 */
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
			/**
			 * @api               {get} v1/settings/tab/editor/homepage Get Homepage Order
			 * @apiVersion        1.0.0
			 * @apiName           GetSettingsTabEditorHomepageOrderPage
			 * @apiGroup          Pages
			 * @apiUse            DataHTMLSuccess
			 * @apiUse            UserNotAuthorizedError
			 */
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
			/**
			 * @api               {get} v1/settings/homepage/list Get Homepage Settings
			 * @apiVersion        1.0.0
			 * @apiName           GetHomepageSettigns
			 * @apiGroup          Homepage
			 * @apiSuccess {String} data Output of all Homepage Settings.
			 * @apiSuccessExample Success-Response:
			 *      HTTP/1.1 200 OK
			 *      {
			 *          "status": "success",
			 *          "statusText": "success",
			 *          "data": [{
			 *              "name": "HealthChecks",
			 *              "enabled": true,
			 *              "image": "plugins\/images\/tabs\/healthchecks.png",
			 *              "category": "Monitor",
			 *              "settings": {
			 *                  "Enable": [
			 *                      {
			 *                          "type": "switch",
			 *                          "name": "homepageHealthChecksEnabled",
			 *                          "label": "Enable",
			 *                          "value": true
			 *                      }, {
			 *                          "type": "select",
			 *                          "name": "homepageHealthChecksAuth",
			 *                          "label": "Minimum Authentication",
			 *                          "value": "1",
			 *                          "options": [
			 *                              {
			 *                                  "name": "Admin",
			 *                                  "value": 0
			 *                              }, {
			 *                                  "name": "Co-Admin",
			 *                                  "value": 1
			 *                              }, {
			 *                                  "name": "Super User",
			 *                                  "value": 2
			 *                              }, {
			 *                                  "name": "Power User",
			 *                                  "value": 3
			 *                              }, {
			 *                                  "name": "User",
			 *                                  "value": 4
			 *                              }, {
			 *                                  "name": "temp again",
			 *                                  "value": 5
			 *                              }, {
			 *                                  "name": "GuestAccts",
			 *                                  "value": 999
			 *                              }
			 *                          ]
			 *                      }
			 *                  ],
			 *              "Connection": [
			 *                  {
			 *                      "type": "input",
			 *                      "name": "healthChecksURL",
			 *                       "label": "URL",
			 *                      "value": "https://healthchecks.io/api/v1/checks/",
			 *                      "help": "URL for HealthChecks API",
			 *                      "placeholder": "HealthChecks API URL"
			 *                  }, {
			 *                      "type": "password-alt",
			 *                      "name": "healthChecksToken",
			 *                      "label": "Token",
			 *                      "value": "TOKENHERE"
			 *                  }
			 *              ],
			 *              "Misc Options": [
			 *                  {
			 *                      "type": "input",
			 *                      "name": "healthChecksTags",
			 *                      "label": "Tags",
			 *                      "value": "",
			 *                      "help": "Pull only checks with this tag - Blank for all",
			 *                      "placeholder": "Multiple tags using CSV - tag1,tag2"
			 *                  }, {
			 *                      "type": "select",
			 *                      "name": "homepageHealthChecksRefresh",
			 *                      "label": "Refresh Seconds",
			 *                      "value": "3600000",
			 *                      "options": [
			 *                          {
			 *                              "name": "5",
			 *                              "value": "5000"
			 *                          }, {
			 *                              "name": "10",
			 *                              "value": "10000"
			 *                          }, {
			 *                              "name": "15",
			 *                              "value": "15000"
			 *                          }, {
			 *                              "name": "30",
			 *                              "value": "30000"
			 *                          }, {
			 *                              "name": "60 [1 Minute]",
			 *                              "value": "60000"
			 *                          }, {
			 *                              "name": "300 [5 Minutes]",
			 *                              "value": "300000"
			 *                          }, {
			 *                              "name": "600 [10 Minutes]",
			 *                              "value": "600000"
			 *                          }, {
			 *                              "name": "900 [15 Minutes]",
			 *                              "value": "900000"
			 *                          }, {
			 *                              "name": "1800 [30 Minutes]",
			 *                              "value": "1800000"
			 *                          }, {
			 *                              "name": "3600 [1 Hour]",
			 *                              "value": "3600000"
			 *                          }
			 *                      ]
			 *                  }
			 *              ]
			 *          }]
			 *      }
			 * @apiUse            UserNotAuthorizedError
			 */
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
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_settings_plugins_list':
		/**
		 * @api               {get} v1/settings/plugins/list Get List of Plugins
		 * @apiVersion        1.0.0
		 * @apiName           GetPlugins
		 * @apiGroup          Plugins
		 * @apiSuccess {String} data Output plugins list.
		 * @apiSuccessExample Success-Response:
		 *     HTTP/1.1 200 OK
		 *     {
		 *       "status": "success",
		 *       "statusText": "success",
		 *       "data": {
		 *         "chat": {
		 *           "name": "Chat",
		 *           "author": "CauseFX",
		 *           "category": "Utilities",
		 *           "link": "",
		 *           "license": "personal,business",
		 *           "idPrefix": "CHAT",
		 *           "configPrefix": "CHAT",
		 *           "version": "1.0.0",
		 *           "image": "plugins/images/chat.png",
		 *           "settings": true,
		 *           "homepage": false,
		 *           "enabled": true
		 *         }
		 *       }
		 *     }
		 * @apiUse            UserNotAuthorizedError
		 */
		/**
		 * @api               {post} v1/settings/plugins/list Toggle Plugin
		 * @apiVersion        1.0.0
		 * @apiName           TogglePlugin
		 * @apiGroup          Plugins
		 * @apiParam {Object} data         nested data object.
		 * @apiParam {String} data[action] enable/disable.
		 * @apiParam {String} data[name]    Name of Plugin.
		 * @apiParam {String} data[configName]   configName i.e. CHAT-enabled.
		 * @apiUse            DataBooleanSuccess
		 * @apiUse            UserNotAuthorizedError
		 */
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
		/**
		 * @api               {get} v1/settings/settings/logs Get Logs
		 * @apiVersion        1.0.0
		 * @apiName           GetLogsPage
		 * @apiGroup          Pages
		 * @apiUse            DataHTMLSuccess
		 * @apiUse            UserNotAuthorizedError
		 */
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
		/**
		 * @api               {get} v1/settings/settings/sso Get SSO
		 * @apiVersion        1.0.0
		 * @apiName           GetSSOPage
		 * @apiGroup          Pages
		 * @apiUse            DataHTMLSuccess
		 * @apiUse            UserNotAuthorizedError
		 */
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
		/**
		 * @api               {get} v1/settings/settings/main Get Settings Main
		 * @apiVersion        1.0.0
		 * @apiName           GetSettingsMainPage
		 * @apiGroup          Pages
		 * @apiUse            DataHTMLSuccess
		 * @apiUse            UserNotAuthorizedError
		 */
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
		/**
		 * @api               {get} v1/settings/customize/appearance Get Customize Appearance
		 * @apiVersion        1.0.0
		 * @apiName           GetCustomizePage
		 * @apiGroup          Pages
		 * @apiUse            DataHTMLSuccess
		 * @apiUse            UserNotAuthorizedError
		 */
		/**
		 * @api               {post} v1/settings/customize/appearance Edit Customize Appearance
		 * @apiVersion        1.0.0
		 * @apiName           PostCustomizePage
		 * @apiGroup          Appearance
		 * @apiParam {Object} data         nested data object.
		 * @apiParam {String} data[action] editCustomizeAppearance.
		 * @apiParam {String} data[name]    Name.
		 * @apiParam {String} data[value]   Value.
		 * @apiUse            DataBooleanSuccess
		 * @apiUse            UserNotAuthorizedError
		 */
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
		/**
		 * @api               {post} v1/remove/file Remove File
		 * @apiVersion        1.0.0
		 * @apiName           PostRemoveFile
		 * @apiGroup          Files
		 * @apiParam {Object} data         nested data object.
		 * @apiParam {String} data[path] File Path.
		 * @apiParam {String} data[name]    File Name.
		 * @apiUse            DataBooleanSuccess
		 * @apiUse            UserNotAuthorizedError
		 */
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
		/**
		 * @api               {post} v1/update/config Update Config Item
		 * @apiVersion        1.0.0
		 * @apiName           PostUpdateConfig
		 * @apiGroup          Config
		 * @apiParam {Object} data         nested data object.
		 * @apiParam {String} data[type] input|select|switch|password.
		 * @apiParam {String} data[name]    Name.
		 * @apiParam {String} data[value]   Value.
		 * @apiUse            DataBooleanSuccess
		 * @apiUse            UserNotAuthorizedError
		 */
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
		/**
		 * @api               {post} v1/update/config/multiple Update Multiple Config Items
		 * @apiVersion        1.0.0
		 * @apiName           PostUpdateConfigMultiple
		 * @apiGroup          Config
		 * @apiPermission     admin
		 * @apiParam  {Object} data[payload]         nested payload object.
		 * @apiParam  {String}   data.:keyName     Value of Name defined from key.
		 * @apiParamExample {json} Request-Example:
		 *      {
		 *          "data": {
		 *              "payload": {
		 *                  "title": "Organizr V2",
		 *                  "logo": "plugins/images/organizr/logo-wide.png"
		 *              }
		 *          }
		 *     }
		 * @apiUse            DataBooleanSuccess
		 * @apiUse            UserNotAuthorizedError
		 */
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
		/**
		 * @api               {post} v1/update/config/multiple/form Update Multiple Config Items Form
		 * @apiVersion        1.0.0
		 * @apiName           PostUpdateConfigMultipleForm
		 * @apiGroup          Config
		 * @apiPermission     admin
		 * @apiParam  {Object} data[payload]         nested payload object.
		 * @apiParam  {Object}   data.:keyName     Config ID/Key.
		 * @apiParam  {String}   data.:keyName.name     Config ID/Key.
		 * @apiParam  {String}   data.:keyName.value     Config Value.
		 * @apiParam  {String}   data.:keyName.type     Config Type input|select|switch|password.
		 * @apiParamExample {json} Request-Example:
		 *      {
		 *          "data": {
		 *              "payload": {
		 *                  "title": {
		 *                      "name": "title",
		 *                      "value": "Organizr V2",
		 *                      "type": "input"
		 *                  },
		 *                  "logo": {
		 *                      "name": "logo",
		 *                      "value": "plugins/images/organizr/logo-wide.png",
		 *                      "type": "input"
		 *                  }
		 *              }
		 *          }
		 *     }
		 * @apiUse            DataBooleanSuccess
		 * @apiUse            UserNotAuthorizedError
		 */
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
		/**
		 * @api               {post} v1/homepage/connect Homepage Item Connect
		 * @apiVersion        1.0.0
		 * @apiName           PostHomepageItemConnect
		 * @apiGroup          Homepage
		 * @apiPermission     admin
		 * @apiParam  {Object} data        payload object.
		 * @apiParam  {Object}   data[action]     Homepage Item i.e. getPlexStreams|getPlexRecent.
		 * @apiParamExample {json} Request-Example:
		 *      {
		 *          "data": {
		 *              "action": "getPlexStreams"
		 *          }
		 *     }
		 * @apiUse            DataJSONSuccess
		 * @apiUse            UserNotAuthorizedError
		 */
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
		/**
		 * @api               {post} v1/ping/list Homepage Item Connect
		 * @apiVersion        1.0.0
		 * @apiName           PostPingList
		 * @apiGroup          Ping
		 * @apiParam  {Object} data        payload object.
		 * @apiParam  {Object[]}   data[pingList]     List of ip/hostname and ports [Optional String of hostname:port]
		 * @apiParamExample {json} Object
		 *      {
		 *          "data": {
		 *              "pingList": ["docker.home.lab:3579", "docker.home.lab:8181"]
		 *          }
		 *     }
		 * @apiParamExample {json} String
		 *      {
		 *          "data": {
		 *              "pingList": ["docker.home.lab:3579", "docker.home.lab:8181"]
		 *          }
		 *     }
		 * @apiSuccess {String} data Output ping results and response times.
		 * @apiSuccessExample Success-Response:
		 *      HTTP/1.1 200 OK
		 *      {
		 *          "status": "success",
		 *          "statusText": "success",
		 *          "data":{
		 *              "docker.home.lab:3579":10.77,
		 *              "docker.home.lab:8181":0.66
		 *          }
		 *     }
		 * @apiUse            UserNotAuthorizedError
		 */
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
		/**
		 * @api               {get} v1/settings/tab/editor/tabs Get Tab Editor Tabs
		 * @apiVersion        1.0.0
		 * @apiName           GetTabEditorTabsPage
		 * @apiGroup          Pages
		 * @apiUse            DataHTMLSuccess
		 * @apiUse            UserNotAuthorizedError
		 */
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
		/**
		 * @api               {get} v1/settings/tab/editor/categories Get Tab Editor Categories
		 * @apiVersion        1.0.0
		 * @apiName           GetTabEditorCategoriesPage
		 * @apiGroup          Pages
		 * @apiUse            DataHTMLSuccess
		 * @apiUse            UserNotAuthorizedError
		 */
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
		/**
		 * @api               {get} v1/settings/user/manage/users Get Manage Users
		 * @apiVersion        1.0.0
		 * @apiName           GetManageUsersPage
		 * @apiGroup          Pages
		 * @apiUse            DataHTMLSuccess
		 * @apiUse            UserNotAuthorizedError
		 */
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
				$status['categories'] = loadTabs('categories');
				$status['tabs'] = loadTabs('tabs');
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
	case 'v1_token_validate':
		switch ($method) {
			case 'GET':
				$token = $_GET['token'] ?? false;
				break;
			case 'POST':
				$token = $_POST['token'] ?? false;
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		$token = validateToken($token);
		if ($token) {
			$result['status'] = 'success';
			$result['statusText'] = 'success';
			$result['data'] = $token;
		} else {
			$result['status'] = 'error';
			$result['statusText'] = 'Token not validated or empty';
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
				$customPage = 'customPage' . ucwords($endpoint);
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
	case 'v1_youtube_search':
		switch ($method) {
			case 'GET':
				$query = isset($_GET['q']) ? $_GET['q'] : false;
				$result['status'] = isset($_GET['q']) ? 'success' : 'error';
				$result['statusText'] = isset($_GET['q']) ? 'success' : 'missing query';
				$result['data'] = youtubeSearch($query);
				break;
			default:
				$result['status'] = 'error';
				$result['statusText'] = 'The function requested is not defined for method: ' . $method;
				break;
		}
		break;
	case 'v1_scrape':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(998)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = scrapePage($_POST);
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
	case 'v1_coordinates_search':
		switch ($method) {
			case 'POST':
				if (qualifyRequest(1)) {
					$result['status'] = 'success';
					$result['statusText'] = 'success';
					$result['data'] = searchCityForCoordinates($_POST);
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
//Set HTTP Code
if ($result['statusText'] == "API/Token invalid or not set") {
	http_response_code(401);
} else {
	http_response_code(200);
}
//return JSON array
if ($pretty) {
	echo '<pre>' . safe_json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
} else {
	exit(safe_json_encode($result, JSON_HEX_QUOT | JSON_HEX_TAG));
}
