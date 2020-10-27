<?php
$app->get('/ping', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     path="/api/v2/ping",
	 *     summary="Ping the Organizr API",
	 *     @OA\Response(
	 *         response="200",
	 *         description="Success",
	 *         @OA\JsonContent(ref="#/components/schemas/ping"),
	 *     ),
	 *   @OA\Response(response="401",description="Unauthorized"),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$GLOBALS['api']['response']['data'] = 'pong';
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json');
	
});
$app->post('/ping', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$GLOBALS['api']['response']['data'] = $Organizr->ping($Organizr->apiData($request));
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/ping/{ping}', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$GLOBALS['api']['response']['data'] = $Organizr->ping(array('list' => $args['ping']));
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});