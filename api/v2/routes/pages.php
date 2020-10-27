<?php
/**
 * @OA\Tag(
 *     name="page",
 *     description="HTML for Organizr Pages"
 * )
 */
/**
 * @OA\Schema(
 *     schema="get-html",
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
 *          description="success message or error message",
 *          type="string",
 *          example=null,
 *      ),
 *      @OA\Property(
 *          property="data",
 *          description="data from api",
 *          type="string",
 *          example="\r\n\u003Cscript\u003E\r\n    (function() {\r\n        updateCheck();\r\n        authDebugCheck();\r\n        sponsorLoad();\r\n        newsLoad();\r\n        checkCommitLoad();\r\n        [].slice.call(document.querySelectorAll('.sttabs-main-settings-div')).forEach(function(el) {\r\n            new CBPFWTabs(el);\r\n        });\r\n    })();\r\n\u003C/script\u003E\r\n"
 *     )
 *    ),
 *  ),
 * )
 */
$app->get('/page/{page}', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     tags={"page"},
	 *     path="/api/v2/page/{page}",
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
	if ($Organizr->checkRoute($request)) {
		$page = $Organizr->getPage($args['page']);
		if ($page) {
			$GLOBALS['api']['response']['data'] = $page;
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/page', function ($request, $response, $args) {
	/**
	 * @OA\Get(
	 *     tags={"page"},
	 *     path="/api/v2/page",
	 *     summary="Get list of all Organizr Pages",
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
	if ($Organizr->checkRoute($request)) {
		$page = $Organizr->getPageList();
		if ($page) {
			$GLOBALS['api']['response']['data'] = $page;
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});