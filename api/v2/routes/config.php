<?php
/**
 * @OA\Tag(
 *     name="config",
 *     description="Organizr Configuration Items"
 * )
 */
$app->get('/config[/{item}[/{term}]]', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     tags={"config"},
	 *     path="/api/v2/config",
	 *     summary="Get Organizr Coniguration Items",
	 *     @OA\Response(
	 *      response="200",
	 *      description="Success",
	 *      @OA\JsonContent(ref="#/components/schemas/success-message"),
	 *     ),
	 *     @OA\Response(response="401",description="Unauthorized"),
	 *     security={{ "api_key":{} }}
	 * )
	 */
	/**
	 * @OA\Get(
	 *     tags={"config"},
	 *     path="/api/v2/config/{item}",
	 *     summary="Get Organizr Coniguration Item",
	 *     @OA\Parameter(name="item",description="The key of the item you want to grab",@OA\Schema(type="string"),in="path",required=true,example="configVersion"),
	 *     @OA\Response(
	 *      response="200",
	 *      description="Success",
	 *      @OA\JsonContent(ref="#/components/schemas/success-message"),
	 *     ),
	 *     @OA\Response(response="401",description="Unauthorized"),
	 *     security={{ "api_key":{} }}
	 * )
	 */
	/**
	 * @OA\Get(
	 *     tags={"config"},
	 *     path="/api/v2/config/search/{term}",
	 *     summary="Search Organizr Coniguration Items",
	 *     @OA\Parameter(name="term",description="The term of the items you want to grab",@OA\Schema(type="string"),in="path",required=true,example="version"),
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
		if (isset($args['item'])) {
			$search = ($args['term']) ?? null;
			$Organizr->getConfigItem($args['item'], $search);
		} else {
			$GLOBALS['api']['response']['data'] = $Organizr->getConfigItems();
		}
		
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