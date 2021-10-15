<?php
$app->get('/log[/{number}]', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->checkRoute($request)) {
		if ($Organizr->qualifyRequest(1, true)) {
			$args['number'] = $args['number'] ?? 0;
			$_GET['pageSize'] = $_GET['pageSize'] ?? 1000;
			$_GET['offset'] = $_GET['offset'] ?? 0;
			$_GET['filter'] = $_GET['filter'] ?? 'NONE';
			$Organizr->getLog($_GET['pageSize'], $_GET['offset'], $_GET['filter'], $args['number']);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->delete('/log[/{number}]', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->checkRoute($request)) {
		if ($Organizr->qualifyRequest(1, true)) {
			$args['number'] = $args['number'] ?? 0;
			$Organizr->purgeLog($args['number']);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});