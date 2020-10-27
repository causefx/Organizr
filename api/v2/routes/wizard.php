<?php
$app->post('/wizard', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->checkRoute($request)) {
		$GLOBALS['api']['response']['data'] = $Organizr->wizardConfig($Organizr->apiData($request));
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});