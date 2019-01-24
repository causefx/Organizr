<?php
// PLUGIN INFORMATION
$GLOBALS['plugins'][]['chat'] = array( // Plugin Name
	'name' => 'Chat', // Plugin Name
	'author' => 'CauseFX', // Who wrote the plugin
	'category' => 'Utilities', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal,business', // License Type use , for multiple
	//'fileName'=>'php-mailer.php',
	//'configFile'=>'php-mailer.php',
	//'apiFile'=>'php-mailer.php',
	'idPrefix' => 'CHAT', // html element id prefix
	'configPrefix' => 'CHAT', // config file prefix for array items without the hypen
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'plugins/images/chat.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings page? true or false
	'homepage' => false // Is plugin for use on homepage? true or false
);
// INCLUDE/REQUIRE FILES
// PLUGIN FUNCTIONS
/* GET CHAT SETTINGS */
function chatGetSettings()
{
	return array(
		'custom' => '
				<div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">
								<span lang="en">Notice</span>
                            </div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
									<ul class="list-icons">
                                        <li><i class="fa fa-chevron-right text-danger"></i> <a href="https://dashboard.pusher.com/accounts/sign_up" target="_blank">Signup for Pusher [FREE]</a></li>
                                        <li><i class="fa fa-chevron-right text-danger"></i> Create an App called whatever you like and choose a cluster (Close to you)</li>
                                        <li><i class="fa fa-chevron-right text-danger"></i> Frontend (JQuery) - Backend (PHP)</li>
                                        <li><i class="fa fa-chevron-right text-danger"></i> Click the overview tab on top left</li>
                                        <li><i class="fa fa-chevron-right text-danger"></i> Copy and paste the 4 values into Organizr</li>
                                        <li><i class="fa fa-chevron-right text-danger"></i> Save and reload!</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
				</div>
				',
		'Options' => array(
			array(
				'type' => 'select',
				'name' => 'CHAT-Auth-include',
				'label' => 'Minimum Authentication',
				'value' => $GLOBALS['CHAT-Auth-include'],
				'options' => groupSelect()
			),
			array(
				'type' => 'number',
				'name' => 'CHAT-messageLoadLimit',
				'label' => '# of Previous Messages',
				'value' => $GLOBALS['CHAT-messageLoadLimit'],
				'placeholder' => ''
			),
			array(
				'type' => 'select',
				'name' => 'CHAT-userRefreshTimeout',
				'label' => 'Refresh Seconds',
				'value' => $GLOBALS['CHAT-userRefreshTimeout'],
				'options' => optionTime()
			),
			array(
				'type' => 'select',
				'name' => 'CHAT-newMessageSound-include',
				'label' => 'Message Sound',
				'value' => $GLOBALS['CHAT-newMessageSound-include'],
				'options' => getSounds()
			),
			array(
				'type' => 'switch',
				'name' => 'CHAT-useSSL',
				'label' => 'Use Pusher SSL',
				'help' => 'If messages get stuck sending, please turn this option off.',
				'value' => $GLOBALS['CHAT-useSSL']
			)
		),
		'Connection' => array(
			array(
				'type' => 'password-alt',
				'name' => 'CHAT-authKey-include',
				'label' => 'Auth Key',
				'value' => $GLOBALS['CHAT-authKey-include']
			),
			array(
				'type' => 'password-alt',
				'name' => 'CHAT-secret',
				'label' => 'API Secret',
				'value' => $GLOBALS['CHAT-secret']
			),
			array(
				'type' => 'input',
				'name' => 'CHAT-appID-include',
				'label' => 'App ID',
				'value' => $GLOBALS['CHAT-appID-include']
			),
			array(
				'type' => 'input',
				'name' => 'CHAT-cluster-include',
				'label' => 'App Cluster',
				'value' => $GLOBALS['CHAT-cluster-include']
			),
		)
	);
}

function sendChatMessage($array)
{
	$message = isset($array['data']['message']) ? $array['data']['message'] : null;
	$message = htmlspecialchars($message, ENT_QUOTES);
	$now = date("Y-m-d H:i:s");
	$currentIP = userIP();
	try {
		$connect = new Dibi\Connection([
			'driver' => 'sqlite3',
			'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
		]);
		$newMessage = [
			'username' => $GLOBALS['organizrUser']['username'],
			'gravatar' => $GLOBALS['organizrUser']['image'],
			'uid' => $GLOBALS['organizrUser']['uid'],
			'date' => $now,
			'ip' => $currentIP,
			'message' => $message
		];
		$connect->query('INSERT INTO [chatroom]', $newMessage);
		$options = array(
			'cluster' => $GLOBALS['CHAT-cluster-include'],
			'useTLS' => $GLOBALS['CHAT-useSSL']
		);
		$pusher = new Pusher\Pusher(
			$GLOBALS['CHAT-authKey-include'],
			$GLOBALS['CHAT-secret'],
			$GLOBALS['CHAT-appID-include'],
			$options
		);
		$pusher->trigger('org_channel', 'my-event', $newMessage);
		return true;
	} catch (Dibi\Exception $e) {
		return $e;
	}
}

function getChatMessage()
{
	try {
		$connect = new Dibi\Connection([
			'driver' => 'sqlite3',
			'database' => $GLOBALS['dbLocation'] . $GLOBALS['dbName'],
		]);
		$all = $connect->fetchAll('SELECT `username`, `gravatar`, `uid`, `date`, `message` FROM chatroom LIMIT ' . $GLOBALS['CHAT-messageLoadLimit']);
		return $all;
	} catch (Dibi\Exception $e) {
		return false;
	}
	
}