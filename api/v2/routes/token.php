<?php
$app->delete('/token/{id}', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(998, true)) {
		$Organizr->revokeTokenByIdCurrentUser($args['id']);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

$app->post('/token/validate', function ($request, $response, $args) {
        $Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
        if ($Organizr->qualifyRequest(999, true)) {
                $GLOBALS['api']['response']['data'] = $Organizr->validateToken($_REQUEST["Token"]);
        }
        $response->getBody()->write(jsonE($GLOBALS['api']));
        return $response
                ->withHeader('Content-Type', 'application/json;charset=UTF-8')
                ->withStatus($GLOBALS['responseCode']);
});
