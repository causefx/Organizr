<?php

trait OrganizrFunctions
{
	public function docs($path): string
	{
		return 'https://organizr.gitbook.io/organizr/' . $path;
	}

	public function loadResources($files = [], $rootPath = '')
	{
		$scripts = '';
		if (count($files) > 0) {
			foreach ($files as $file) {
				if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'js') {
					$scripts .= $this->loadJavaResource($file, $rootPath);
				} elseif (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'css') {
					$scripts .= $this->loadStyleResource($file, $rootPath);
				}
			}
		}
		return $scripts;
	}

	public function loadJavaResource($file = '', $rootPath = '')
	{
		return ($file !== '') ? '<script src="' . $rootPath . $file . '?v=' . trim($this->fileHash) . '"></script>' . "\n" : '';
	}

	public function loadStyleResource($file = '', $rootPath = '')
	{
		return ($file !== '') ? '<link href="' . $rootPath . $file . '?v=' . trim($this->fileHash) . '" rel="stylesheet">' . "\n" : '';
	}

	public function loadDefaultJavascriptFiles()
	{
		$javaFiles = [
			'js/jquery-2.2.4.min.js',
			'bootstrap/dist/js/bootstrap.min.js',
			'plugins/bower_components/sidebar-nav/dist/sidebar-nav.min.js',
			'js/jquery.slimscroll.js',
			'plugins/bower_components/styleswitcher/jQuery.style.switcher.js',
			'plugins/bower_components/moment/moment.js',
			'plugins/bower_components/moment/moment-timezone.js',
			'plugins/bower_components/jquery-wizard-master/dist/jquery-wizard.min.js',
			'plugins/bower_components/jquery-wizard-master/libs/formvalidation/formValidation.min.js',
			'plugins/bower_components/jquery-wizard-master/libs/formvalidation/bootstrap.min.js',
			'js/bowser.min.js',
			'js/jasny-bootstrap.js'
		];
		$scripts = '';
		foreach ($javaFiles as $file) {
			$scripts .= '<script src="' . $file . '?v=' . trim($this->fileHash) . '"></script>' . "\n";
		}
		return $scripts;
	}

	public function loadJavascriptFile($file)
	{
		return '<script>loadJavascript("' . $file . '?v=' . trim($this->fileHash) . '");' . "</script>\n";
	}

	public function embyJoinAPI($array)
	{
		$username = ($array['username']) ?? null;
		$email = ($array['email']) ?? null;
		$password = ($array['password']) ?? null;
		if (!$username) {
			$this->setAPIResponse('error', 'Username not supplied', 422);
			return false;
		}
		if (!$email) {
			$this->setAPIResponse('error', 'Email not supplied', 422);
			return false;
		}
		if (!$password) {
			$this->setAPIResponse('error', 'Password not supplied', 422);
			return false;
		}
		return $this->embyJoin($username, $email, $password);
	}

	public function embyJoin($username, $email, $password)
	{
		try {
			#create user in emby.
			$headers = array(
				"Accept" => "application/json"
			);
			$data = array();
			$url = $this->config['embyURL'] . '/emby/Users/New?name=' . $username . '&api_key=' . $this->config['embyToken'];
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
			$url = $this->config['embyURL'] . '/emby/Users/AuthenticateByName';
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
			$url = $this->config['embyURL'] . '/emby/Users/' . $userID . '/Password';
			Requests::Post($url, $headers, json_encode($data), array());
			#update config
			$headers = array(
				"Accept" => "application/json",
				"Content-Type" => "application/json"
			);
			$url = $this->config['embyURL'] . '/emby/Users/' . $userID . '/Policy?api_key=' . $this->config['embyToken'];
			$response = Requests::Post($url, $headers, $this->getEmbyTemplateUserJson(), array());
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
				$url = $this->config['embyURL'] . '/emby/Users/' . $userID . '/Connect/Link';
				Requests::Post($url, $headers, json_encode($data), array());
			} catch (Requests_Exception $e) {
				$this->setLoggerChannel('Emby')->error($e);
				$this->setResponse(500, $e->getMessage());
				return false;
			}
			$this->setAPIResponse('success', 'User has joined Emby', 200);
			return true;
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('Emby')->error($e);
			$this->setResponse(500, $e->getMessage());
			return false;
		}
	}

	/*loads users from emby and returns a correctly formated policy for a new user.
	*/
	public function getEmbyTemplateUserJson()
	{
		$headers = array(
			"Accept" => "application/json"
		);
		$data = array();
		$url = $this->config['embyURL'] . '/emby/Users?api_key=' . $this->config['embyToken'];
		$response = Requests::Get($url, $headers, array());
		$response = $response->body;
		$response = json_decode($response, true);
		//$correct stores the template users object
		$correct = null;
		foreach ($response as $element) {
			if ($element['Name'] == $this->config['INVITES-EmbyTemplate']) {
				$correct = $element;
			}
		}
		if ($correct == null) {
			//return empty JSON if user incorrectly configured template
			return "{}";
		}
		//select policy section and remove possibly dangerous rows.
		$policy = $correct['Policy'];
		unset($policy['AuthenticationProviderId']);
		unset($policy['InvalidLoginAttemptCount']);
		unset($policy['DisablePremiumFeatures']);
		unset($policy['DisablePremiumFeatures']);
		return (json_encode($policy));
	}

	public function checkHostPrefix($s)
	{
		if (empty($s)) {
			return $s;
		}
		return (substr($s, -1, 1) == '\\') ? $s : $s . '\\';
	}

	public function approvedFileExtension($filename, $type = 'image')
	{
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if ($type == 'image') {
			switch ($ext) {
				case 'gif':
				case 'png':
				case 'jpeg':
				case 'jpg':
					return true;
				default:
					return false;
			}
		} elseif ($type == 'cert') {
			switch ($ext) {
				case 'pem':
					return true;
				default:
					return false;
			}
		}
	}

	public function approvedFileType($file, $type = 'image')
	{
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$ext = $finfo->file($file);
		if ($type == 'image') {
			switch ($ext) {
				case 'image/gif':
				case 'image/png':
				case 'image/jpeg':
				case 'image/pjpeg':
					return true;
				default:
					return false;
			}
		}
		return false;
	}

	public function getImages()
	{
		$allIconsPrep = array();
		$allIcons = array();
		$ignore = array(".", "..", "._.DS_Store", ".DS_Store", ".pydio_id", "index.html");
		$dirname = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'tabs' . DIRECTORY_SEPARATOR;
		$path = 'plugins/images/tabs/';
		$images = scandir($dirname);
		foreach ($images as $image) {
			if (!in_array($image, $ignore)) {
				$allIconsPrep[$image] = array(
					'path' => $path,
					'name' => $image
				);
			}
		}
		$dirname = $this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'userTabs' . DIRECTORY_SEPARATOR;
		$path = 'data/userTabs/';
		$images = scandir($dirname);
		foreach ($images as $image) {
			if (!in_array($image, $ignore)) {
				$allIconsPrep[$image] = array(
					'path' => $path,
					'name' => $image
				);
			}
		}
		ksort($allIconsPrep);
		foreach ($allIconsPrep as $item) {
			$allIcons[] = $item['path'] . $item['name'];
		}
		return $allIcons;
	}

	public function imageSelect($form)
	{
		$i = 1;
		$images = $this->getImages();
		$return = '<select class="form-control tabIconImageList" id="' . $form . '-chooseImage" name="chooseImage"><option lang="en">Select or type Icon</option>';
		foreach ($images as $image) {
			$i++;
			$return .= '<option value="' . $image . '">' . basename($image) . '</option>';
		}
		return $return . '</select>';
	}

	public function getThemes()
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

	public function getSounds()
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

	public function getBranches()
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

	public function getSettingsTabs()
	{
		return array(
			array(
				'name' => 'Tab Editor',
				'value' => '0'
			),
			array(
				'name' => 'Customize',
				'value' => '1'
			),
			array(
				'name' => 'User Management',
				'value' => '2'
			),
			array(
				'name' => 'Image Manager',
				'value' => '3'
			),
			array(
				'name' => 'Plugins',
				'value' => '4'
			),
			array(
				'name' => 'System Settings',
				'value' => '5'
			)
		);
	}

	public function getAuthTypes()
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

	public function getLDAPOptions()
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
				'name' => 'Free IPA',
				'value' => '3'
			),
		);
	}

	public function getAuthBackends()
	{
		$backendOptions = array();
		$backendOptions[] = array(
			'name' => 'Choose Backend',
			'value' => false,
			'disabled' => true
		);
		foreach (array_filter(get_class_methods('Organizr'), function ($v) {
			return strpos($v, 'plugin_auth_') === 0;
		}) as $value) {
			$name = str_replace('plugin_auth_', '', $value);
			if ($name == 'ldap') {
				if (!function_exists('ldap_connect')) {
					continue;
				}
			}
			if ($name == 'ldap_disabled') {
				if (function_exists('ldap_connect')) {
					continue;
				}
			}
			if (strpos($name, 'disabled') === false) {
				$backendOptions[] = array(
					'name' => ucwords(str_replace('_', ' ', $name)),
					'value' => $name
				);
			} else {
				$backendOptions[] = array(
					'name' => $this->$value(),
					'value' => 'none',
					'disabled' => true,
				);
			}
		}
		ksort($backendOptions);
		return $backendOptions;
	}

	public function importUserButtons()
	{
		$emptyButtons = '
		<div class="col-md-12">
            <div class="white-box bg-org">
                <h3 class="box-title m-0" lang="en">Currently User import is available for Plex only.</h3> </div>
        </div>
	';
		$buttons = '';
		if (!empty($this->config['plexToken'])) {
			$buttons .= '<button class="btn m-b-20 m-r-20 bg-plex text-muted waves-effect waves-light importUsersButton" onclick="importUsers(\'plex\')" type="button"><span class="btn-label"><i class="mdi mdi-plex"></i></span><span lang="en">Import Plex Users</span></button>';
		}
		if (!empty($this->config['jellyfinURL']) && !empty($this->config['jellyfinToken'])) {
			$buttons .= '<button class="btn m-b-20 m-r-20 bg-primary text-muted waves-effect waves-light importUsersButton" onclick="importUsers(\'jellyfin\')" type="button"><span class="btn-label"><i class="mdi mdi-fish"></i></span><span lang="en">Import Jellyfin Users</span></button>';
		}
		if (!empty($this->config['embyURL']) && !empty($this->config['embyToken'])) {
			$buttons .= '<button class="btn m-b-20 m-r-20 bg-emby text-muted waves-effect waves-light importUsersButton" onclick="importUsers(\'emby\')" type="button"><span class="btn-label"><i class="mdi mdi-emby"></i></span><span lang="en">Import Emby Users</span></button>';
		}
		return ($buttons !== '') ? $buttons : $emptyButtons;
	}

	public function getHomepageMediaImage()
	{
		$refresh = false;
		$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
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
				$plexAddress = $this->qualifyURL($this->config['plexURL']);
				$image_src = $plexAddress . '/photo/:/transcode?height=' . $image_height . '&width=' . $image_width . '&upscale=1&url=' . $image_url . '&X-Plex-Token=' . $this->config['plexToken'];
				break;
			case 'emby':
				$embyAddress = $this->qualifyURL($this->config['embyURL']);
				$imgParams = array();
				if (isset($_GET['height'])) {
					$imgParams['height'] = 'maxHeight=' . $_GET['height'];
				}
				if (isset($_GET['width'])) {
					$imgParams['width'] = 'maxWidth=' . $_GET['width'];
				}
				$image_src = $embyAddress . '/Items/' . $image_url . '/Images/' . $itemType . '?' . implode('&', $imgParams);
				break;
			case 'jellyfin':
				$jellyfinAddress = $this->qualifyURL($this->config['jellyfinURL']);
				$imgParams = array();
				if (isset($_GET['height'])) {
					$imgParams['height'] = 'maxHeight=' . $_GET['height'];
				}
				if (isset($_GET['width'])) {
					$imgParams['width'] = 'maxWidth=' . $_GET['width'];
				}
				$image_src = $jellyfinAddress . '/Items/' . $image_url . '/Images/' . $itemType . '?' . implode('&', $imgParams);
				break;
			default:
				# code...
				break;
		}
		if (strpos($key, '-') !== false) {
			$noImage = 'no-' . explode('-', $key)[1] . '.png';
		} else {
			$noImage = 'no-np.png';
		}
		$noImage = $this->root . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'homepage' . DIRECTORY_SEPARATOR . $noImage;
		if (isset($image_url) && isset($image_height) && isset($image_width) && isset($image_src)) {
			$cachefile = $cacheDirectory . $key . '.jpg';
			$cachetime = 604800;
			// Serve from the cache if it is younger than $cachetime
			if (file_exists($cachefile) && (time() - $cachetime < filemtime($cachefile)) && $refresh == false) {
				header('Content-type: image/jpeg');
				if (filesize($cachefile) > 0) {
					@readfile($cachefile);
				} else {
					@readfile($noImage);
				}
				exit;
			}
			$options = array('verify' => false);
			$response = Requests::get($image_src, array(), $options);
			if ($response->success) {
				ob_start(); // Start the output buffer
				header('Content-type: image/jpeg');
				echo $response->body;
				// Cache the output to a file
				$fp = fopen($cachefile, 'wb');
				fwrite($fp, ob_get_contents());
				fclose($fp);
				ob_end_flush(); // Send the output to the browser
				die();
			} else {
				header('Content-type: image/jpeg');
				@readfile($noImage);
			}
		} else {
			header('Content-type: image/jpeg');
			@readfile($noImage);
		}
	}

	public function cacheImage($url, $name, $extension = 'jpg')
	{
		$cacheDirectory = $this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
		if (!file_exists($cacheDirectory)) {
			mkdir($cacheDirectory, 0777, true);
		}
		$cacheFile = $cacheDirectory . $name . '.' . $extension;
		$cacheTime = 604800;
		$ctx = stream_context_create(array(
			'http' => array(
				'timeout' => 5,
				'protocol_version' => 1.1,
				'header' => 'Connection: close'
			)
		));
		if ((file_exists($cacheFile) && (time() - $cacheTime) > filemtime($cacheFile)) || !file_exists($cacheFile)) {
			@copy($url, $cacheFile, $ctx);
		}
	}

	public function checkFrame($array, $url)
	{
		if (array_key_exists("x-frame-options", $array)) {
			if (gettype($array['x-frame-options']) == 'array') {
				$array['x-frame-options'] = $array['x-frame-options'][0];
			}
			$array['x-frame-options'] = strtolower($array['x-frame-options']);
			if ($array['x-frame-options'] == "deny") {
				return false;
			} elseif ($array['x-frame-options'] == "sameorgin") {
				$digest = parse_url($url);
				$host = ($digest['host'] ?? '');
				if ($this->getServer() == $host) {
					return true;
				} else {
					return false;
				}
			} elseif (strpos($array['x-frame-options'], 'allow-from') !== false) {
				$explodeServers = explode(' ', $array['x-frame-options']);
				$allowed = false;
				foreach ($explodeServers as $server) {
					$digest = parse_url($server);
					$host = ($digest['host'] ?? '');
					if ($this->getServer() == $host) {
						$allowed = true;
					}
				}
				return $allowed;
			} else {
				return false;
			}
		} else {
			if (!$array) {
				return false;
			}
			return true;
		}
	}

	public function frameTest($url)
	{
		if (!$url || $url == '') {
			$this->setAPIResponse('error', 'URL not supplied', 404);
			return false;
		}
		$array = array_change_key_case(get_headers($this->qualifyURL($url), 1));
		$url = $this->qualifyURL($url);
		if ($this->checkFrame($array, $url)) {
			$this->setAPIResponse('success', 'URL approved for iFrame', 200);
			return true;
		} else {
			$this->setAPIResponse('error', 'URL failed approval for iFrame', 409);
			return false;
		}
	}

	public function groupSelect()
	{
		$groups = $this->getAllGroups();
		$select = array();
		foreach ($groups as $key => $value) {
			$select[] = array(
				'name' => $value['group'],
				'value' => $value['group_id']
			);
		}
		return $select;
	}

	public function userSelect()
	{
		$users = $this->getAllUsers();
		$select = [];
		foreach ($users as $key => $value) {
			$select[] = array(
				'name' => $value['username'],
				'value' => $value['id']
			);
		}
		return $select;
	}

	public function showLogin()
	{
		if ($this->config['hideRegistration'] == false) {
			return '<p><span lang="en">Don\'t have an account?</span><a href="#" class="text-primary m-l-5 to-register"><b lang="en">Sign Up</b></a></p>';
		}
	}

	public function checkoAuth()
	{
		return $this->config['plexoAuth'] && $this->config['authBackend'] == 'plex' && $this->config['authType'] !== 'internal';
	}

	public function checkoAuthOnly()
	{
		return $this->config['plexoAuth'] && $this->config['authBackend'] == 'plex' && $this->config['authType'] == 'external';
	}

	public function showoAuth()
	{
		$buttons = '';
		if ($this->config['plexoAuth'] && $this->config['authBackend'] == 'plex' && $this->config['authType'] !== 'internal') {
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

	public function logoOrText()
	{
		$showLogo = $this->config['minimalLoginScreen'] ? '' : 'visible-xs';
		if ($this->config['useLogoLogin'] == false) {
			$html = '<h1>' . $this->config['title'] . '</h1>';
		} else {
			$html = '<img class="loginLogo" src="' . $this->config['loginLogo'] . '" alt="Home" />';
		}
		return '<a href="javascript:void(0)" class="text-center db ' . $showLogo . '" id="login-logo">' . $html . '</a>';
	}

	public function settingsDocker()
	{
		$type = ($this->docker) ? 'Official Docker' : 'Native';
		return '<li><div class="bg-info"><i class="mdi mdi-flag mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">Install Type</span> ' . $type . '</li>';
	}

	public function settingsPathChecks()
	{
		$paths = $this->pathsWritable($this->paths);
		$items = '';
		$type = (array_search(false, $paths)) ? 'Not Writable' : 'Writable';
		$result = '<li class="mouse" onclick="toggleWritableFolders();"><div class="bg-info"><i class="mdi mdi-folder mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">Organizr Paths</span> ' . $type . '</li>';
		foreach ($paths as $k => $v) {
			$items .= '<li class="folders-writable hidden"><div class="bg-primary"><i class="mdi mdi-folder mdi-24px text-white"></i></div><a tabindex="0" type="button" class="btn btn-default btn-outline popover-info pull-right clipboard" lang="en" data-container="body" title="" data-toggle="popover" data-placement="left" data-content="' . $v['path'] . '" data-original-title="File Path" data-clipboard-text="' . $v['path'] . '">' . $k . '</a> ' . (($v['writable']) ? 'Writable' : 'Not Writable') . '</li>';
		}
		return $result . $items;
	}

	public function pathsWritable($paths)
	{
		$results = array();
		foreach ($paths as $k => $v) {
			$results[$k] = [
				'writable' => is_writable($v),
				'path' => $v
			];
		}
		return $results;
	}

	public function clearTautulliTokens()
	{
		foreach (array_keys($_COOKIE) as $k => $v) {
			if (strpos($v, 'tautulli') !== false) {
				$this->coookie('delete', $v);
			}
		}
	}

	public function clearJellyfinTokens()
	{
		foreach (array_keys($_COOKIE) as $k => $v) {
			if (strpos($v, 'user-') !== false) {
				$this->coookie('delete', $v);
			}
		}
		$this->coookie('delete', 'jellyfin_credentials');
	}

	public function clearKomgaToken()
	{
		if (isset($_COOKIE['komga_token'])) {
			try {
				$url = $this->qualifyURL($this->config['komgaURL']);
				$options = $this->requestOptions($url, 60000, true, false);
				$response = Requests::post($url . '/api/v1/users/logout', ['X-Auth-Token' => $_COOKIE['komga_token']], $options);
				if ($response->success) {
					$this->setLoggerChannel('Komga')->info('Logged User out');
				} else {
					$this->setLoggerChannel('Komga')->warning('Unable to Logged User out');
				}
			} catch (Requests_Exception $e) {
				$this->setLoggerChannel('Komga')->error($e);
			}
			$this->coookie('delete', 'komga_token');
		}
	}

	public function analyzeIP($ip)
	{
		if (strpos($ip, '/') !== false) {
			$explodeIP = explode('/', $ip);
			$prefix = $explodeIP[1];
			$start_ip = $explodeIP[0];
			$ip_count = 1 << (32 - $prefix);
			$start_ip_long = ip2long($start_ip);
			$last_ip_long = ip2long($start_ip) + $ip_count - 1;
		} elseif (substr_count($ip, '.') == 3) {
			$start_ip_long = ip2long($ip);
			$last_ip_long = ip2long($ip);
		}
		return (isset($start_ip_long) && isset($last_ip_long)) ? array('from' => $start_ip_long, 'to' => $last_ip_long) : false;
	}

	public function authProxyRangeCheck($from, $to)
	{
		$approved = false;
		$userIP = ip2long($_SERVER['REMOTE_ADDR']);
		$low = $from;
		$high = $to;
		if ($userIP <= $high && $low <= $userIP) {
			$approved = true;
		}
		$this->logger->debug('authProxy range check', ['server_ip' => ['long' => $userIP, 'short' => long2ip($userIP)], 'range_from' => ['long' => $from, 'short' => long2ip($from)], 'range_to' => ['long' => $to, 'short' => long2ip($to)], 'approved' => $approved]);
		return $approved;
	}

	public function userDefinedIdReplacementLink($link, $variables)
	{
		if (!isset($link) || $link == '') {
			return null;
		}
		return strtr($link, $variables);
	}

	public function requestOptions($url, $timeout = null, $override = false, $customCertificate = false, $extras = null)
	{
		$options = [];
		if (is_numeric($timeout)) {
			if ($timeout >= 1000) {
				$timeout = $timeout / 1000;
			}
			$options = array_merge($options, array('timeout' => $timeout));
		}
		if ($customCertificate) {
			if ($this->hasCustomCert()) {
				$options = array_merge($options, array('verify' => $this->getCustomCert(), 'verifyname' => false));
			}
		}
		if ($this->localURL($url, $override)) {
			$options = array_merge($options, array('verify' => false, 'verifyname' => false));
		}
		if ($extras) {
			if (gettype($extras) == 'array') {
				$options = array_merge($options, $extras);
			}
		}
		return array_merge($options, array('useragent' => 'organizr/' . $this->version, 'connect_timeout' => 5));
	}

	public function showHTML(string $title = 'Organizr Alert', string $notice = '', bool $autoClose = false)
	{
		$close = $autoClose ? 'onLoad="setTimeout(\'closemyself()\',3000);"' : '';
		$closeMessage = $autoClose ? '<p><sup>(This window will close automatically)</sup></p>' : '';
		return
			'<!DOCTYPE html>
			<html lang="en">
			<head>
				<style>' . file_get_contents($this->root . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'mvp.css') . '</style>
				<meta charset="utf-8">
				<meta name="description" content="' . $title . '">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title>' . $title . '</title>
			</head>
			<script language=javascript>
					function closemyself() {
						window.opener=self;
						window.close();
					}
			</script>
			<body ' . $close . '>
				<main>
					<section>
						<div>
							<h3>' . $title . '</h3>
							<p>' . $notice . '</p>
							' . $closeMessage . '
						</div>
					</section>
				</main>
			</body>
			</html>';
	}

	public function buildSettingsMenus($menuItems, $menuName)
	{
		$selectMenuItems = '';
		$unorderedListMenuItems = '';
		$menuNameLower = strtolower(str_replace(' ', '-', $menuName));
		foreach ($menuItems as $menuItem) {
			$anchorShort = str_replace('-anchor', '', $menuItem['anchor']);
			$active = ($menuItem['active']) ? 'active' : '';
			$apiPage = ($menuItem['api']) ? 'loadSettingsPage2(\'' . $menuItem['api'] . '\',\'#' . $anchorShort . '\',\'' . $menuItem['name'] . '\');' : '';
			$onClick = (isset($menuItem['onclick'])) ? $menuItem['onclick'] : '';
			$selectMenuItems .= '<option value="#' . $menuItem['anchor'] . '" lang="en">' . $menuItem['name'] . '</option>';
			$unorderedListMenuItems .= '
				<li onclick="changeSettingsMenu(\'Settings::' . $menuName . '::' . $menuItem['name'] . '\'); ' . $apiPage . $onClick . '" role="presentation" class="' . $active . '">
					<a id="' . $menuItem['anchor'] . '" href="#' . $anchorShort . '" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="true">
						<span lang="en">' . $menuItem['name'] . '</span>
					</a>
			</li>';
		}
		$selectMenu = '<select class="form-control settings-dropdown-box ' . $menuNameLower . '-menu w-100 visible-xs">' . $selectMenuItems . '</select>';
		$unorderedListMenu = '<ul class="nav customtab2 nav-tabs nav-non-mobile hidden-xs" data-dropdown="' . $menuNameLower . '-menu" role="tablist">' . $unorderedListMenuItems . '</ul>';
		return $selectMenu . $unorderedListMenu;
	}

	public function isJSON($string)
	{
		return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE);
	}

	public function isXML($string)
	{
		libxml_use_internal_errors(true);
		return (bool)simplexml_load_string($string);
	}

	public function testAndFormatString($string)
	{
		if ($this->isJSON($string)) {
			return ['type' => 'json', 'data' => json_decode($string, true)];
		} elseif ($this->isXML($string)) {
			libxml_use_internal_errors(true);
			return ['type' => 'xml', 'data' => simplexml_load_string($string)];
		} else {
			return ['type' => 'string', 'data' => $string];
		}
	}
}