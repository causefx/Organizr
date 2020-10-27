<?php
/**
 * @OA\Tag(
 *     name="config",
 *     description="Organizr Configuration Items"
 * )
 */
$app->get('/config', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$GLOBALS['api']['response']['data'] = $Organizr->config;
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->put('/config', function ($request, $response, $args) {
	/**
	 * @OA\Put(
	 *     tags={"config"},
	 *     path="/api/v2/config",
	 *     summary="Update Organizr Coniguration Item(s)",
	 *     @OA\RequestBody(
	 *      description="Success",
	 *      required=true,
	 *      @OA\JsonContent(ref="#/components/schemas/config-items-example"),
	 *     ),
	 *     @OA\Response(
	 *      response="200",
	 *      description="Success",
	 *      @OA\JsonContent(ref="#/components/schemas/success-message"),
	 *     ),
	 *     @OA\Response(response="401",description="Unauthorized"),
	 *     security={{ "api_key":{} }}
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->updateConfigItems($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});