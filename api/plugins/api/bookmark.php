<?php

$app->get('/plugins/bookmark/settings', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->checkRoute($request)) {
		if ($Bookmark->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $Bookmark->_getSettings();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

$app->get('/plugins/bookmark/page', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_checkRequest($request) && $Bookmark->checkRoute($request)) {
		if ($Bookmark->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $Bookmark->_getPage();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

$app->get('/plugins/bookmark/settings_tab_editor_bookmark_tabs', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_checkRequest($request) && $Bookmark->checkRoute($request)) {
		if ($Bookmark->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $Bookmark->_getSettingsTabEditorBookmarkTabsPage();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

$app->get('/plugins/bookmark/settings_tab_editor_bookmark_categories', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_checkRequest($request) && $Bookmark->checkRoute($request)) {
		if ($Bookmark->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $Bookmark->_getSettingsTabEditorBookmarkCategoriesPage();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// TABS
$app->get('/plugins/bookmark/tabs', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_checkRequest($request) && $Bookmark->checkRoute($request)) {
		if ($Bookmark->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $Bookmark->_getTabs();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->get('/plugins/bookmark/tabs/{id}', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_checkRequest($request) && $Bookmark->checkRoute($request)) {
		$GLOBALS['api']['response']['data'] = $Bookmark->_getTabByIdCheckUser($args['id']);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/plugins/bookmark/tabs', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_checkRequest($request) && $Bookmark->checkRoute($request)) {
		if ($Bookmark->qualifyRequest(1, true)) {
			$Bookmark->_addTab($Bookmark->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->put('/plugins/bookmark/tabs', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_checkRequest($request) && $Bookmark->checkRoute($request)) {
		if ($Bookmark->qualifyRequest(1, true)) {
			$Bookmark->_updateTabOrder($Bookmark->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus($GLOBALS['responseCode']);
});
$app->put('/plugins/bookmark/tabs/{id}', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_checkRequest($request) && $Bookmark->checkRoute($request)) {
		if ($Bookmark->qualifyRequest(1, true)) {
			$Bookmark->_updateTab($args['id'], $Bookmark->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus($GLOBALS['responseCode']);
});
$app->delete('/plugins/bookmark/tabs/{id}', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_checkRequest($request) && $Bookmark->checkRoute($request)) {
		if ($Bookmark->qualifyRequest(1, true)) {
			$Bookmark->_deleteTab($args['id']);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus($GLOBALS['responseCode']);
});

// CATEGORIES
$app->get('/plugins/bookmark/categories', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_checkRequest($request) && $Bookmark->checkRoute($request)) {
		if ($Bookmark->qualifyRequest(1, true)) {
			$GLOBALS['api']['response']['data'] = $Bookmark->_getTabs();
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->post('/plugins/bookmark/categories', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_checkRequest($request) && $Bookmark->checkRoute($request)) {
		if ($Bookmark->qualifyRequest(1, true)) {
			$Bookmark->_addCategory($Bookmark->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
$app->put('/plugins/bookmark/categories', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_checkRequest($request) && $Bookmark->checkRoute($request)) {
		if ($Bookmark->qualifyRequest(1, true)) {
			$Bookmark->_updateCategoryOrder($Bookmark->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus($GLOBALS['responseCode']);
});
$app->put('/plugins/bookmark/categories/{id}', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_checkRequest($request) && $Bookmark->checkRoute($request)) {
		if ($Bookmark->qualifyRequest(1, true)) {
			$Bookmark->_updateCategory($args['id'], $Bookmark->apiData($request));
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus($GLOBALS['responseCode']);
});
$app->delete('/plugins/bookmark/categories/{id}', function ($request, $response, $args) {
	$Bookmark = new Bookmark();
	if ($Bookmark->_checkRequest($request) && $Bookmark->checkRoute($request)) {
		if ($Bookmark->qualifyRequest(1, true)) {
			$Bookmark->_deleteCategory($args['id']);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus($GLOBALS['responseCode']);
});
