<?php
/**
 * @OA\Tag(
 *     name="plugins-speedtest",
 *     description="SpeedTest Plugin"
 * )
 */
$app->get('/plugins/speedtest/settings', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     tags={"plugins-speedtest"},
	 *     path="/api/v2/plugins/speedtest/settings",
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
	$SpeedTest = new SpeedTest();
	if ($SpeedTest->checkRoute($request)) {
		if ($SpeedTest->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $SpeedTest->speedTestGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
