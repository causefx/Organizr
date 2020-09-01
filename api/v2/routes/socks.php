<?php
$app->any('/socks/sonarr/{route:.*}', function ($request, $response) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$socks = $Organizr->socks(
		'sonarrURL',
		'sonarrSocksEnabled',
		'sonarrSocksAuth',
		$request,
		'X-Api-Key'
	);
	$data = $socks ?? jsonE($GLOBALS['api']);
	$response->getBody()->write($data);
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->any('/socks/radarr/{route:.*}', function ($request, $response) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$socks = $Organizr->socks(
		'radarrURL',
		'radarrSocksEnabled',
		'radarrSocksAuth',
		$request,
		'X-Api-Key'
	);
	$data = $socks ?? jsonE($GLOBALS['api']);
	$response->getBody()->write($data);
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->any('/socks/lidarr/{route:.*}', function ($request, $response) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$socks = $Organizr->socks(
		'lidarrURL',
		'lidarrSocksEnabled',
		'lidarrSocksAuth',
		$request,
		'X-Api-Key'
	);
	$data = $socks ?? jsonE($GLOBALS['api']);
	$response->getBody()->write($data);
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});