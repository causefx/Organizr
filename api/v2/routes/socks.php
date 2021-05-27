<?php
$app->any('/multiple/socks/{app}/{instance}/{route:.*}', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$appDetails = $Organizr->socksListing($args['app']);
	if (!$appDetails) {
		$Organizr->setAPIResponse('error', 'Application not supported for socks', 404);
		$response->getBody()->write(jsonE($GLOBALS['api']));
		return $response
			->withHeader('Content-Type', 'application/json;charset=UTF-8')
			->withStatus($GLOBALS['responseCode']);
	}
	$socks = $Organizr->socks($appDetails, $request, $args['instance']);
	$data = $socks ?? jsonE($GLOBALS['api']);
	$response->getBody()->write($data);
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->any('/socks/{app}/{route:.*}', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$appDetails = $Organizr->socksListing($args['app']);
	if (!$appDetails) {
		$Organizr->setAPIResponse('error', 'Application not supported for socks', 404);
		$response->getBody()->write(jsonE($GLOBALS['api']));
		return $response
			->withHeader('Content-Type', 'application/json;charset=UTF-8')
			->withStatus($GLOBALS['responseCode']);
	}
	$socks = $Organizr->socks($appDetails, $request);
	$data = $socks ?? jsonE($GLOBALS['api']);
	$response->getBody()->write($data);
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});