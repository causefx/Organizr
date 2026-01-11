<?php
/**
 * OIDC Authentication Routes
 */

// Ensure PHP sessions are started for OIDC state management
if (session_status() === PHP_SESSION_NONE) {
	// Configure session cookies for cross-site requests (OIDC redirect flow)
	session_set_cookie_params([
		'lifetime' => 600, // 10 minutes for OIDC flow
		'path' => '/',
		'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
		'httponly' => true,
		'samesite' => 'Lax' // Lax allows the session cookie to be sent on top-level navigations
	]);
	session_start();
}

/**
 * Get enabled OIDC providers (public endpoint)
 */
$app->get('/oidc/providers', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$providers = $Organizr->getEnabledOIDCProviders();
	$result = [];
	foreach ($providers as $provider => $config) {
		$result[$provider] = [
			'name' => $Organizr->config[$config['configPrefix'] . 'Name'] ?? ucfirst($provider),
			'enabled' => true,
		];
	}
	$GLOBALS['api']['response']['data'] = $result;
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

/**
 * Initiate OIDC authorization flow
 */
$app->get('/oidc/{provider}/authorize', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$provider = $args['provider'] ?? '';
	// This will redirect to the provider, exit happens in initiateOIDCFlow
	$Organizr->initiateOIDCFlow($provider);
	// If we get here, there was an error
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

/**
 * OIDC callback handler
 */
$app->get('/oidc/{provider}/callback', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$provider = $args['provider'] ?? '';
	$params = $request->getQueryParams();
	$code = $params['code'] ?? null;
	$state = $params['state'] ?? null;
	$error = $params['error'] ?? null;
	$errorDescription = $params['error_description'] ?? 'Unknown error';
	// Handle error from provider
	if ($error) {
		$Organizr->outputOIDCCallbackError($errorDescription);
		return $response;
	}
	// Validate required parameters
	if (!$code || !$state) {
		$Organizr->outputOIDCCallbackError('Missing code or state parameter');
		return $response;
	}
	// Process callback
	$user = $Organizr->processOIDCCallback($provider, $code, $state);
	if ($user) {
		$Organizr->outputOIDCCallbackSuccess($user['username']);
	} else {
		$Organizr->outputOIDCCallbackError($GLOBALS['api']['response']['message'] ?? 'Authentication failed');
	}
	return $response;
});

/**
 * Test OIDC provider connection (admin only)
 */
$app->get('/oidc/{provider}/test', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->checkRoute($request)) {
		if ($Organizr->qualifyRequest(1, true)) {
			$provider = $args['provider'] ?? '';
			$Organizr->testOIDCConnection($provider);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
