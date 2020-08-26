<?php
/**
 * @OA\Tag(
 *     name="plugins-php-mailer",
 *     description="PHP Mailer Plugin"
 * )
 */
/**
 * @OA\Schema(
 *     schema="sendEmailData",
 *     type="object",
 *      @OA\Property(
 *          property="bcc",
 *          description="email of recipients (csv)",
 *          type="string",
 *          example="causefx@organizr.app,elmer@organizr.app",
 *      ),
 *      @OA\Property(
 *          property="subject",
 *          type="string",
 *          example="Hey There Buddy?!",
 *      ),
 *      @OA\Property(
 *          property="body",
 *          type="string",
 *          example="Hi! Boy, has it been a long time!  Have you seen rox in socks?",
 *      ),
 * )
 */
$app->get('/plugins/php-mailer/settings', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     tags={"plugins-php-mailer"},
	 *     path="/api/v2/plugins/php-mailer/settings",
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
	$PhpMailer = new PhpMailer();
	if ($PhpMailer->checkRoute($request)) {
		if ($PhpMailer->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $PhpMailer->_phpMailerPluginGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/php-mailer/email/test', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     tags={"plugins-php-mailer"},
	 *     path="/api/v2/plugins/php-mailer/email/test",
	 *     summary="Send Test Email to Default Admin Email",
	 *     @OA\Response(
	 *      response="200",
	 *      description="Success",
	 *      @OA\JsonContent(ref="#/components/schemas/successNullData"),
	 *     ),
	 *     @OA\Response(response="401",description="Unauthorized"),
	 *     security={{ "api_key":{} }}
	 * )
	 */
	$PhpMailer = new PhpMailer();
	if ($PhpMailer->checkRoute($request)) {
		if ($PhpMailer->qualifyRequest(1, true)) {
			$PhpMailer->_phpMailerPluginSendTestEmail();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/plugins/php-mailer/email/send', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     tags={"plugins-php-mailer"},
	 *     path="/api/v2/plugins/php-mailer/email/send",
	 *     summary="Send Email",
	 *     @OA\RequestBody(
	 *      description="Success",
	 *      required=true,
	 *      @OA\JsonContent(ref="#/components/schemas/sendEmailData"),
	 *     ),
	 *     @OA\Response(
	 *      response="200",
	 *      description="Success",
	 *      @OA\JsonContent(ref="#/components/schemas/successNullData"),
	 *     ),
	 *     @OA\Response(response="401",description="Unauthorized"),
	 *     security={{ "api_key":{} }}
	 * )
	 */
	$PhpMailer = new PhpMailer();
	if ($PhpMailer->checkRoute($request)) {
		if ($PhpMailer->qualifyRequest(1, true)) {
			$PhpMailer->_phpMailerPluginAdminSendEmail($PhpMailer->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/php-mailer/email/list', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     tags={"plugins-php-mailer"},
	 *     path="/api/v2/plugins/php-mailer/email/list",
	 *     summary="Get List of User Emails",
	 *     @OA\Response(
	 *      response="200",
	 *      description="Success",
	 *      @OA\JsonContent(ref="#/components/schemas/php-mailer-email-list"),
	 *     ),
	 *     @OA\Response(response="401",description="Unauthorized"),
	 *     security={{ "api_key":{} }}
	 * )
	 */
	$PhpMailer = new PhpMailer();
	if ($PhpMailer->checkRoute($request)) {
		if ($PhpMailer->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $PhpMailer->_phpMailerPluginGetEmails();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});