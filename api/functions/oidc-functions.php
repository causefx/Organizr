<?php

trait OIDCFunctions
{
	/**
	 * Provider registry with provider-specific configurations
	 */
	private $oidcProviders = [
		'authentik' => [
			'configPrefix' => 'oidcAuthentik',
			'defaultGroupClaim' => 'groups',
			'supportsEndSession' => true,
		],
		'keycloak' => [
			'configPrefix' => 'oidcKeycloak',
			'defaultGroupClaim' => 'groups',
			'supportsEndSession' => true,
		],
		'pocketid' => [
			'configPrefix' => 'oidcPocketid',
			'defaultGroupClaim' => 'groups',
			'supportsEndSession' => false,
		],
		'zitadel' => [
			'configPrefix' => 'oidcZitadel',
			'defaultGroupClaim' => 'urn:zitadel:iam:org:project:roles',
			'supportsEndSession' => true,
			'rolesAsObject' => true,
		],
	];

	/**
	 * Get list of enabled OIDC providers
	 */
	public function getEnabledOIDCProviders()
	{
		$enabled = [];
		if (!$this->config['oidcEnabled']) {
			return $enabled;
		}
		foreach ($this->oidcProviders as $provider => $config) {
			$enabledKey = $config['configPrefix'] . 'Enabled';
			if ($this->config[$enabledKey] ?? false) {
				$enabled[$provider] = $config;
			}
		}
		return $enabled;
	}

	/**
	 * Get provider configuration
	 */
	public function getOIDCProviderConfig($provider)
	{
		if (!isset($this->oidcProviders[$provider])) {
			return null;
		}
		$config = $this->oidcProviders[$provider];
		$prefix = $config['configPrefix'];
		return [
			'provider' => $provider,
			'name' => $this->config[$prefix . 'Name'] ?? ucfirst($provider),
			'clientId' => $this->config[$prefix . 'ClientId'] ?? '',
			'clientSecret' => $this->decrypt($this->config[$prefix . 'ClientSecret'] ?? ''),
			'discoveryUrl' => $this->config[$prefix . 'DiscoveryUrl'] ?? '',
			'scopes' => $this->config[$prefix . 'Scopes'] ?? 'openid,profile,email',
			'groupClaim' => $this->config[$prefix . 'GroupClaim'] ?: ($config['defaultGroupClaim'] ?? 'groups'),
			'supportsEndSession' => $config['supportsEndSession'] ?? false,
			'rolesAsObject' => $config['rolesAsObject'] ?? false,
		];
	}

	/**
	 * Fetch and cache OIDC discovery document
	 */
	public function getOIDCDiscovery($provider)
	{
		$config = $this->getOIDCProviderConfig($provider);
		if (!$config || empty($config['discoveryUrl'])) {
			$this->setLoggerChannel('OIDC')->error('Discovery URL not configured for provider: ' . $provider);
			return null;
		}
		$cacheKey = 'oidc_discovery_' . $provider;
		$cached = $_SESSION[$cacheKey] ?? null;
		if ($cached && (time() - ($cached['fetched_at'] ?? 0)) < 3600) {
			return $cached['data'];
		}
		try {
			$response = Requests::get($config['discoveryUrl'], [], [
				'verify' => $this->getCert(),
				'timeout' => 10,
			]);
			if ($response->success) {
				$discovery = json_decode($response->body, true);
				$_SESSION[$cacheKey] = [
					'data' => $discovery,
					'fetched_at' => time(),
				];
				return $discovery;
			}
			$this->setLoggerChannel('OIDC')->error('Failed to fetch discovery document: ' . $response->status_code);
			return null;
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('OIDC')->error($e);
			return null;
		}
	}

	/**
	 * Generate PKCE code verifier and challenge
	 */
	public function generatePKCE()
	{
		$codeVerifier = bin2hex(random_bytes(32));
		$codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
		return [
			'code_verifier' => $codeVerifier,
			'code_challenge' => $codeChallenge,
			'code_challenge_method' => 'S256',
		];
	}

	/**
	 * Generate state parameter for CSRF protection
	 */
	public function generateOIDCState($provider)
	{
		$state = bin2hex(random_bytes(16));
		$_SESSION['oidc_state'] = $state;
		$_SESSION['oidc_state_provider'] = $provider;
		$_SESSION['oidc_state_time'] = time();
		return $state;
	}

	/**
	 * Validate state parameter
	 */
	public function validateOIDCState($state, $provider)
	{
		$storedState = $_SESSION['oidc_state'] ?? null;
		$storedProvider = $_SESSION['oidc_state_provider'] ?? null;
		$stateTime = $_SESSION['oidc_state_time'] ?? 0;
		// State expires after 10 minutes
		if (time() - $stateTime > 600) {
			$this->setLoggerChannel('OIDC')->warning('OIDC state expired');
			return false;
		}
		if (!hash_equals($storedState ?? '', $state) || $storedProvider !== $provider) {
			$this->setLoggerChannel('OIDC')->warning('OIDC state mismatch');
			return false;
		}
		// Clear used state
		unset($_SESSION['oidc_state'], $_SESSION['oidc_state_provider'], $_SESSION['oidc_state_time']);
		return true;
	}

	/**
	 * Generate authorization URL for OIDC provider
	 */
	public function getOIDCAuthorizationUrl($provider)
	{
		$config = $this->getOIDCProviderConfig($provider);
		if (!$config) {
			return null;
		}
		$discovery = $this->getOIDCDiscovery($provider);
		if (!$discovery || empty($discovery['authorization_endpoint'])) {
			$this->setLoggerChannel('OIDC')->error('Authorization endpoint not found in discovery');
			return null;
		}
		$pkce = $this->generatePKCE();
		$_SESSION['oidc_code_verifier'] = $pkce['code_verifier'];
		$state = $this->generateOIDCState($provider);
		$nonce = bin2hex(random_bytes(16));
		$_SESSION['oidc_nonce'] = $nonce;
		$scopes = str_replace(',', ' ', $config['scopes']);
		$params = [
			'response_type' => 'code',
			'client_id' => $config['clientId'],
			'redirect_uri' => $this->getServerPath() . 'api/v2/oidc/' . $provider . '/callback',
			'scope' => $scopes,
			'state' => $state,
			'nonce' => $nonce,
			'code_challenge' => $pkce['code_challenge'],
			'code_challenge_method' => $pkce['code_challenge_method'],
		];
		return $discovery['authorization_endpoint'] . '?' . http_build_query($params);
	}

	/**
	 * Exchange authorization code for tokens
	 */
	public function exchangeOIDCCode($provider, $code)
	{
		$config = $this->getOIDCProviderConfig($provider);
		if (!$config) {
			return null;
		}
		$discovery = $this->getOIDCDiscovery($provider);
		if (!$discovery || empty($discovery['token_endpoint'])) {
			$this->setLoggerChannel('OIDC')->error('Token endpoint not found in discovery');
			return null;
		}
		$codeVerifier = $_SESSION['oidc_code_verifier'] ?? '';
		unset($_SESSION['oidc_code_verifier']);
		$data = [
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $this->getServerPath() . 'api/v2/oidc/' . $provider . '/callback',
			'client_id' => $config['clientId'],
			'client_secret' => $config['clientSecret'],
			'code_verifier' => $codeVerifier,
		];
		try {
			$response = Requests::post($discovery['token_endpoint'], [
				'Content-Type' => 'application/x-www-form-urlencoded',
			], http_build_query($data), [
				'verify' => $this->getCert(),
				'timeout' => 10,
			]);
			if ($response->success) {
				$tokens = json_decode($response->body, true);
				$this->setLoggerChannel('OIDC')->debug('Token exchange successful for provider: ' . $provider);
				return $tokens;
			}
			$this->setLoggerChannel('OIDC')->error('Token exchange failed: ' . $response->body);
			return null;
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('OIDC')->error($e);
			return null;
		}
	}

	/**
	 * Get user info from OIDC provider
	 */
	public function getOIDCUserInfo($provider, $accessToken)
	{
		$discovery = $this->getOIDCDiscovery($provider);
		if (!$discovery || empty($discovery['userinfo_endpoint'])) {
			$this->setLoggerChannel('OIDC')->warning('Userinfo endpoint not found, using ID token claims');
			return null;
		}
		try {
			$response = Requests::get($discovery['userinfo_endpoint'], [
				'Authorization' => 'Bearer ' . $accessToken,
			], [
				'verify' => $this->getCert(),
				'timeout' => 10,
			]);
			if ($response->success) {
				return json_decode($response->body, true);
			}
			$this->setLoggerChannel('OIDC')->error('Userinfo request failed: ' . $response->status_code);
			return null;
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('OIDC')->error($e);
			return null;
		}
	}

	/**
	 * Decode JWT ID token (without verification - tokens already verified by provider)
	 */
	public function decodeOIDCIdToken($idToken)
	{
		$parts = explode('.', $idToken);
		if (count($parts) !== 3) {
			return null;
		}
		$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
		return $payload;
	}

	/**
	 * Extract groups from OIDC claims
	 */
	public function extractOIDCGroups($provider, $claims)
	{
		$config = $this->getOIDCProviderConfig($provider);
		$groupClaim = $config['groupClaim'] ?? $this->config['oidcGroupClaimName'];
		$groups = $claims[$groupClaim] ?? [];
		// Handle Zitadel's role format (object keys)
		if ($provider === 'zitadel' && ($config['rolesAsObject'] ?? false)) {
			if (is_array($groups) && !empty($groups) && !isset($groups[0])) {
				$groups = array_keys($groups);
			}
		}
		// Ensure array format
		if (is_string($groups)) {
			$groups = [$groups];
		}
		return $groups;
	}

	/**
	 * Map OIDC groups to Organizr group ID
	 */
	public function mapOIDCGroupToOrganizr($oidcGroups)
	{
		$mappings = json_decode($this->config['oidcGroupMappings'] ?? '{}', true) ?: [];
		$mode = $this->config['oidcGroupMappingMode'] ?? 'first';
		$matchedGroups = [];
		foreach ($oidcGroups as $oidcGroup) {
			// Direct match
			if (isset($mappings[$oidcGroup])) {
				$matchedGroups[] = (int)$mappings[$oidcGroup];
				continue;
			}
			// Try matching without leading slash (Keycloak nested groups)
			$stripped = ltrim($oidcGroup, '/');
			if (isset($mappings[$stripped])) {
				$matchedGroups[] = (int)$mappings[$stripped];
				continue;
			}
			// Try matching last segment only
			$segments = explode('/', $stripped);
			$lastSegment = end($segments);
			if (isset($mappings[$lastSegment])) {
				$matchedGroups[] = (int)$mappings[$lastSegment];
			}
		}
		if (empty($matchedGroups)) {
			// Fallback to default group
			$defaultGroupId = $this->config['oidcDefaultGroupId'];
			if ($defaultGroupId !== null && $defaultGroupId !== '') {
				return (int)$defaultGroupId;
			}
			// Use system default
			return $this->getDefaultGroupId();
		}
		switch ($mode) {
			case 'highest_privilege':
				// Lower group_id = higher privilege in Organizr (0 = admin)
				return min($matchedGroups);
			case 'lowest_privilege':
				return max($matchedGroups);
			case 'first':
			default:
				return $matchedGroups[0];
		}
	}

	/**
	 * Get default group ID from system settings
	 */
	private function getDefaultGroupId()
	{
		// Default to group ID 3 (User) if not configured
		return 3;
	}

	/**
	 * Create or link user from OIDC claims
	 */
	public function createOrLinkOIDCUser($provider, $userInfo, $oidcGroups)
	{
		$email = $userInfo['email'] ?? '';
		$username = $userInfo['preferred_username'] ?? $userInfo['name'] ?? $userInfo['sub'] ?? '';
		$image = $userInfo['picture'] ?? '';
		if (empty($username)) {
			$this->setLoggerChannel('OIDC')->error('No username available from OIDC claims');
			return null;
		}
		$groupId = $this->mapOIDCGroupToOrganizr($oidcGroups);
		$group = $this->getGroupById($groupId);
		$groupName = $group['group'] ?? 'User';
		// Check if user exists by username
		$existingUser = $this->getUserByUsername($username);
		if ($existingUser) {
			// Update auth_service and optionally group
			$updates = ['auth_service' => 'oidc::' . $provider];
			if ($this->config['oidcUpdateGroupsOnLogin']) {
				$updates['group_id'] = $groupId;
				$updates['group'] = $groupName;
			}
			$this->updateUserById($existingUser['id'], $updates);
			$this->setLoggerChannel('OIDC')->info('Linked existing user: ' . $username);
			return $existingUser;
		}
		// Check if user exists by email and linking is enabled
		if (!empty($email) && $this->config['oidcLinkExistingUsers']) {
			$existingUser = $this->getUserByEmail($email);
			if ($existingUser) {
				$updates = ['auth_service' => 'oidc::' . $provider];
				if ($this->config['oidcUpdateGroupsOnLogin']) {
					$updates['group_id'] = $groupId;
					$updates['group'] = $groupName;
				}
				$this->updateUserById($existingUser['id'], $updates);
				$this->setLoggerChannel('OIDC')->info('Linked user by email: ' . $email);
				return $existingUser;
			}
		}
		// Create new user if auto-create is enabled
		if (!$this->config['oidcAutoCreateUsers']) {
			$this->setLoggerChannel('OIDC')->warning('User not found and auto-create disabled: ' . $username);
			return null;
		}
		// Generate random password (user won't use it)
		$password = bin2hex(random_bytes(16));
		$userInfo = [
			'username' => $username,
			'password' => password_hash($password, PASSWORD_BCRYPT),
			'email' => $email,
			'group' => $groupName,
			'group_id' => $groupId,
			'image' => $image ?: $this->gravatar($email),
			'register_date' => $this->currentTime,
			'auth_service' => 'oidc::' . $provider,
		];
		try {
			$this->db->query('INSERT INTO [users]', $userInfo);
			$this->setLoggerChannel('OIDC')->info('Created new OIDC user: ' . $username);
			return $this->getUserByUsername($username);
		} catch (Exception $e) {
			$this->setLoggerChannel('OIDC')->error('Failed to create user: ' . $e->getMessage());
			return null;
		}
	}

	/**
	 * Process OIDC callback
	 */
	public function processOIDCCallback($provider, $code, $state)
	{
		// Validate state
		if (!$this->validateOIDCState($state, $provider)) {
			$this->setAPIResponse('error', 'Invalid or expired state parameter', 400);
			return false;
		}
		// Exchange code for tokens
		$tokens = $this->exchangeOIDCCode($provider, $code);
		if (!$tokens || empty($tokens['access_token'])) {
			$this->setAPIResponse('error', 'Failed to exchange authorization code', 500);
			return false;
		}
		// Get user info from userinfo endpoint or ID token
		$userInfo = $this->getOIDCUserInfo($provider, $tokens['access_token']);
		if (!$userInfo && !empty($tokens['id_token'])) {
			$userInfo = $this->decodeOIDCIdToken($tokens['id_token']);
		}
		if (!$userInfo) {
			$this->setAPIResponse('error', 'Failed to get user information', 500);
			return false;
		}
		// Validate nonce if present
		$storedNonce = $_SESSION['oidc_nonce'] ?? null;
		unset($_SESSION['oidc_nonce']);
		if ($storedNonce && isset($userInfo['nonce']) && !hash_equals($storedNonce, $userInfo['nonce'])) {
			$this->setLoggerChannel('OIDC')->warning('Nonce mismatch');
			$this->setAPIResponse('error', 'Invalid nonce', 400);
			return false;
		}
		// Extract groups
		$groups = $this->extractOIDCGroups($provider, $userInfo);
		// Create or link user
		$user = $this->createOrLinkOIDCUser($provider, $userInfo, $groups);
		if (!$user) {
			$this->setAPIResponse('error', 'Failed to create or link user', 500);
			return false;
		}
		// Create Organizr token
		$this->createToken($user['username'], $user['email'], $this->config['rememberMeDays']);
		$this->setLoggerChannel('OIDC')->info('OIDC login successful: ' . $user['username']);
		return $user;
	}

	/**
	 * Initiate OIDC authorization flow
	 */
	public function initiateOIDCFlow($provider)
	{
		if (!$this->config['oidcEnabled']) {
			$this->setAPIResponse('error', 'OIDC is not enabled', 403);
			return false;
		}
		$enabledProviders = $this->getEnabledOIDCProviders();
		if (!isset($enabledProviders[$provider])) {
			$this->setAPIResponse('error', 'Provider not enabled: ' . $provider, 404);
			return false;
		}
		$authUrl = $this->getOIDCAuthorizationUrl($provider);
		if (!$authUrl) {
			$this->setAPIResponse('error', 'Failed to generate authorization URL', 500);
			return false;
		}
		header('Location: ' . $authUrl);
		exit;
	}

	/**
	 * Test OIDC provider connection
	 */
	public function testOIDCConnection($provider)
	{
		$config = $this->getOIDCProviderConfig($provider);
		if (!$config) {
			$this->setAPIResponse('error', 'Provider not found', 404);
			return false;
		}
		if (empty($config['discoveryUrl'])) {
			$this->setAPIResponse('error', 'Discovery URL not configured', 422);
			return false;
		}
		if (empty($config['clientId'])) {
			$this->setAPIResponse('error', 'Client ID not configured', 422);
			return false;
		}
		$discovery = $this->getOIDCDiscovery($provider);
		if (!$discovery) {
			$this->setAPIResponse('error', 'Failed to fetch discovery document', 500);
			return false;
		}
		$required = ['authorization_endpoint', 'token_endpoint', 'issuer'];
		foreach ($required as $field) {
			if (empty($discovery[$field])) {
				$this->setAPIResponse('error', 'Discovery document missing: ' . $field, 500);
				return false;
			}
		}
		$this->setAPIResponse('success', 'OIDC connection successful - Issuer: ' . $discovery['issuer'], 200);
		return true;
	}

	/**
	 * Output callback success page - redirects to Organizr root
	 */
	public function outputOIDCCallbackSuccess($username)
	{
		$this->setLoggerChannel('OIDC')->info('Redirecting user after successful login: ' . $username);
		header('Location: ' . $this->getServerPath());
		exit;
	}

	/**
	 * Output callback error page
	 */
	public function outputOIDCCallbackError($error)
	{
		echo '<!DOCTYPE html>
<html lang="en">
<head>
	<link rel="stylesheet" href="' . $this->getServerPath() . '/css/mvp.css">
	<meta charset="utf-8">
	<meta name="description" content="OIDC Login Error">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>OIDC Login Error</title>
</head>
<body>
	<main>
		<section>
			<aside>
				<h3>Login Failed</h3>
				<p>' . htmlspecialchars($error) . '</p>
				<p><a href="javascript:window.close()">Close this window</a></p>
			</aside>
		</section>
	</main>
</body>
</html>';
		exit;
	}

	/**
	 * Get OIDC logout URL
	 */
	public function getOIDCLogoutUrl($provider)
	{
		$config = $this->getOIDCProviderConfig($provider);
		if (!$config || !$config['supportsEndSession']) {
			return null;
		}
		$discovery = $this->getOIDCDiscovery($provider);
		if (!$discovery || empty($discovery['end_session_endpoint'])) {
			return null;
		}
		$params = [
			'client_id' => $config['clientId'],
			'post_logout_redirect_uri' => $this->getServerPath(),
		];
		return $discovery['end_session_endpoint'] . '?' . http_build_query($params);
	}

	/**
	 * Check if auto-redirect to OIDC is enabled
	 */
	public function shouldAutoRedirectToOIDC()
	{
		if (!$this->config['oidcEnabled'] || !$this->config['oidcAutoRedirect']) {
			return false;
		}
		$provider = $this->config['oidcAutoRedirectProvider'] ?? '';
		if (empty($provider)) {
			return false;
		}
		$enabledProviders = $this->getEnabledOIDCProviders();
		return isset($enabledProviders[$provider]);
	}

	/**
	 * Get auto-redirect provider
	 */
	public function getAutoRedirectProvider()
	{
		return $this->config['oidcAutoRedirectProvider'] ?? '';
	}
}
