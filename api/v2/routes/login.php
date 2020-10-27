<?php
$app->post('/login', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$Organizr->login($Organizr->apiData($request));
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});