<?php
/**
 * @OA\Tag(
 *     name="plex"
 * )
 */
/**
 * @OA\Schema(
 *     schema="plexRegister",
 *     type="object",
 *     @OA\Property(
 *      property="username",
 *      type="string",
 *      example="causefx"
 *  ),@OA\Property(
 *      property="email",
 *      type="string",
 *      example="causefx@organizr.app"
 *  ),@OA\Property(
 *      property="password",
 *      type="string",
 *      example="iCanHazPa$$w0Rd"
 *  ),
 * )
 */
$app->post('/plex/register', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     tags={"plex"},
	 *     path="/api/v2/plex/register",
	 *     summary="Register a user using Plex API",
	 *     @OA\RequestBody(
	 *      description="Success",
	 *      required=true,
	 *      @OA\JsonContent(ref="#/components/schemas/plexRegister"),
	 *     ),
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
		$Organizr->plexJoinAPI($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plex/servers', function ($request, $response, $args) {
	
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->checkRoute($request)) {
		if ($Organizr->qualifyRequest(1, true)) {
			$Organizr->getPlexServers();
		}
		
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});