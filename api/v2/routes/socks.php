<?php
$app->any('/socks/sonarr/{route:.*}', function ($request, $response) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$error = false;
	if (!$Organizr->config['sonarrSocksEnabled']) {
		$error = true;
		$Organizr->setAPIResponse('error', 'Sonarr SOCKS module is not enabled', 409);
	}
	if (!$Organizr->qualifyRequest($Organizr->config['sonarrSocksAuth'], true)) {
		$error = true;
	}
	if (!$error) {
		$new = str_ireplace('/api/v2/socks/sonarr', '', $request->getUri()->getPath());
		$getParams = ($_GET) ? '?' . http_build_query($_GET) : '';
		$url = $Organizr->qualifyURL($Organizr->config['sonarrURL']) . $new . $getParams;
		$url = $Organizr->cleanPath($url);
		$options = ($Organizr->localURL($url)) ? array('verify' => false) : array();
		$headers = [];
		if ($request->hasHeader('X-Api-Key')) {
			$headerKey = $request->getHeaderLine('X-Api-Key');
			$headers['X-Api-Key'] = $headerKey;
		}
		switch ($request->getMethod()) {
			case 'GET':
				$call = Requests::get($url, $headers, $options);
				break;
			case 'POST':
				$call = Requests::post($url, $headers, $Organizr->apiData($request), $options);
				break;
			case 'DELETE':
				$call = Requests::delete($url, $headers, $options);
				break;
			case 'PUT':
				$call = Requests::put($url, $headers, $Organizr->apiData($request), $options);
				break;
			default:
				$call = Requests::get($url, $headers, $options);
		}
		$response->getBody()->write($call->body);
	} else {
		$response->getBody()->write(jsonE($GLOBALS['api']));
	}
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->any('/socks/radarr/{route:.*}', function ($request, $response) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$error = false;
	if (!$Organizr->config['radarrSocksEnabled']) {
		$error = true;
		$Organizr->setAPIResponse('error', 'Radarr SOCKS module is not enabled', 409);
	}
	if (!$Organizr->qualifyRequest($Organizr->config['radarrSocksAuth'], true)) {
		$error = true;
	}
	if (!$error) {
		$new = str_ireplace('/api/v2/socks/radarr', '', $request->getUri()->getPath());
		$getParams = ($_GET) ? '?' . http_build_query($_GET) : '';
		$url = $Organizr->qualifyURL($Organizr->config['radarrURL']) . $new . $getParams;
		$url = $Organizr->cleanPath($url);
		$options = ($Organizr->localURL($url)) ? array('verify' => false) : array();
		$headers = [];
		if ($request->hasHeader('X-Api-Key')) {
			$headerKey = $request->getHeaderLine('X-Api-Key');
			$headers['X-Api-Key'] = $headerKey;
		}
		switch ($request->getMethod()) {
			case 'GET':
				$call = Requests::get($url, $headers, $options);
				break;
			case 'POST':
				$call = Requests::post($url, $headers, $Organizr->apiData($request), $options);
				break;
			case 'DELETE':
				$call = Requests::delete($url, $headers, $options);
				break;
			case 'PUT':
				$call = Requests::put($url, $headers, $Organizr->apiData($request), $options);
				break;
			default:
				$call = Requests::get($url, $headers, $options);
		}
		$response->getBody()->write($call->body);
	} else {
		$response->getBody()->write(jsonE($GLOBALS['api']));
	}
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->any('/socks/lidarr/{route:.*}', function ($request, $response) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	$error = false;
	if (!$Organizr->config['lidarrSocksEnabled']) {
		$error = true;
		$Organizr->setAPIResponse('error', 'Lidarr SOCKS module is not enabled', 409);
	}
	if (!$Organizr->qualifyRequest($Organizr->config['lidarrSocksAuth'], true)) {
		$error = true;
	}
	if (!$error) {
		$new = str_ireplace('/api/v2/socks/lidarr', '', $request->getUri()->getPath());
		$getParams = ($_GET) ? '?' . http_build_query($_GET) : '';
		$url = $Organizr->qualifyURL($Organizr->config['lidarrURL']) . $new . $getParams;
		$url = $Organizr->cleanPath($url);
		$options = ($Organizr->localURL($url)) ? array('verify' => false) : array();
		$headers = [];
		if ($request->hasHeader('X-Api-Key')) {
			$headerKey = $request->getHeaderLine('X-Api-Key');
			$headers['X-Api-Key'] = $headerKey;
		}
		switch ($request->getMethod()) {
			case 'GET':
				$call = Requests::get($url, $headers, $options);
				break;
			case 'POST':
				$call = Requests::post($url, $headers, $Organizr->apiData($request), $options);
				break;
			case 'DELETE':
				$call = Requests::delete($url, $headers, $options);
				break;
			case 'PUT':
				$call = Requests::put($url, $headers, $Organizr->apiData($request), $options);
				break;
			default:
				$call = Requests::get($url, $headers, $options);
		}
		$response->getBody()->write($call->body);
	} else {
		$response->getBody()->write(jsonE($GLOBALS['api']));
	}
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});