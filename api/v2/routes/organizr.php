<?php
$app->get('/organizr/{page}[/{var1}[/{var2}]]', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     tags={"page"},
	 *     path="/api/v2/organizr/{page}",
	 *     summary="Get HTML for Organizr Pages",
	 *     @OA\Parameter(
	 *      name="page",
	 *      description="Page to get",
	 *      @OA\Schema(
	 *          type="string"
	 *      ),
	 *      in="path",
	 *      required=true
	 *      ),
	 *     @OA\Response(
	 *      response="200",
	 *      description="Success",
	 *      @OA\JsonContent(ref="#/components/schemas/get-html"),
	 *     ),
	 *     @OA\Response(response="401",description="Unauthorized"),
	 *     security={{ "api_key":{} }}
	 * )
	 */
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$_GET['organizr'] = true;
	$_GET['vars'] = $args;
	$page = null;
	if ($Organizr->checkRoute($request)) {
		$page = $Organizr->getPage($args['page']);
	}
	if ($page) {
		$response->getBody()->write($page);
		return $response
			->withHeader('Content-Type', 'text/html;charset=UTF-8')
			->withStatus($GLOBALS['responseCode']);
	} else {
		$response->getBody()->write(jsonE($GLOBALS['api']));
		return $response
			->withHeader('Content-Type', 'application/json;charset=UTF-8')
			->withStatus($GLOBALS['responseCode']);
	}
});