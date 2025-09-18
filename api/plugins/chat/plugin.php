<?php
// PLUGIN INFORMATION
use Pusher\PusherException;

$GLOBALS['plugins']['Chat'] = array( // Plugin Name
	'name' => 'Chat', // Plugin Name
	'author' => 'CauseFX', // Who wrote the plugin
	'category' => 'Utilities', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal,business', // License Type use , for multiple
	'idPrefix' => 'CHAT', // html element id prefix
	'configPrefix' => 'CHAT', // config file prefix for array items without the hyphen
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'api/plugins/chat/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => true, // use default bind to make settings page - true or false
	'api' => 'api/v2/plugins/chat/settings', // api route for settings page
	'homepage' => false // Is plugin for use on homepage? true or false
);

class Chat extends Organizr
{
	public function _chatPluginGetSettings()
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
										<li><i class="fa fa-chevron-right text-danger"></i> <a href="https://dashboard.pusher.com/accounts/sign_up" target="_blank"><span lang="en">Signup for Pusher [FREE]</span></a></li>
										<li><i class="fa fa-chevron-right text-danger"></i> <span lang="en">Create an App called whatever you like and choose a cluster (Close to you)</span></li>
										<li><i class="fa fa-chevron-right text-danger"></i> <span lang="en">Frontend (JQuery) - Backend (PHP)</span></li>
										<li><i class="fa fa-chevron-right text-danger"></i> <span lang="en">Click the App Keys tab on top left</span></li>
										<li><i class="fa fa-chevron-right text-danger"></i> <span lang="en">Copy and paste the 4 values into Organizr</span></li>
										<li><i class="fa fa-chevron-right text-danger"></i> <span lang="en">Save and reload!</span></li>
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
					'value' => $this->config['CHAT-Auth-include'],
					'options' => $this->groupSelect()
				),
				array(
					'type' => 'number',
					'name' => 'CHAT-messageLoadLimit',
					'label' => '# of Previous Messages',
					'value' => $this->config['CHAT-messageLoadLimit'],
					'placeholder' => ''
				),
				array(
					'type' => 'select',
					'name' => 'CHAT-userRefreshTimeout',
					'label' => 'Refresh Seconds',
					'value' => $this->config['CHAT-userRefreshTimeout'],
					'options' => $this->timeOptions()
				),
				array(
					'type' => 'select',
					'name' => 'CHAT-newMessageSound-include',
					'label' => 'Message Sound',
					'value' => $this->config['CHAT-newMessageSound-include'],
					'options' => $this->getSounds()
				),
				array(
					'type' => 'switch',
					'name' => 'CHAT-useSSL',
					'label' => 'Use Pusher SSL',
					'help' => 'If messages get stuck sending, please turn this option off.',
					'value' => $this->config['CHAT-useSSL']
				)
			),
			'Connection' => array(
				array(
					'type' => 'password-alt',
					'name' => 'CHAT-authKey-include',
					'label' => 'Auth Key',
					'value' => $this->config['CHAT-authKey-include']
				),
				array(
					'type' => 'password-alt',
					'name' => 'CHAT-secret',
					'label' => 'API Secret',
					'value' => $this->config['CHAT-secret']
				),
				array(
					'type' => 'input',
					'name' => 'CHAT-appID-include',
					'label' => 'App ID',
					'value' => $this->config['CHAT-appID-include']
				),
				array(
					'type' => 'input',
					'name' => 'CHAT-cluster-include',
					'label' => 'App Cluster',
					'value' => $this->config['CHAT-cluster-include']
				),
			)
		);
	}

	public function _chatPluginSendChatMessage($array)
	{
		$message = isset($array['message']) ? $array['message'] : null;
		if (!$message) {
			$this->setAPIResponse('error', 'No message supplied', 409);
			return false;
		}
		$message = htmlspecialchars($message, ENT_QUOTES);
		$now = date("Y-m-d H:i:s");
		$currentIP = $this->userIP();
		$newMessage = [
			'username' => $this->user['username'],
			'gravatar' => $this->user['image'],
			'uid' => $this->user['uid'],
			'date' => $now,
			'ip' => $currentIP,
			'message' => $message
		];
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [chatroom]',
					$newMessage
				)
			),
		];
		$query = $this->processQueries($response);
		if ($query) {
			$options = array(
				'cluster' => $this->config['CHAT-cluster-include'],
				'useTLS' => $this->config['CHAT-useSSL']
			);
			try {
				$pusher = new Pusher\Pusher(
					$this->config['CHAT-authKey-include'],
					$this->config['CHAT-secret'],
					$this->config['CHAT-appID-include'],
					$options
				);
				$pusher->trigger('org_channel', 'my-event', $newMessage);
				$this->setAPIResponse('success', 'Chat message accepted', 200);
				return true;
			} catch (PusherException $e) {
				$this->setAPIResponse('error', 'Chat message error', 500);
			}
		}
		$this->setAPIResponse('error', 'Chat error occurred', 409);
		return false;
	}

	public function _chatPluginGetChatMessages()
	{
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => array(
					'SELECT `username`, `gravatar`, `uid`, `date`, `message` FROM chatroom ORDER BY date DESC LIMIT ?',
					(int)$this->config['CHAT-messageLoadLimit']
				)
			),
		];
		return $this->processQueries($response);
	}
}
