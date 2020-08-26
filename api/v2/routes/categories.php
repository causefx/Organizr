<?php
/**
 * @OA\Tag(
 *     name="categories",
 *     description="Category Information"
 * )
 */
$app->get('/categories', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     security={{ "api_key":{} }},
	 *     tags={"categories"},
	 *     path="/api/v2/categories",
	 *     summary="Get all categories",
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$GLOBALS['api']['response']['data'] = $Organizr->getAllTabs();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});
$app->post('/categories', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     security={{ "api_key":{} }},
	 *     tags={"categories"},
	 *     path="/api/v2/categories",
	 *     summary="Add new category",
	 *     @OA\RequestBody(description="Success",required=true,@OA\JsonContent(ref="#/components/schemas/submit-2fa-verify")),
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->checkRoute($request)) {
		if ($Organizr->qualifyRequest(1, true)) {
			$Organizr->addCategory($Organizr->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});
$app->put('/categories', function ($request, $response, $args) {
	/**
	 * @OA\Put(
	 *     security={{ "api_key":{} }},
	 *     tags={"categories"},
	 *     path="/api/v2/categories",
	 *     summary="Update category order",
	 *     @OA\RequestBody(description="Success",required=true,@OA\JsonContent(ref="#/components/schemas/submit-2fa-verify")),
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->checkRoute($request)) {
		if ($Organizr->qualifyRequest(1, true)) {
			$Organizr->updateCategoryOrder($Organizr->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus($GLOBALS['responseCode']);
});
$app->put('/categories/{id}', function ($request, $response, $args) {
	/**
	 * @OA\Put(
	 *     security={{ "api_key":{} }},
	 *     tags={"categories"},
	 *     path="/api/v2/categories/{id}",
	 *     summary="Update category",
	 *     @OA\RequestBody(description="Success",required=true,@OA\JsonContent(ref="#/components/schemas/submit-2fa-verify")),
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->checkRoute($request)) {
		if ($Organizr->qualifyRequest(1, true)) {
			$Organizr->updateCategory($args['id'], $Organizr->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus($GLOBALS['responseCode']);
});
$app->delete('/categories/{id}', function ($request, $response, $args) {
	/**
	 * @OA\Delete(
	 *     security={{ "api_key":{} }},
	 *     tags={"categories"},
	 *     path="/api/v2/categories/{id}",
	 *     summary="Delete category",
	 *     @OA\RequestBody(description="Success",required=true,@OA\JsonContent(ref="#/components/schemas/submit-2fa-verify")),
	 *     @OA\Response(response="200",description="Success",@OA\JsonContent(ref="#/components/schemas/success-message")),
	 *     @OA\Response(response="401",description="Unauthorized",@OA\JsonContent(ref="#/components/schemas/unauthorized-message")),
	 *     @OA\Response(response="404",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="422",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 *     @OA\Response(response="500",description="Error",@OA\JsonContent(ref="#/components/schemas/error-message")),
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->checkRoute($request)) {
		if ($Organizr->qualifyRequest(1, true)) {
			$Organizr->deleteCategory($args['id']);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus($GLOBALS['responseCode']);
});