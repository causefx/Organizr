<?php
// PLUGIN INFORMATION
$GLOBALS['plugins']['ShuckStop'] = array( // Plugin Name
	'name' => 'ShuckStop', // Plugin Name
	'author' => 'CauseFX', // Who wrote the plugin
	'category' => 'Utilities', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal,business', // License Type use , for multiple
	'idPrefix' => 'SHUCKSTOP', // html element id prefix
	'configPrefix' => 'SHUCKSTOP', // config file prefix for array items without the hyphen
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'api/plugins/shuck-stop/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => true, // use default bind to make settings page - true or false
	'api' => 'api/v2/plugins/shuck-stop/settings', // api route for settings page
	'homepage' => false // Is plugin for use on homepage? true or false
);

class ShuckStop extends Organizr
{
	public function _shuckStopPluginGetSettings()
	{
		return [
			'Cron' => [
				$this->settingsOption('cron-file'),
				$this->settingsOption('blank'),
				$this->settingsOption('enable', 'SHUCKSTOP-cron-run-enabled'),
				$this->settingsOption('cron', 'SHUCKSTOP-cron-run-schedule')
			],
			'Email' => [
				$this->settingsOption('multiple', 'SHUCKSTOP-emails', ['label' => 'Emails']),
			],
			'Model' => [
				$this->settingsOption('switch', 'SHUCKSTOP-easystore', ['label' => 'Monitor EasyStore']),
				$this->settingsOption('switch', 'SHUCKSTOP-my-book', ['label' => 'Monitor My Book']),
				$this->settingsOption('switch', 'SHUCKSTOP-elements', ['label' => 'Monitor Elements']),
			],
			'Capacity' => [
				$this->settingsOption('switch', 'SHUCKSTOP-8', ['label' => 'Monitor 8TB']),
				$this->settingsOption('switch', 'SHUCKSTOP-10', ['label' => 'Monitor 10TB']),
				$this->settingsOption('switch', 'SHUCKSTOP-12', ['label' => 'Monitor 12TB']),
				$this->settingsOption('switch', 'SHUCKSTOP-14', ['label' => 'Monitor 14TB']),
				$this->settingsOption('switch', 'SHUCKSTOP-16', ['label' => 'Monitor 16TB']),
				$this->settingsOption('switch', 'SHUCKSTOP-18', ['label' => 'Monitor 18TB']),
				$this->settingsOption('switch', 'SHUCKSTOP-20', ['label' => 'Monitor 20TB']),
			]
		];
	}

	public function _shuckStopPluginRun()
	{
		if ($this->config['SHUCKSTOP-enabled'] && !empty($this->config['SHUCKSTOP-emails']) && $this->qualifyRequest(1)) {
			if (
				($this->config['SHUCKSTOP-easystore'] ||
					$this->config['SHUCKSTOP-my-book'] ||
					$this->config['SHUCKSTOP-elements']
				) &&
				($this->config['SHUCKSTOP-8'] ||
					$this->config['SHUCKSTOP-10'] ||
					$this->config['SHUCKSTOP-12'] ||
					$this->config['SHUCKSTOP-14'] ||
					$this->config['SHUCKSTOP-16'] ||
					$this->config['SHUCKSTOP-18'] ||
					$this->config['SHUCKSTOP-20']
				)
			) {
				$file = $this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'shuck-stop' . DIRECTORY_SEPARATOR . 'drives.json';
				$hasFile = file_exists($file);
				$json = null;
				if ($hasFile && filesize($file) > 0) {
					$jsonFile = file_get_contents($file);
					$json = json_decode($jsonFile, true);
				}
				$url = 'https://shucks.top/';
				$options = ($this->localURL($url)) ? array('verify' => false) : array();
				$response = Requests::get($url, array(), $options);
				if ($response->success) {
					$drives = [
						'run_date' => $this->currentTime,
						'has_file' => $hasFile,
						'email_setup' => $this->config['PHPMAILER-enabled'],
						'last_email_drives_lowest' => ($json) ? $json['last_email_drives_lowest'] : [],
						'last_email_drives_decent' => ($json) ? $json['last_email_drives_decent'] : [],
						'lowest_priced_drives' => [],
						'recent_decent_drives' => [],
					];
					$dom = new PHPHtmlParser\Dom;
					try {
						$dom->loadStr($response->body);
						$contents = $dom->find('tbody tr');
						foreach ($contents as $content) {
							//$html = $content->innerHtml;
							$capacity = $content->getAttribute('data-capacity');
							$model = str_replace(' ', '-', $content->find('td')[1]->text);
							$lastDecent = $content->find('td')[8]->text;
							$lowestDollars = $content->find('td')[7]->getAttribute('data-dollars');
							$lowestPerTB = $content->find('td')[7]->getAttribute('data-per-tb');
							$lowestNow = $content->find('td')[7]->find('p')[1]->text;
							$drives['drives'][$capacity][strtolower($model)]['capacity'] = $capacity;
							$drives['drives'][$capacity][strtolower($model)]['model'] = strtolower($model);
							$drives['drives'][$capacity][strtolower($model)]['last_decent'] = $lastDecent;
							$drives['drives'][$capacity][strtolower($model)]['lowest_dollars'] = $lowestDollars;
							$drives['drives'][$capacity][strtolower($model)]['lowest_decent_dollars'] = 100000;
							$drives['drives'][$capacity][strtolower($model)]['lowest_per_tb'] = $lowestPerTB;
							$drives['drives'][$capacity][strtolower($model)]['lowest_now'] = $lowestNow == 'now';
							$drives['drives'][$capacity][strtolower($model)]['decent_now'] = $lastDecent == 'now';

							$checkItems = [
								'amazon' => $this->_checkShuckClassNA('amazon', $content->find('td')[2]->getAttribute('class')),
								'bestbuy' => $this->_checkShuckClassNA('bestbuy', $content->find('td')[3]->getAttribute('class')),
								'bhphoto' => $this->_checkShuckClassNA('bhphoto', $content->find('td')[4]->getAttribute('class')),
								'ebay' => $this->_checkShuckClassNA('ebay', $content->find('td')[5]->getAttribute('class')),
								'newegg' => $this->_checkShuckClassNA('newegg', $content->find('td')[6]->getAttribute('class'))
							];
							$i = 2;
							foreach ($checkItems as $store => $class) {
								if ($class) {
									$driveInfo = $class;
								} else {
									$driveInfo = $this->_checkShuckStore($store, [
										'data-per-tb' => $content->find('td')[$i]->getAttribute('data-per-tb'),
										'data-dollars' => $content->find('td')[$i]->getAttribute('data-dollars'),
										'title' => $content->find('td')[$i]->getAttribute('title'),
										'link' => $content->find('td')[$i]->find('a')->getAttribute('href'),
										'lowest_dollars' => $lowestDollars,
										'lowest_now' => $lowestNow == 'now'
									]);
								}
								$i++;
								$drives['drives'][$capacity][strtolower($model)]['lowest_decent_dollars'] = ($driveInfo['data-dollars'] <= $drives['drives'][$capacity][strtolower($model)]['lowest_decent_dollars'] && $driveInfo['data-dollars'] !== 0 && $driveInfo['data-dollars'] !== null) ? $driveInfo['data-dollars'] : $drives['drives'][$capacity][strtolower($model)]['lowest_decent_dollars'];
								$drives['drives'][$capacity][strtolower($model)]['stores'][$store] = $driveInfo;
							}
							if ($drives['drives'][$capacity][strtolower($model)]['lowest_now']) {
								$drives['lowest_priced_drives'][$capacity][strtolower($model)] = $lowestDollars;
							}
							if ($drives['drives'][$capacity][strtolower($model)]['decent_now']) {
								$drives['recent_decent_drives'][$capacity][strtolower($model)] = $drives['drives'][$capacity][strtolower($model)]['lowest_decent_dollars'];
							}
						}
						// Run the checks...
						$capacities = [8, 10, 12, 14, 16, 18, 20];
						$models = ['easystore', 'elements', 'my-book'];
						$emailBody = '';
						foreach ($capacities as $capacity) {
							if ($this->config['SHUCKSTOP-' . $capacity]) {
								foreach ($models as $model) {
									if ($this->config['SHUCKSTOP-' . $model]) {
										if (isset($drives['lowest_priced_drives'][$capacity][$model]) &&
											(!$json ||
												(isset($json['lowest_priced_drives'][$capacity][$model]) &&
													$drives['lowest_priced_drives'][$capacity][$model] !== $json['last_email_drives_lowest'][$capacity][$model])
											)
										) {
											$emailBody .= '<br/>The ' . $capacity . 'TB drive is at the lowest price of $' . $drives['lowest_priced_drives'][$capacity][$model];
											foreach ($drives['drives'][$capacity][$model]['stores'] as $store => $storeInfo) {
												if ($storeInfo['data-dollars'] == $drives['lowest_priced_drives'][$capacity][$model]) {
													if ($storeInfo['link'] !== '') {
														$emailBody .= '<br/><a href="' . $storeInfo['link'] . '">' . $store . '</a>';
													}
												}
											}
											$drives['last_email_drives_lowest'][$capacity][$model] = $drives['lowest_priced_drives'][$capacity][$model];
										}
										if (isset($drives['recent_decent_drives'][$capacity][$model]) &&
											(!$json ||
												(isset($json['recent_decent_drives'][$capacity][$model]) &&
													$drives['recent_decent_drives'][$capacity][$model] !== $json['last_email_drives_decent'][$capacity][$model])
											)
										) {
											$emailBody .= '<br/>The ' . $capacity . 'TB drive has dropped to the price of $' . $drives['recent_decent_drives'][$capacity][$model];
											foreach ($drives['drives'][$capacity][$model]['stores'] as $store => $storeInfo) {
												if ($storeInfo['data-dollars'] == $drives['recent_decent_drives'][$capacity][$model]) {
													if ($storeInfo['link'] !== '') {
														$emailBody .= '<br/><a href="' . $storeInfo['link'] . '">' . $store . '</a>';
													}
												}
											}
											$drives['last_email_drives_decent'][$capacity][$model] = $drives['recent_decent_drives'][$capacity][$model];
										}
									}
								}
							}
						}
						// Send email if setup
						if ($this->config['PHPMAILER-enabled'] && $emailBody !== '') {
							$PhpMailer = new PhpMailer();
							$emailTemplate = [
								'type' => 'shuckstop',
								'body' => $emailBody,
								'subject' => 'New Shuck Drive Alert!',
								'user' => null,
								'password' => null,
								'inviteCode' => null,
							];
							$emailTemplate = $PhpMailer->_phpMailerPluginEmailTemplate($emailTemplate);
							$sendEmail = array(
								'to' => $this->config['SHUCKSTOP-emails'],
								'subject' => $emailTemplate['subject'],
								'body' => $PhpMailer->_phpMailerPluginBuildEmail($emailTemplate),
							);
							$PhpMailer->_phpMailerPluginSendEmail($sendEmail);
						}
						// Write file
						file_put_contents($file, safe_json_encode($drives, JSON_HEX_QUOT | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
						$this->setResponse(200, null, $drives);
						return $drives;
					} catch (\PHPHtmlParser\Exceptions\ChildNotFoundException|\PHPHtmlParser\Exceptions\CircularException|\PHPHtmlParser\Exceptions\LogicalException|\PHPHtmlParser\Exceptions\StrictException|\PHPHtmlParser\Exceptions\ContentLengthException|\PHPHtmlParser\Exceptions\NotLoadedException $e) {
						$this->setResponse(500, 'Error connecting to ShuckStop');
						return false;
					}
				}
				$this->setResponse(500, 'Error connecting to ShuckStop');
			} else {
				$this->setResponse(409, 'No Drives are monitored');
			}
		} else {
			$this->setResponse(401, 'User does not have access or user email not setup');
		}
		return false;
	}

	public function _checkShuckStore($store, $info)
	{
		return [
			'store' => $store,
			'data-per-tb' => $info['data-per-tb'],
			'data-dollars' => $info['data-dollars'],
			'title' => $info['title'],
			'link' => $info['link'],
			'lowest_now' => $info['lowest_now'] && $info['data-dollars'] == $info['lowest_dollars']
		];
	}

	public function _checkShuckClassNA($store, $class)
	{
		if ($class == 'n-a') {
			return [
				'store' => $store,
				'data-per-tb' => 0,
				'data-dollars' => 0,
				'title' => '',
				'link' => '',
				'lowest_now' => false
			];
		} else {
			return false;
		}
	}
}