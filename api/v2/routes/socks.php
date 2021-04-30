<?php
$app->any('/multiple/socks/{app}/{instance}/{route:.*}', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	switch ($args['app']) {
		case 'sonarr':
			$url = 'sonarrURL';
			$enabled = 'sonarrSocksEnabled';
			$auth = 'sonarrSocksAuth';
			$header = 'X-Api-Key';
			break;
		case 'radarr':
			$url = 'radarrURL';
			$enabled = 'radarrSocksEnabled';
			$auth = 'radarrSocksAuth';
			$header = 'X-Api-Key';
			break;
		case 'lidarr':
			$url = 'lidarrURL';
			$enabled = 'lidarrSocksEnabled';
			$auth = 'lidarrSocksAuth';
			$header = 'X-Api-Key';
			break;
		case 'sabnzbd':
			$url = 'sabnzbdURL';
			$enabled = 'sabnzbdSocksEnabled';
			$auth = 'sabnzbdSocksAuth';
			$header = null;
			break;
		case 'nzbget':
			$url = 'nzbgetURL';
			$enabled = 'nzbgetSocksEnabled';
			$auth = 'nzbgetSocksAuth';
			$header = 'Authorization';
			break;
		default:
			$Organizr->setAPIResponse('error', 'Application not supported for socks', 404);
			$response->getBody()->write(jsonE($GLOBALS['api']));
			return $response
				->withHeader('Content-Type', 'application/json;charset=UTF-8')
				->withStatus($GLOBALS['responseCode']);
	}
	$socks = $Organizr->socks($url, $enabled, $auth, $request, $header, $args['instance']);
	$data = $socks ?? jsonE($GLOBALS['api']);
	$response->getBody()->write($data);
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->any('/socks/{app}/{route:.*}', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	switch ($args['app']) {
		case 'sonarr':
			$url = 'sonarrURL';
			$enabled = 'sonarrSocksEnabled';
			$auth = 'sonarrSocksAuth';
			$header = 'X-Api-Key';
			break;
		case 'radarr':
			$url = 'radarrURL';
			$enabled = 'radarrSocksEnabled';
			$auth = 'radarrSocksAuth';
			$header = 'X-Api-Key';
			break;
		case 'lidarr':
			$url = 'lidarrURL';
			$enabled = 'lidarrSocksEnabled';
			$auth = 'lidarrSocksAuth';
			$header = 'X-Api-Key';
			break;
		case 'sabnzbd':
			$url = 'sabnzbdURL';
			$enabled = 'sabnzbdSocksEnabled';
			$auth = 'sabnzbdSocksAuth';
			$header = null;
			break;
		case 'nzbget':
			$url = 'nzbgetURL';
			$enabled = 'nzbgetSocksEnabled';
			$auth = 'nzbgetSocksAuth';
			$header = 'Authorization';
			break;
		default:
			$Organizr->setAPIResponse('error', 'Application not supported for socks', 404);
			$response->getBody()->write(jsonE($GLOBALS['api']));
			return $response
				->withHeader('Content-Type', 'application/json;charset=UTF-8')
				->withStatus($GLOBALS['responseCode']);
	}
	$socks = $Organizr->socks($url, $enabled, $auth, $request, $header);
	$data = $socks ?? jsonE($GLOBALS['api']);
	$response->getBody()->write($data);
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});