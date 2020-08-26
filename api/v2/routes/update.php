<?php
/**
 * @OA\Tag(
 *     name="update",
 *     description="Organizr Update"
 * )
 */
$app->get('/update/download/{branch}', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     security={{ "api_key":{} }},
	 *     tags={"update"},
	 *     path="/api/v2/update/download/{branch}",
	 *     summary="Download Organizr Update Files",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->upgradeInstall($args['branch'], '1');
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});
$app->get('/update/unzip/{branch}', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     security={{ "api_key":{} }},
	 *     tags={"update"},
	 *     path="/api/v2/update/unzip/{branch}",
	 *     summary="Unzip Organizr Update Files",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->upgradeInstall($args['branch'], '2');
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});
$app->get('/update/move/{branch}', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     security={{ "api_key":{} }},
	 *     tags={"update"},
	 *     path="/api/v2/update/move/{branch}",
	 *     summary="Move Organizr Update Files",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->upgradeInstall($args['branch'], '3');
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});
$app->get('/update/cleanup/{branch}', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     security={{ "api_key":{} }},
	 *     tags={"update"},
	 *     path="/api/v2/update/cleanup/{branch}",
	 *     summary="Cleanup Organizr Update Files",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->upgradeInstall($args['branch'], '4');
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});
$app->get('/update/docker', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     security={{ "api_key":{} }},
	 *     tags={"update"},
	 *     path="/api/v2/update/docker",
	 *     summary="Update Organizr install using Docker Container script",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->dockerUpdate();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});
$app->get('/update/windows', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     security={{ "api_key":{} }},
	 *     tags={"update"},
	 *     path="/api/v2/update/windows",
	 *     summary="Update Organizr install using Windows script",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->windowsUpdate();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});