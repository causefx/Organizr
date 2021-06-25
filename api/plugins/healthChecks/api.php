<?php
/**
 * @OA\Tag(
 *     name="plugins-healthchecks",
 *     description="Healthchecks.io Ping Plugin"
 * )
 */
/**
 * @OA\Schema(
 *     schema="healthChecksRun",
 *     type="object",
 *     @OA\Property(
 *      property="response",
 *      type="object",
 *      @OA\Property(
 *          property="result",
 *          description="success or error",
 *          type="string",
 *          example="success",
 *      ),
 *      @OA\Property(
 *          property="message",
 *          description="success or error message",
 *          type="string",
 *          example=null,
 *      ),
 *      @OA\Property(
 *          property="data",
 *          description="data from api",
 *          type="array",
 *          @OA\Items({
 *          @OA\Property(
 *              property="Service Name",
 *              type="string",
 *              example="Radarr",
 *          ),
 *          @OA\Property(
 *              property="UUID",
 *              type="string",
 *              example="883f0097-8f4c-4ca5-a9cf-053cfab8e334",
 *          ),
 *          @OA\Property(
 *              property="External URL",
 *              type="string",
 *              example="https://radarr.com",
 *          ),
 *          @OA\Property(
 *              property="Internal URL",
 *              type="string",
 *              example="http://radarr:7878",
 *          ),
 *          @OA\Property(
 *              property="Enabled",
 *              type="string",
 *              example="true",
 *          ),
 *          @OA\Property(
 *              property="results",
 *              type="array",
 *              @OA\Items({
 *                  @OA\Property(
 *                      property="internal",
 *                      type="string",
 *                      example="Success",
 *                  ),
 *                  @OA\Property(
 *                      property="external",
 *                      type="string",
 *                      example="Success",
 *                  ),
 *
 *              }),
 *          ),
 * })
 *      ),
 *  ),
 * )
 */
$app->get('/plugins/healthchecks/settings', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     tags={"plugins-healthchecks"},
	 *     path="/api/v2/plugins/healthchecks/settings",
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
	$HealthChecks = new HealthChecks();
	if ($HealthChecks->checkRoute($request)) {
		if ($HealthChecks->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $HealthChecks->_healthCheckPluginGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/healthchecks/run', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     tags={"plugins-healthchecks"},
	 *     path="/api/v2/plugins/healthchecks/run",
	 *     summary="Run Healthchecks.io plugin",
	 *     @OA\Response(
	 *      response="200",
	 *      description="Success",
	 *      @OA\JsonContent(ref="#/components/schemas/healthChecksRun"),
	 *     ),
	 *     @OA\Response(response="401",description="Unauthorized"),
	 *     security={{ "api_key":{} }}
	 * )
	 */
	$HealthChecks = new HealthChecks();
	if ($HealthChecks->checkRoute($request)) {
		if ($HealthChecks->qualifyRequest($HealthChecks->config['HEALTHCHECKS-Auth-include'], true)) {
			$HealthChecks->_healthCheckPluginRun();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});