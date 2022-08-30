<?php
$app->get('/plugins/shuck-stop/settings', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     tags={"plugins-shuck-stop"},
	 *     path="/api/v2/plugins/shuck-stop/settings",
	 *     summary="Get settings",
	 *     @OA\Response(
	 *      response="200",
	 *      description="Success",
	 *      @OA\JsonContent(ref="#/components/schemas/pluginSettingsPage"),
	 *     ),
	 *     @OA\Response(response="401",description="Unauthorized"),
	 *     security={{ "api_key":{} }}
	 * )
	 */
	$shuckStop = new ShuckStop();
	if ($shuckStop->checkRoute($request)) {
		if ($shuckStop->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $shuckStop->_shuckStopPluginGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/shuck-stop/run', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     tags={"plugins-shuck-stop"},
	 *     path="/api/v2/plugins/shuck-stop/run",
	 *     summary="Run ShuckStop plugin",
	 *     @OA\Response(
	 *      response="200",
	 *      description="Success",
	 *      @OA\JsonContent(ref="#/components/schemas/shuckStopRun"),
	 *     ),
	 *     @OA\Response(response="401",description="Unauthorized"),
	 *     security={{ "api_key":{} }}
	 * )
	 */
	$shuckStop = new ShuckStop();
	if ($shuckStop->checkRoute($request)) {
		if ($shuckStop->qualifyRequest(1, true)) {
			$shuckStop->_shuckStopPluginRun();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});