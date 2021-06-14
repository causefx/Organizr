<?php
$app->get('/plugins/test/settings', function ($request, $response, $args) {
	$TestPlugin = new TestPlugin();
	if ($TestPlugin->checkRoute($request)) {
		if ($TestPlugin->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $TestPlugin->_pluginGetSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});