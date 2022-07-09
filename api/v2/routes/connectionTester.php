<?php
/**
 * @OA\Tag(
 *     name="test connection",
 *     description="Test Connections"
 * )
 */
$app->post('/test/ldap', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/ldap",
	 *     summary="Test LDAP connection",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="409",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionLdap();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/ldap/login', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/ldap/login",
	 *     summary="Test LDAP connection using account login",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="409",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionLdapLogin($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/iframe', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/iframe",
	 *     summary="Test if URL can be iFramed",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="409",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->frameTest($Organizr->apiData($request)['url']);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/path', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/path",
	 *     summary="Test if path has correct permissions",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$Organizr->testWizardPath($Organizr->apiData($request));
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/plex', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/plex",
	 *     summary="Test connection to Plex",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionPlex($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/emby', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/emby",
	 *     summary="Test connection to Emby",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionEmby($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/jellyfin', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/jellyfin",
	 *     summary="Test connection to Jellyfin",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionJellyfin($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/sabnzbd', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/sabnzbd",
	 *     summary="Test connection to SabNZBd",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionSabNZBd($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/pihole', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/pihole",
	 *     summary="Test connection to PiHole",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionPihole($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/adguard', function ($request, $response, $args) {
		/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/adguard",
	 *     summary="Test connection to AdGuard",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionAdGuard($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/rtorrent', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/rtorrent",
	 *     summary="Test connection to rTorrent",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionRTorrent($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/sonarr', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/sonarr",
	 *     summary="Test connection to Sonarr",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionSonarr($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/radarr', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/radarr",
	 *     summary="Test connection to Radarr",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionRadarr($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/lidarr', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/lidarr",
	 *     summary="Test connection to Lidarr",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionLidarr($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/sickrage', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/sickrage",
	 *     summary="Test connection to SickRage",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionSickRage($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/ombi', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/ombi",
	 *     summary="Test connection to Ombi",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionOmbi($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/overseerr', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/overseerr",
	 *     summary="Test connection to Overseerr",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionOverseerr($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/nzbget', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/nzbget",
	 *     summary="Test connection to NzbGet",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionNZBGet($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/utorrent', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/utorrent",
	 *     summary="Test connection to uTorrent",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="400",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionuTorrent($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/deluge', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/deluge",
	 *     summary="Test connection to Deluge",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionDeluge($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/jdownloader', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/jdownloader",
	 *     summary="Test connection to jDownloader",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionJDownloader($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/transmission', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/transmission",
	 *     summary="Test connection to Transmission",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionTransmission($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/qbittorrent', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/qbittorrent",
	 *     summary="Test connection to qBittorrent",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionQBittorrent($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/unifi', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/unifi",
	 *     summary="Test connection to Unifi",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionUnifi($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/unifi/site', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/unifi/site",
	 *     summary="Test connection to Unifi Sites",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->getUnifiSiteName($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/tautulli', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/tautulli",
	 *     summary="Test connection to Tautulli",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionTautulli($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/cron', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/cron",
	 *     summary="Test cron schedule",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testCronSchedule($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/test/cron', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/cron",
	 *     summary="Test if cron is setup correctly",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$file = $Organizr->checkCronFile();
		if ($file) {
			$Organizr->setResponse(200, 'Cron file is setup');
		} else {
			$Organizr->setResponse(500, 'Cron file is not setup correctly');
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/folder', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/folder",
	 *     summary="Test folder path",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testFolder($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/database', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/database",
	 *     summary="Test Database connection",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true) || !$Organizr->hasConfig()) {
		$Organizr->testDatabaseConnection($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/jackett', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/jackett",
	 *     summary="Test connection to Jackett",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionJackett();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/test/slack-logs', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"test connection"},
	 *     path="/api/v2/test/slack-logs",
	 *     summary="Test connection to Slack/Discord",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->testConnectionSlackLogs();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
