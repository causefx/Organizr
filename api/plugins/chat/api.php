<?php
/**
 * @OA\Tag(
 *     name="plugins-chat",
 *     description="Pusher Chat Plugin"
 * )
 */
/**
 * @OA\Schema(
 *     schema="getChatMessages",
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
 *              property="username",
 *              type="string",
 *              example="causefx",
 *          ),
 *          @OA\Property(
 *              property="date",
 *              type="string",
 *              example="2018-09-01 02:02:24",
 *          ),
 *          @OA\Property(
 *              property="gravatar",
 *              type="string",
 *              example="https://www.gravatar.com/avatar/a47c4a4b915ddf9601cd228f890bc366?s=100&d=mm",
 *          ),
 *          @OA\Property(
 *              property="message",
 *              type="string",
 *              example="ok first message!",
 *          ),
 *          @OA\Property(
 *              property="uid",
 *              type="string",
 *              example="f5287",
 *          )
 * })
 *      ),
 *  ),
 * )
 */
/**
 * @OA\Schema(
 *     schema="submitMessageData",
 *     type="object",
 *     @OA\Property(
 *      property="message",
 *      type="string",
 *      example="This is my message"
 *  ),
 * )
 */
/**
 * @OA\Schema(
 *     schema="submitMessage",
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
 *          example="message has been accepted",
 *      ),
 *      @OA\Property(
 *          property="data",
 *          description="data from api",
 *          type="string",
 *          example=null,
 *      ),
 *  ),
 * )
 */
$app->get('/plugins/chat/settings', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     tags={"plugins-chat"},
	 *     path="/api/v2/plugins/chat/settings",
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
	$Chat = new Chat();
	if ($Chat->checkRoute($request)) {
		if ($Chat->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $Chat->_chatPluginGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/chat/message', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     tags={"plugins-chat"},
	 *     path="/api/v2/plugins/chat/message",
	 *     summary="Get all messages",
	 *     @OA\Response(
	 *      response="200",
	 *      description="Success",
	 *      @OA\JsonContent(ref="#/components/schemas/getChatMessages"),
	 *     ),
	 *     @OA\Response(response="401",description="Unauthorized"),
	 *     security={{ "api_key":{} }}
	 * )
	 */
	$Chat = new Chat();
	if ($Chat->checkRoute($request)) {
		if ($Chat->qualifyRequest($Chat->config['CHAT-Auth-include'], true)) {
			$GLOBALS['api']['response']['data'] = $Chat->_chatPluginGetChatMessages();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/plugins/chat/message', function ($request, $response, $args) {
	/**
	 * @OA\Post(
	 *     tags={"plugins-chat"},
	 *     path="/api/v2/plugins/chat/message",
	 *     summary="Submit a message",
	 *     @OA\RequestBody(
	 *      description="Success",
	 *      required=true,
	 *      @OA\JsonContent(ref="#/components/schemas/submitMessageData"),
	 *      @OA\MediaType(
	 *          mediaType="application/x-www-form-urlencoded",
	 *          @OA\Schema(
	 *              type="object",
	 *              @OA\Property(
	 *                  property="message",
	 *                  description="message to send",
	 *                  type="string",
	 *              )
	 *          )
	 *      )
	 *     ),
	 *     @OA\Response(
	 *      response="200",
	 *      description="Success",
	 *      @OA\JsonContent(ref="#/components/schemas/submitMessage"),
	 *     ),
	 *     @OA\Response(response="401",description="Unauthorized"),
	 *     security={{ "api_key":{} }}
	 * )
	 */
	$Chat = new Chat();
	if ($Chat->checkRoute($request)) {
		if ($Chat->qualifyRequest($Chat->config['CHAT-Auth-include'], true)) {
			$Chat->_chatPluginSendChatMessage($Chat->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});