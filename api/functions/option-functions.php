<?php

trait OptionsFunction
{
	public function settingsOptionGroup($options = [])
	{
		$settings = [];
		foreach ($options as $option) {
			$optionType = $option[0] ? $option[0] : false;
			$optionName = $option[1] ? $option[1] : null;
			$optionExtras = $option[2] ? $option[2] : [];
			$setting = $this->settingsOption($optionType, $optionName, $optionExtras);
			array_push($settings, $setting);
		}
		return $settings;
	}

	public function settingsOption($type, $name = null, $extras = null)
	{
		$type = strtolower(str_replace('-', '', $type));
		$setting = [
			'name' => $name,
			'value' => $this->config[$name] ?? ''
		];
		switch ($type) {
			case 'orguser':
				$this->setUserOptionsVariable();
				$settingMerge = [
					'type' => 'select',
					'label' => 'Organizr User',
					'options' => $this->userOptions
				];
				break;
			case 'enable':
				$settingMerge = [
					'type' => 'switch',
					'label' => 'Enable',
				];
				break;
			case 'auth':
				$this->setGroupOptionsVariable();
				$settingMerge = [
					'type' => 'select',
					'label' => 'Minimum Authentication',
					'options' => $this->groupOptions
				];
				break;
			case 'refresh':
				$settingMerge = [
					'type' => 'select',
					'label' => 'Refresh Seconds',
					'options' => $this->timeOptions()
				];
				break;
			case 'combine':
			case 'combine-downloader':
				$settingMerge = [
					'type' => 'switch',
					'label' => 'Add to Combined Downloader',
				];
				break;
			case 'test':
				$settingMerge = [
					'type' => 'button',
					'label' => 'Test Connection',
					'icon' => 'fa fa-flask',
					'class' => 'pull-right',
					'text' => 'Test Connection',
					'attr' => 'onclick="testAPIConnection(\'' . $name . '\')"',
					'help' => 'Remember! Please save before using the test button!'
				];
				break;
			case 'url':
				$settingMerge = [
					'type' => 'input',
					'label' => 'URL',
					'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
					'placeholder' => 'http(s)://hostname:port'
				];
				break;
			case 'multipleurl':
				$settingMerge = [
					'type' => 'select2',
					'class' => 'select2-multiple',
					'id' => $name . '-select-' . $this->random_ascii_string(6),
					'label' => 'Multiple URL\'s',
					'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
					'placeholder' => 'http(s)://hostname:port',
					'options' => $this->makeOptionsFromValues($this->config[$name]),
					'settings' => '{tags: true, selectOnClose: true, closeOnSelect: true, allowClear: true}',
				];
				break;
			case 'multiple':
				$settingMerge = [
					'type' => 'select2',
					'class' => 'select2-multiple',
					'id' => $name . '-select-' . $this->random_ascii_string(6),
					'label' => 'Multiple Values\'s',
					'options' => $this->makeOptionsFromValues($this->config[$name]),
					'settings' => '{tags: true, selectOnClose: true, closeOnSelect: true, allowClear: true}',
				];
				break;
			case 'cron':
				$settingMerge = [
					'type' => 'cron',
					'label' => 'Cron Schedule',
					'help' => 'You may use either Cron format or - @hourly, @daily, @monthly',
					'placeholder' => '* * * * *'
				];
				break;
			case 'folder':
				$settingMerge = [
					'type' => 'folder',
					'label' => 'Save Path',
					'help' => 'Folder path',
					'placeholder' => '/path/to/folder'
				];
				break;
			case 'cronfile':
				$path = $this->root . DIRECTORY_SEPARATOR . 'cron.php';
				$server = $this->serverIP();
				$installInstruction = ($this->docker) ?
					'<p lang="en">No action needed.  Organizr\'s docker image comes with the Cron job built-in</p>' :
					'<p lang="en">Setup a Cron job so it\'s call will originate from either the server\'s IP address or a local IP address.  Please use the following information to set up the Cron Job correctly.</p>
					<h5>Cron Information</h5>
					<ul class="list-icons">
						<li><i class="fa fa-caret-right text-info"></i> <b lang="en">Schedule</b> <small>* * * * *</small></li>
						<li><i class="fa fa-caret-right text-info"></i> <b lang="en">File Path</b> <small>' . $path . '</small></li>
					</ul>
					<h5>Command Examples</h5>
					<ul class="list-icons">
						<li><i class="ti-angle-right"></i> * * * * * /path/to/php ' . $path . '</li>
						<li><i class="ti-angle-right"></i> * * * * * curl -XGET -sL  "http://' . $server . '/cron.php"</li>
					</ul>
					';
				$settingMerge = [
					'type' => 'html',
					'override' => 12,
					'label' => '',
					'html' => '
						<div class="row">
							<div class="col-lg-12">
								<div class="panel panel-info">
									<div class="panel-heading">
										<span lang="en">Organizr Enable Cron Instructions</span>
									</div>
									<div class="panel-wrapper collapse in" aria-expanded="true">
										<div class="panel-body">
											<h3 lang="en">Instructions for your install type</h3>
											<span>' . $installInstruction . '</span>
											<button type="button" onclick="checkCronFile();" class="btn btn-outline btn-info btn-lg btn-block" lang="en">Check Cron Status</button>
											<div class="m-t-15 hidden cron-results-container">
												<div class="well">
													<pre class="cron-results"></pre>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						'
				];
				break;
			case 'username':
				$settingMerge = [
					'type' => 'input',
					'label' => 'Username',
				];
				break;
			case 'password':
				$settingMerge = [
					'type' => 'password',
					'label' => 'Password',
				];
				break;
			case 'passwordalt':
				$settingMerge = [
					'type' => 'password-alt',
					'label' => 'Password',
				];
				break;
			case 'passwordaltcopy':
				$settingMerge = [
					'type' => 'password-alt-copy',
					'label' => 'Password',
				];
				break;
			case 'apikey':
			case 'token':
				$settingMerge = [
					'type' => 'password-alt',
					'label' => 'API Key/Token',
				];
				break;
			case 'multipleapikey':
			case 'multipletoken':
				$settingMerge = [
					'type' => 'select2',
					'class' => 'select2-multiple',
					'id' => $name . '-select-' . $this->random_ascii_string(6),
					'label' => 'Multiple API Key/Token\'s',
					'options' => $this->makeOptionsFromValues($this->config[$name]),
					'settings' => '{tags: true, theme: "default", selectionCssClass: "password-alt", selectOnClose: true, closeOnSelect: true, allowClear: true}',
				];
				break;
			case 'notice':
				$settingMerge = [
					'type' => 'html',
					'override' => 12,
					'label' => '',
					'html' => '
						<div class="row">
							<div class="col-lg-12">
								<div class="panel panel-' . ($extras['notice'] ?? 'info') . '">
									<div class="panel-heading">
										<span lang="en">' . ($extras['title'] ?? 'Attention') . '</span>
									</div>
									<div class="panel-wrapper collapse in" aria-expanded="true">
										<div class="panel-body">
											<span lang="en">' . ($extras['body'] ?? '') . '</span>
											<span>' . ($extras['bodyHTML'] ?? '') . '</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						'
				];
				break;
			case 'socks':
				$settingMerge = [
					'type' => 'html',
					'override' => 12,
					'label' => '',
					'html' => '
						<div class="panel panel-default">
							<div class="panel-wrapper collapse in">
								<div class="panel-body">' . $this->socksHeadingHTML($name) . '</div>
							</div>
						</div>'
				];
				break;
			case 'about':
				$settingMerge = [
					'type' => 'html',
					'override' => 12,
					'label' => '',
					'html' => '
						<div class="panel panel-default">
							<div class="panel-wrapper collapse in">
								<div class="panel-body">
									<h3 lang="en">' . ucwords($name) . ' Homepage Item</h3>
									<p lang="en">' . $extras["about"] . '</p>
								</div>
							</div>
						</div>'
				];
				break;
			case 'title':
				$settingMerge = [
					'type' => 'input',
					'label' => 'Title',
					'help' => 'Sets the title of this homepage module',
				];
				break;
			case 'toggletitle':
				$settingMerge = [
					'type' => 'switch',
					'label' => 'Toggle Title',
					'help' => 'Shows/hides the title of this homepage module'
				];
				break;
			case 'disablecertcheck':
				$settingMerge = [
					'type' => 'switch',
					'label' => 'Disable Certificate Check',
				];
				break;
			case 'usecustomcertificate':
				$settingMerge = [
					'type' => 'switch',
					'label' => 'Use Custom Certificate',
				];
				break;
			case 'hideseeding':
				$settingMerge = [
					'type' => 'switch',
					'label' => 'Hide Seeding',
				];
				break;
			case 'hidecompleted':
				$settingMerge = [
					'type' => 'switch',
					'label' => 'Hide Completed',
				];
				break;
			case 'hidestatus':
				$settingMerge = [
					'type' => 'switch',
					'label' => 'Hide Status',
				];
				break;
			case 'limit':
				$settingMerge = [
					'type' => 'number',
					'label' => 'Item Limit',
				];
				break;
			case 'mediasearchserver':
				$settingMerge = [
					'type' => 'select',
					'label' => 'Media Search Server',
					'options' => $this->mediaServerOptions()
				];
				break;
			case 'imagecachequality':
				$settingMerge = [
					'type' => 'select',
					'label' => 'Image Cache Quality',
					'options' => [
						[
							'name' => 'Low',
							'value' => '.5'
						],
						[
							'name' => '1x',
							'value' => '1'
						],
						[
							'name' => '2x',
							'value' => '2'
						],
						[
							'name' => '3x',
							'value' => '3'
						]
					]
				];
				break;
			case 'blank':
				$settingMerge = [
					'type' => 'blank',
					'label' => '',
				];
				break;
			case 'plexlibraryexclude':
				$settingMerge = [
					'type' => 'select2',
					'class' => 'select2-multiple',
					'id' => $name . '-exclude-select-' . $this->random_ascii_string(6),
					'label' => 'Libraries to Exclude',
					'options' => $extras['options']
				];
				break;
			case 'plexlibraryinclude':
				$settingMerge = [
					'type' => 'select2',
					'class' => 'select2-multiple',
					'id' => $name . '-include-select-' . $this->random_ascii_string(6),
					'label' => 'Libraries to Include',
					'options' => $extras['options']
				];
				break;
			// HTML ITEMS
			// precodeeditor possibly not needed anymore
			case 'precodeeditor':
				$settingMerge = [
					'type' => 'textbox',
					'class' => 'hidden ' . $name . 'Textarea',
					'label' => '',
				];
				break;
			case 'codeeditor':
				$mode = strtolower($extras['mode'] ?? 'css');
				switch ($mode) {
					case 'html':
					case 'javascript':
						$mode = 'ace/mode/' . $mode;
						break;
					case 'js':
						$mode = 'ace/mode/javascript';
						break;
					default:
						$mode = 'ace/mode/css';
						break;
				}
				$settingMerge = [
					'type' => 'html',
					'override' => 12,
					'label' => 'Custom Code',
					'html' => '
					<textarea data-changed="false" class="form-control hidden ' . $name . 'Textarea" name="' . $name . '" data-type="textbox" autocomplete="new-password">' . $this->config[$name] . '</textarea>
					<div id="' . $name . 'Editor" style="height:300px">' . htmlentities($this->config[$name]) . '</div>
					<script>
						let mode = ace.require("' . $mode . '").Mode;
						' . str_replace('-', '', $name) . ' = ace.edit("' . $name . 'Editor");
						' . str_replace('-', '', $name) . '.session.setMode(new mode());
						' . str_replace('-', '', $name) . '.setTheme("ace/theme/idle_fingers");
						' . str_replace('-', '', $name) . '.setShowPrintMargin(false);
						' . str_replace('-', '', $name) . '.session.on("change", function(delta) { 
							$(".' . $name . 'Textarea").val(' . str_replace('-', '', $name) . '.getValue());
							$(".' . $name . 'Textarea").trigger("change");
                        });
					</script>
					'
				];
				break;
			// CALENDAR ITEMS
			case 'calendarstart':
				$settingMerge = [
					'type' => 'number',
					'label' => '# of Days Before'
				];
				break;
			case 'calendarend':
				$settingMerge = [
					'type' => 'number',
					'label' => '# of Days After'
				];
				break;
			case 'calendarstartingday':
			case 'calendarstartday':
			case 'calendarstart':
				$settingMerge = [
					'type' => 'select',
					'label' => 'Start Day',
					'options' => $this->daysOptions()
				];
				break;
			case 'calendardefaultview':
				$settingMerge = [
					'type' => 'select',
					'label' => 'Default View',
					'options' => $this->calendarDefaultOptions()
				];
				break;
			case 'calendartimeformat':
				$settingMerge = [
					'type' => 'select',
					'label' => 'Time Format',
					'options' => $this->timeFormatOptions()
				];
				break;
			case 'calendarlocale':
				$settingMerge = [
					'type' => 'select',
					'label' => 'Locale',
					'options' => $this->calendarLocaleOptions()
				];
				break;
			case 'calendarlimit':
				$settingMerge = [
					'type' => 'select',
					'label' => 'Items Per Day',
					'options' => $this->limitOptions()
				];
				break;
			case 'color':
				$settingMerge = [
					'type' => 'input',
					'label' => 'Color',
					'class' => 'pick-a-color-custom-options',
					'attr' => 'data-original="' . $this->config[$name] . '"'
				];
				break;
			case 'calendarlinkurl':
				$settingMerge = [
					'type' => 'select-input',
					'label' => 'Target URL',
					'help' => 'Set the primary URL used when clicking on calendar icon.',
					'options' => $this->makeOptionsFromValues($this->config[str_replace('CalendarLink', '', $name) . 'URL'], true, 'Use Default'),
				];
				break;
			case 'calendarframetarget':
				$settingMerge = [
					'type' => 'select',
					'label' => 'Target Tab',
					'help' => 'Set the tab used when clicking on calendar icon. If not set, link will open in new window.',
					'options' => $this->getIframeTabs($this->config[str_replace('FrameTarget', 'CalendarLink', $name)])
				];
				break;
			default:
				$settingMerge = [
					'type' => strtolower($type),
					'label' => ''
				];
				break;
		}
		$setting = array_merge($settingMerge, $setting);
		if ($extras) {
			if (gettype($extras) == 'array') {
				$setting = array_merge($setting, $extras);
			}
		}
		return $setting;
	}

	public function getIframeTabs($url = "")
	{
		if (!empty($url)) {
			$response = [
				array(
					'function' => 'fetchAll',
					'query' => array(
						"SELECT * FROM tabs WHERE `enabled`='1' AND `type`='1' AND `group_id`>=? AND (`url` = '" . $url . "' OR `url_local` = '" . $url . "') ORDER BY `order` ASC",
						$this->getUserLevel(),
					)
				)
			];
		} else {
			$response = [
				array(
					'function' => 'fetchAll',
					'query' => array(
						"SELECT * FROM tabs WHERE `enabled`='1' AND `type`='1' AND `group_id`>=? ORDER BY `order` ASC",
						$this->getUserLevel()
					)
				)
			];
		}
		$formattedValues[] = [
			'name' => 'Open in New Window',
			'value' => ''
		];
		foreach ($this->processQueries($response) as $result) {
			$formattedValues[] = [
				'name' => $result['name'],
				'value' => $result['id']
			];
		}
		return $formattedValues;
	}

	public function makeOptionsFromValues($values = null, $appendBlank = null, $blankLabel = null)
	{
		if ($appendBlank === true) {
			$formattedValues[] = [
				'name' => (!empty($blankLabel)) ? $blankLabel : 'Select option...',
				'value' => ''
			];
		} else {
			$formattedValues = [];
		}
		if (strpos($values, ',') !== false) {
			$explode = explode(',', $values);
			foreach ($explode as $item) {
				$formattedValues[] = [
					'name' => $item,
					'value' => $item
				];
			}
		} elseif ($values == '') {
			$formattedValues = '';
		} else {
			$formattedValues[] = [
				'name' => $values,
				'value' => $values
			];
		}
		return $formattedValues;
	}

	public function logLevels()
	{
		return [
			[
				'name' => 'Debug',
				'value' => 'DEBUG'
			],
			[
				'name' => 'Info',
				'value' => 'INFO'
			],
			[
				'name' => 'Notice',
				'value' => 'NOTICE'
			],
			[
				'name' => 'Warning',
				'value' => 'WARNING'
			],
			[
				'name' => 'Error',
				'value' => 'ERROR'
			],
			[
				'name' => 'Critical',
				'value' => 'CRITICAL'
			],
			[
				'name' => 'Alert',
				'value' => 'ALERT'
			],
			[
				'name' => 'Emergency',
				'value' => 'EMERGENCY'
			]
		];
	}

	public function sandboxOptions()
	{
		return [
			[
				'name' => 'Allow Presentation',
				'value' => 'allow-presentation'
			],
			[
				'name' => 'Allow Forms',
				'value' => 'allow-forms'
			],
			[
				'name' => 'Allow Same Origin',
				'value' => 'allow-same-origin'
			],
			[
				'name' => 'Allow Orientation Lock',
				'value' => 'allow-orientation-lock'
			],
			[
				'name' => 'Allow Pointer Lock',
				'value' => 'allow-pointer-lock'
			],
			[
				'name' => 'Allow Scripts',
				'value' => 'allow-scripts'
			],
			[
				'name' => 'Allow Popups',
				'value' => 'allow-popups'
			],
			[
				'name' => 'Allow Popups To Escape Sandbox',
				'value' => 'allow-popups-to-escape-sandbox'
			],
			[
				'name' => 'Allow Modals',
				'value' => 'allow-modals'
			],
			[
				'name' => 'Allow Top Navigation',
				'value' => 'allow-top-navigation'
			],
			[
				'name' => 'Allow Top Navigation By User Activation',
				'value' => 'allow-top-navigation-by-user-activation'
			],
			[
				'name' => 'Allow Downloads',
				'value' => 'allow-downloads'
			],
		];
	}

	public function iframeAllowOptions()
	{
		return [
			[
				'name' => 'Allow Clipboard Read',
				'value' => 'clipboard-read'
			],
			[
				'name' => 'Allow Clipboard Write',
				'value' => 'clipboard-write'
			],
			[
				'name' => 'Allow Camera',
				'value' => 'camera'
			],
			[
				'name' => 'Allow Microphone',
				'value' => 'microphone'
			],
			[
				'name' => 'Allow Speaker Selection',
				'value' => 'speaker-selection'
			],
			[
				'name' => 'Allow Encrypted Media',
				'value' => 'encrypted-media'
			],
			[
				'name' => 'Allow Web Share',
				'value' => 'web-share'
			],
			[
				'name' => 'Allow Capture the Screen',
				'value' => 'display-capture'
			],
			[
				'name' => 'Allow Screen Wake Lock',
				'value' => 'screen-wake-lock'
			],
			[
				'name' => 'Allow Geolocation',
				'value' => 'geolocation'
			],
			[
				'name' => 'Allow Autoplay Media',
				'value' => 'autoplay'
			],
			[
				'name' => 'Allow USB',
				'value' => 'usb'
			],
			[
				'name' => 'Allow MIDI',
				'value' => 'midi'
			],
			[
				'name' => 'Allow Fullscreen',
				'value' => 'fullscreen'
			],
			[
				'name' => 'Allow Payment',
				'value' => 'payment'
			],
			[
				'name' => 'Allow Picture-in-Picture',
				'value' => 'picture-in-picture'
			],
			[
				'name' => 'Allow Gamepad',
				'value' => 'gamepad'
			],
			[
				'name' => 'Allow WebXR Spatial Tracking (VR)',
				'value' => 'xr-spatial-tracking'
			],
			[
				'name' => 'Allow Accelerometer Sensor',
				'value' => 'accelerometer'
			],
			[
				'name' => 'Allow Gyroscope Sensor',
				'value' => 'gyroscope'
			],
			[
				'name' => 'Allow Magnetometer Sensor',
				'value' => 'magnetometer'
			],
			[
				'name' => 'Allow Ambient Light Sensor',
				'value' => 'ambient-light-sensor'
			],
			[
				'name' => 'Allow Battery Status',
				'value' => 'battery'
			],
			[
				'name' => 'Allow Sync XMLHttpRequest',
				'value' => 'sync-xhr'
			],
		];
	}

	public function calendarLocaleOptions()
	{
		return [
			[
				'name' => 'Arabic (Standard)',
				'value' => 'ar',
			],
			[
				'name' => 'Arabic (Morocco)',
				'value' => 'ar-ma',
			],
			[
				'name' => 'Arabic (Saudi Arabia)',
				'value' => 'ar-sa'
			],
			[
				'value' => 'ar-tn',
				'name' => 'Arabic (Tunisia)'
			],
			[
				'value' => 'bg',
				'name' => 'Bulgarian'
			],
			[
				'value' => 'ca',
				'name' => 'Catalan'
			],
			[
				'value' => 'cs',
				'name' => 'Czech'
			],
			[
				'value' => 'da',
				'name' => 'Danish'
			],
			[
				'value' => 'de',
				'name' => 'German (Standard)'
			],
			[
				'value' => 'de-at',
				'name' => 'German (Austria)'
			],
			[
				'value' => 'el',
				'name' => 'Greek'
			],
			[
				'value' => 'en',
				'name' => 'English'
			],
			[
				'value' => 'en-au',
				'name' => 'English (Australia)'
			],
			[
				'value' => 'en-ca',
				'name' => 'English (Canada)'
			],
			[
				'value' => 'en-gb',
				'name' => 'English (United Kingdom)'
			],
			[
				'value' => 'es',
				'name' => 'Spanish'
			],
			[
				'value' => 'fa',
				'name' => 'Farsi'
			],
			[
				'value' => 'fi',
				'name' => 'Finnish'
			],
			[
				'value' => 'fr',
				'name' => 'French (Standard)'
			],
			[
				'value' => 'fr-ca',
				'name' => 'French (Canada)'
			],
			[
				'value' => 'he',
				'name' => 'Hebrew'
			],
			[
				'value' => 'hi',
				'name' => 'Hindi'
			],
			[
				'value' => 'hr',
				'name' => 'Croatian'
			],
			[
				'value' => 'hu',
				'name' => 'Hungarian'
			],
			[
				'value' => 'id',
				'name' => 'Indonesian'
			],
			[
				'value' => 'is',
				'name' => 'Icelandic'
			],
			[
				'value' => 'it',
				'name' => 'Italian'
			],
			[
				'value' => 'ja',
				'name' => 'Japanese'
			],
			[
				'value' => 'ko',
				'name' => 'Korean'
			],
			[
				'value' => 'lt',
				'name' => 'Lithuanian'
			],
			[
				'value' => 'lv',
				'name' => 'Latvian'
			],
			[
				'value' => 'nb',
				'name' => 'Norwegian (Bokmal)'
			],
			[
				'value' => 'nl',
				'name' => 'Dutch (Standard)'
			],
			[
				'value' => 'pl',
				'name' => 'Polish'
			],
			[
				'value' => 'pt',
				'name' => 'Portuguese'
			],
			[
				'value' => 'pt-br',
				'name' => 'Portuguese (Brazil)'
			],
			[
				'value' => 'ro',
				'name' => 'Romanian'
			],
			[
				'value' => 'ru',
				'name' => 'Russian'
			],
			[
				'value' => 'sk',
				'name' => 'Slovak'
			],
			[
				'value' => 'sl',
				'name' => 'Slovenian'
			],
			[
				'value' => 'sr',
				'name' => 'Serbian'
			],
			[
				'value' => 'sv',
				'name' => 'Swedish'
			],
			[
				'value' => 'th',
				'name' => 'Thai'
			],
			[
				'value' => 'tr',
				'name' => 'Turkish'
			],
			[
				'value' => 'uk',
				'name' => 'Ukrainian'
			],
			[
				'value' => 'vi',
				'name' => 'Vietnamese'
			],
			[
				'value' => 'zh-cn',
				'name' => 'Chinese (PRC)'
			],
			[
				'value' => 'zh-tw',
				'name' => 'Chinese (Taiwan)'
			]
		];
	}

	public function daysOptions()
	{
		return array(
			array(
				'name' => 'Sunday',
				'value' => '0'
			),
			array(
				'name' => 'Monday',
				'value' => '1'
			),
			array(
				'name' => 'Tueday',
				'value' => '2'
			),
			array(
				'name' => 'Wednesday',
				'value' => '3'
			),
			array(
				'name' => 'Thursday',
				'value' => '4'
			),
			array(
				'name' => 'Friday',
				'value' => '5'
			),
			array(
				'name' => 'Saturday',
				'value' => '6'
			)
		);
	}

	public function mediaServerOptions()
	{
		return array(
			array(
				'name' => 'N/A',
				'value' => ''
			),
			array(
				'name' => 'Plex',
				'value' => 'plex'
			),
			array(
				'name' => 'Emby [Not Available]',
				'value' => 'emby'
			)
		);
	}

	public function requestTvOptions($includeUserOption = false)
	{
		$options = [
			[
				'name' => 'All Seasons',
				'value' => 'all'
			],
			[
				'name' => 'First Season Only',
				'value' => 'first'
			],
			[
				'name' => 'Last Season Only',
				'value' => 'last'
			],
		];
		$userOption = [
			'name' => 'Let User Select',
			'value' => 'user'
		];
		if ($includeUserOption) {
			array_push($options, $userOption);
		}
		return $options;
	}

	public function requestServiceOptions()
	{
		return [
			[
				'name' => 'Ombi',
				'value' => 'ombi'
			],
			[
				'name' => 'Overseerr',
				'value' => 'overseerr'
			]
		];
	}

	public function limitOptions()
	{
		return array(
			array(
				'name' => '1 Item',
				'value' => '1'
			),
			array(
				'name' => '2 Items',
				'value' => '2'
			),
			array(
				'name' => '3 Items',
				'value' => '3'
			),
			array(
				'name' => '4 Items',
				'value' => '4'
			),
			array(
				'name' => '5 Items',
				'value' => '5'
			),
			array(
				'name' => '6 Items',
				'value' => '6'
			),
			array(
				'name' => '7 Items',
				'value' => '7'
			),
			array(
				'name' => '8 Items',
				'value' => '8'
			),
			array(
				'name' => 'Unlimited',
				'value' => '1000'
			),
		);
	}

	public function notificationTypesOptions()
	{
		return array(
			array(
				'name' => 'Toastr',
				'value' => 'toastr'
			),
			array(
				'name' => 'Izi',
				'value' => 'izi'
			),
			array(
				'name' => 'Alertify',
				'value' => 'alertify'
			),
			array(
				'name' => 'Noty',
				'value' => 'noty'
			),
		);
	}

	public function notificationPositionsOptions()
	{
		return array(
			array(
				'name' => 'Bottom Right',
				'value' => 'br'
			),
			array(
				'name' => 'Bottom Left',
				'value' => 'bl'
			),
			array(
				'name' => 'Bottom Center',
				'value' => 'bc'
			),
			array(
				'name' => 'Top Right',
				'value' => 'tr'
			),
			array(
				'name' => 'Top Left',
				'value' => 'tl'
			),
			array(
				'name' => 'Top Center',
				'value' => 'tc'
			),
			array(
				'name' => 'Center',
				'value' => 'c'
			),
		);
	}

	public function timeOptions()
	{
		return array(
			array(
				'name' => '2.5',
				'value' => '2500'
			),
			array(
				'name' => '5',
				'value' => '5000'
			),
			array(
				'name' => '10',
				'value' => '10000'
			),
			array(
				'name' => '15',
				'value' => '15000'
			),
			array(
				'name' => '30',
				'value' => '30000'
			),
			array(
				'name' => '60 [1 Minute]',
				'value' => '60000'
			),
			array(
				'name' => '300 [5 Minutes]',
				'value' => '300000'
			),
			array(
				'name' => '600 [10 Minutes]',
				'value' => '600000'
			),
			array(
				'name' => '900 [15 Minutes]',
				'value' => '900000'
			),
			array(
				'name' => '1800 [30 Minutes]',
				'value' => '1800000'
			),
			array(
				'name' => '3600 [1 Hour]',
				'value' => '3600000'
			),
		);
	}

	public function netdataOptions()
	{
		return [
			[
				'name' => 'Disk Read',
				'value' => 'disk-read',
			],
			[
				'name' => 'Disk Write',
				'value' => 'disk-write',
			],
			[
				'name' => 'CPU',
				'value' => 'cpu'
			],
			[
				'name' => 'Network Inbound',
				'value' => 'net-in',
			],
			[
				'name' => 'Network Outbound',
				'value' => 'net-out',
			],
			[
				'name' => 'Used RAM',
				'value' => 'ram-used',
			],
			[
				'name' => 'Used Swap',
				'value' => 'swap-used',
			],
			[
				'name' => 'Disk space used',
				'value' => 'disk-used',
			],
			[
				'name' => 'Disk space available',
				'value' => 'disk-avail',
			],
			[
				'name' => 'Custom',
				'value' => 'custom',
			]
		];
	}

	public function netdataChartOptions()
	{
		return [
			[
				'name' => 'Easy Pie Chart',
				'value' => 'easypiechart',
			],
			[
				'name' => 'Gauge',
				'value' => 'gauge'
			]
		];
	}

	public function netdataColourOptions()
	{
		return [
			[
				'name' => 'Red',
				'value' => 'fe3912',
			],
			[
				'name' => 'Green',
				'value' => '46e302',
			],
			[
				'name' => 'Purple',
				'value' => 'CC22AA'
			],
			[
				'name' => 'Blue',
				'value' => '5054e6',
			],
			[
				'name' => 'Yellow',
				'value' => 'dddd00',
			],
			[
				'name' => 'Orange',
				'value' => 'd66300',
			]
		];
	}

	public function netdataSizeOptions()
	{
		return [
			[
				'name' => 'Large',
				'value' => 'lg',
			],
			[
				'name' => 'Medium',
				'value' => 'md',
			],
			[
				'name' => 'Small',
				'value' => 'sm'
			]
		];
	}

	public function timeFormatOptions()
	{
		return array(
			array(
				'name' => '6p',
				'value' => 'h(:mm)t'
			),
			array(
				'name' => '6:00p',
				'value' => 'h:mmt'
			),
			array(
				'name' => '6pm',
				'value' => 'h(:mm)a'
			),
			array(
				'name' => '6:00pm',
				'value' => 'h:mma'
			),
			array(
				'name' => '6:00',
				'value' => 'h:mm'
			),
			array(
				'name' => '18',
				'value' => 'H(:mm)'
			),
			array(
				'name' => '18:00',
				'value' => 'H:mm'
			)
		);
	}

	public function rTorrentSortOptions()
	{
		return array(
			array(
				'name' => 'Date Desc',
				'value' => 'dated'
			),
			array(
				'name' => 'Date Asc',
				'value' => 'datea'
			),
			array(
				'name' => 'Hash Desc',
				'value' => 'hashd'
			),
			array(
				'name' => 'Hash Asc',
				'value' => 'hasha'
			),
			array(
				'name' => 'Name Desc',
				'value' => 'named'
			),
			array(
				'name' => 'Name Asc',
				'value' => 'namea'
			),
			array(
				'name' => 'Size Desc',
				'value' => 'sized'
			),
			array(
				'name' => 'Size Asc',
				'value' => 'sizea'
			),
			array(
				'name' => 'Label Desc',
				'value' => 'labeld'
			),
			array(
				'name' => 'Label Asc',
				'value' => 'labela'
			),
			array(
				'name' => 'Status Desc',
				'value' => 'statusd'
			),
			array(
				'name' => 'Status Asc',
				'value' => 'statusa'
			),
		);
	}

	public function qBittorrentApiOptions()
	{
		return array(
			array(
				'name' => 'V1',
				'value' => '1'
			),
			array(
				'name' => 'V2',
				'value' => '2'
			),
		);
	}

	public function qBittorrentSortOptions()
	{
		return array(
			array(
				'name' => 'Hash',
				'value' => 'hash'
			),
			array(
				'name' => 'Name',
				'value' => 'name'
			),
			array(
				'name' => 'Size',
				'value' => 'size'
			),
			array(
				'name' => 'Progress',
				'value' => 'progress'
			),
			array(
				'name' => 'Download Speed',
				'value' => 'dlspeed'
			),
			array(
				'name' => 'Upload Speed',
				'value' => 'upspeed'
			),
			array(
				'name' => 'Priority',
				'value' => 'priority'
			),
			array(
				'name' => 'Number of Seeds',
				'value' => 'num_seeds'
			),
			array(
				'name' => 'Number of Seeds in Swarm',
				'value' => 'num_complete'
			),
			array(
				'name' => 'Number of Leechers',
				'value' => 'num_leechs'
			),
			array(
				'name' => 'Number of Leechers in Swarm',
				'value' => 'num_incomplete'
			),
			array(
				'name' => 'Ratio',
				'value' => 'ratio'
			),
			array(
				'name' => 'ETA',
				'value' => 'eta'
			),
			array(
				'name' => 'State',
				'value' => 'state'
			),
			array(
				'name' => 'Category',
				'value' => 'category'
			)
		);
	}

	public function calendarDefaultOptions()
	{
		return array(
			array(
				'name' => 'Month',
				'value' => 'month'
			),
			array(
				'name' => 'Day',
				'value' => 'basicDay'
			),
			array(
				'name' => 'Week',
				'value' => 'basicWeek'
			),
			array(
				'name' => 'List',
				'value' => 'list'
			)
		);
	}
}
