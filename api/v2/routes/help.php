<?php
$app->get('/help/smtp', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->checkRoute($request)) {
		if ($Organizr->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $Organizr->saveOrganizrSmtpFromAPI();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});