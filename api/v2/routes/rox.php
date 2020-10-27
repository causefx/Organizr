<?php
$app->get('/rox', function ($request, $response, $args) {
	$GLOBALS['api']['response']['message'] = 'rox in socks!';
	$GLOBALS['api']['response']['data'] = 'https://www.roxinsocks.com/';
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->redirect('/roxinsocks', 'https://roxinsocks.com', 301);