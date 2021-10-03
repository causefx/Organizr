<?php
$app->get('/plugins/plexlibraries/settings', function ($request, $response, $args) {
	$plexLibrariesPlugin = new plexLibrariesPlugin();
	if ($plexLibrariesPlugin->checkRoute($request)) {
		if ($plexLibrariesPlugin->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $plexLibrariesPlugin->_pluginGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/plexlibraries/launch', function ($request, $response, $args) {
	$plexLibrariesPlugin = new plexLibrariesPlugin();
	if ($plexLibrariesPlugin->checkRoute($request)) {
		if ($plexLibrariesPlugin->qualifyRequest($plexLibrariesPlugin->config['PLEXLIBRARIES-pluginAuth'], true)) {
			$plexLibrariesPlugin->_pluginLaunch();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/plexlibraries/shares', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->checkRoute($request)) {
		$plexLibrariesPlugin = new plexLibrariesPlugin;
		if ($plexLibrariesPlugin->qualifyRequest($plexLibrariesPlugin->config['PLEXLIBRARIES-pluginAuth'], true)) {
			$GLOBALS['api']['response']['data'] = $plexLibrariesPlugin->plexLibrariesPluginGetPlexShares();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/plugins/plexlibraries/shares/{userId}/{action}/{shareId}', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->checkRoute($request)) {
		$plexLibrariesPlugin = new plexLibrariesPlugin;
		if ($plexLibrariesPlugin->qualifyRequest($plexLibrariesPlugin->config['PLEXLIBRARIES-pluginAuth'], true)) {
			$userId = $args['userId'] ?? null;
			$action = $args['action'] ?? null;
			$shareId = $args['shareId'] ?? null;
			$plexLibrariesPlugin->plexLibrariesPluginUpdatePlexShares($userId, $action, $shareId);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
