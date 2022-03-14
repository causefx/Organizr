<?php
/* Forward root to /status */
$app->get('', function ($request, $response, $args) {
	return $response
		->withHeader('Location', '/api/v2/status')
		->withStatus(302);
});
$app->get('/', function ($request, $response, $args) {
	return $response
		->withHeader('Location', '/api/v2/status')
		->withStatus(302);
});
$app->get('/status', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     path="/api/v2/status",
	 *     summary="Query Organizr API to perform a Status Check",
	 *     @OA\Response(
	 *      response="200",
	 *      description="Success",
	 *      @OA\JsonContent(ref="#/components/schemas/status"),
	 *     ),
	 *     @OA\Response(response="401",description="Unauthorized")
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->checkRoute($request)) {
		$GLOBALS['api']['response']['data'] = $Organizr->status(true);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->any('/auth', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     path="/api/v2/auth",
	 *     summary="Nginx auth_request",
	 * @OA\Parameter(
	 *   name="group",
	 *   description="The id of the group allowed",
	 *   @OA\Schema(
	 *     type="integer",
	 *     format="int64",
	 *   ),
	 *   in="query",
	 *   required=false
	 * ),
	 * @OA\Parameter(
	 *   name="whitelist",
	 *   description="Whitelisted Ip's",
	 *   @OA\Schema(
	 *     type="array",
	 *     @OA\Items(
	 *      type="string",
	 *     ),
	 *   ),
	 *   in="query",
	 *   explode=false,
	 *   required=false
	 * ),
	 * @OA\Parameter(
	 *   name="blacklist",
	 *   description="Blacklisted Ip's",
	 *   @OA\Schema(
	 *     type="array",
	 *     @OA\Items(
	 *      type="string",
	 *     ),
	 *   ),
	 *   in="query",
	 *   explode=false,
	 *   required=false
	 * ),
	 * @OA\Response(
	 *  response="200",
	 *  description="Success",
	 *  ),
	 *  @OA\Response(response="401",description="Unauthorized"),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$Organizr->auth();
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->any('/auth-{group}', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$_GET['group'] = $args['group'];
	$Organizr->auth();
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->any('/auth/[{group}[/{type}[/{ips}]]]', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$_GET['group'] = $args['group'] ?? 0;
	$_GET['type'] = $args['type'] ?? 'deny';
	$_GET['ips'] = $args['ips'] ?? '192.0.0.0';
	$Organizr->auth();
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->any('/organizr-auth/[{group}[/{type}[/{ips}]]]', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$_GET['group'] = $args['group'] ?? 0;
	$_GET['type'] = $args['type'] ?? 'deny';
	$_GET['ips'] = $args['ips'] ?? '192.0.0.0';
	$Organizr->auth();
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/launch', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$tabInfo = $Organizr->getUserTabsAndCategories();
	$GLOBALS['api']['response']['data']['categories'] = ($tabInfo['categories']) ?? false;
	$GLOBALS['api']['response']['data']['tabs'] = ($tabInfo['tabs']) ?? false;
	$GLOBALS['api']['response']['data']['user'] = $Organizr->user;
	$GLOBALS['api']['response']['data']['branch'] = $Organizr->config['branch'];
	$GLOBALS['api']['response']['data']['theme'] = $Organizr->config['theme'];
	$GLOBALS['api']['response']['data']['style'] = $Organizr->config['style'];
	$GLOBALS['api']['response']['data']['darkmode'] = $Organizr->config['autoDarkMode'];
	$GLOBALS['api']['response']['data']['version'] = $Organizr->version;
	$GLOBALS['api']['response']['data']['settings'] = $Organizr->organizrSpecialSettings();
	$GLOBALS['api']['response']['data']['plugins'] = $Organizr->pluginGlobalList();
	$GLOBALS['api']['response']['data']['appearance'] = $Organizr->loadAppearance();
	$GLOBALS['api']['response']['data']['status'] = $Organizr->launch();
	$GLOBALS['api']['response']['data']['sso'] = $Organizr->ssoCookies();
	$GLOBALS['api']['response']['data']['warnings'] = $Organizr->warnings;
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
