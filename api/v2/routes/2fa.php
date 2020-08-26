<?php
/**
 * @OA\Tag(
 *     name="2fa",
 *     description="Two Form Authentication"
 * )
 */
/**
 * @OA\Schema(
 *     schema="submit-2fa-verify",
 *     type="object",
 *     @OA\Property(
 *      property="secret",
 *      type="string",
 *      example="OX1R4GA3425GSDF"
 *     ),
 *     @OA\Property(
 *      property="code",
 *      type="string",
 *      example="145047"
 *     ),
 *     @OA\Property(
 *      property="type",
 *      type="string",
 *      example="google"
 *     ),
 * )
 */
/**
 * @OA\Schema(
 *     schema="submit-2fa-save",
 *     type="object",
 *     @OA\Property(
 *      property="secret",
 *      type="string",
 *      example="OX1R4GA3425GSDF"
 *     ),
 *     @OA\Property(
 *      property="type",
 *      type="string",
 *      example="google"
 *     ),
 * )
 */
/**
 * @OA\Schema(
 *     schema="submit-2fa-create",
 *     type="object",
 *     @OA\Property(
 *      property="type",
 *      type="string",
 *      example="google"
 *     ),
 * )
 */
$app->post('/2fa', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"2fa"},
	 *     path="/api/v2/2fa",
	 *     summary="Verify 2FA code",
	 *     @OA\RequestBody(description="Success",required=true,@OA\JsonContent(ref="#/components/schemas/submit-2fa-verify")),
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(998, true)) {
		$data = $Organizr->apiData($request);
		$GLOBALS['api']['response']['data'] = $Organizr->verify2FA($data['secret'], $data['code'], $data['type']);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->put('/2fa', function ($request, $response, $args) {
	/**
	 * @OA\Put(
	 *     security={{ "api_key":{} }},
	 *     tags={"2fa"},
	 *     path="/api/v2/2fa",
	 *     summary="Save 2FA code",
	 *     @OA\RequestBody(description="Success",required=true,@OA\JsonContent(ref="#/components/schemas/submit-2fa-save")),
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(998, true)) {
		$data = $Organizr->apiData($request);
		$Organizr->save2FA($data['secret'], $data['type']);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/2fa/{type}', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     tags={"2fa"},
	 *     path="/api/v2/2fa/{type}",
	 *     summary="Create 2FA code",
	 *     @OA\Parameter(name="type",description="The type of 2FA",@OA\Schema(type="string"),in="path",required=true,example="google"),
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message"))
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(998, true)) {
		$GLOBALS['api']['response']['data'] = $Organizr->create2FA($args['type']);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->delete('/2fa', function ($request, $response, $args) {
	/**
	 * @OA\Delete(
	 *     security={{ "api_key":{} }},
	 *     tags={"2fa"},
	 *     path="/api/v2/2fa",
	 *     summary="Delete 2FA code",
	 *     @OA\Response(response="204",description="Success"),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(998, true)) {
		$Organizr->remove2FA();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});