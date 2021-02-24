<?php
/**
 * @OA\Tag(
 *     name="emby"
 * )
 */
$app->post('/emby/register', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     tags={"emby"},
	 *     path="/api/v2/emby/register",
	 *     summary="Register a user using Emby API",
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
		$Organizr->embyJoinAPI($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});