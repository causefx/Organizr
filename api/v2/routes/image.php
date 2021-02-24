<?php
$app->get('/image', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$GLOBALS['api']['response']['data'] = $Organizr->getImages();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});
$app->get('/image/select', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$GLOBALS['api']['response']['data'] = $Organizr->getImagesSelect();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});
$app->delete('/image/{image}', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->removeImage($args['image']);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});
$app->post('/image', function ($request, $response, $args) {
	$Organizr = ($request->getAttribute('Organizr')) ?? new Organizr();
	if ($Organizr->qualifyRequest(1, true)) {
		$Organizr->uploadImage();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
	
});