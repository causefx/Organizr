<?php
$app->get('/plugins/komga/books/latest', function ($request, $response, $args) {
    $komgaPlugin = new KomgaPlugin();
    $GLOBALS['api']['response']['data'] = [];

    if ($komgaPlugin->checkRoute($request)) {
        if ($komgaPlugin->qualifyRequest($komgaPlugin->config['KOMGA-minAuth'], true)) {
            $url = $komgaPlugin->config['KOMGA-url'] ?? '';
            $apiKey = $komgaPlugin->config['KOMGA-apikey'] ?? '';

            // Check for group override
            $groupId = $komgaPlugin->user['groupID'] ?? null;
            $libraries = $komgaPlugin->config['KOMGA-libraries'] ?? 'all';

            if ($groupId !== null) {
                $groupOverride = $komgaPlugin->config['KOMGA-library-group-' . $groupId] ?? 'default';
                if ($groupOverride !== 'default') {
                    $libraries = $groupOverride;
                }
            }

            if ($url && $apiKey) {
                $endpoint = '/api/v1/books?sort=createdDate,desc&size=20';
                if ($libraries && $libraries !== 'all') {
                    $endpoint .= '&library_id=' . $libraries;
                }

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, rtrim($url, '/') . $endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "X-API-Key: $apiKey",
                    "Accept: application/json"
                ]);
                $res = curl_exec($ch);
                curl_close($ch);

                if ($res) {
                    $data = json_decode($res, true);
                    // Provide settings to JS via our API response
                    $GLOBALS['api']['response']['data'] = [
                        'title' => $komgaPlugin->config['KOMGA-title'] ?? 'Recently added books',
                        'baseUrl' => rtrim($url, '/') . '/book/',
                        'books' => (isset($data['content']) ? $data['content'] : $data),
                        'tabName' => $komgaPlugin->config['KOMGA-tab-name'] ?? 'Komga'
                    ];
                }
            }
        }
    }

    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
    ->withHeader('Content-Type', 'application/json;charset=UTF-8')
    ->withStatus($GLOBALS['responseCode']);
});

$app->get('/plugins/komga/image', function ($request, $response, $args) {
    $komgaPlugin = new KomgaPlugin();

    if ($komgaPlugin->checkRoute($request)) {
        if ($komgaPlugin->qualifyRequest(999, true)) {
            $apiUrl = $komgaPlugin->config['KOMGA-url'] ?? '';
            $apiKey = $komgaPlugin->config['KOMGA-apikey'] ?? '';

            // Allow full URL (if passed via query string) or just append to Komga API URL
            $urlParams = $request->getQueryParams();
            $thumbnailUrl = $urlParams['url'] ?? '';

            if ($apiUrl && $apiKey && $thumbnailUrl) {
                // Determine if thumbnailUrl is absolute or relative
                if (!preg_match('~^(?:f|ht)tps?://~i', $thumbnailUrl)) {
                    $thumbnailUrl = rtrim($apiUrl, '/') . '/' . ltrim($thumbnailUrl, '/');
                }

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $thumbnailUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "X-API-Key: $apiKey"
                ]);
                $res = curl_exec($ch);
                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                curl_close($ch);

                if ($res) {
                    $response->getBody()->write($res);
                    return $response
                    ->withHeader('Content-Type', $contentType ?: 'image/jpeg');
                }
            }
        }
    }

    return $response->withStatus(404);
});

$app->get('/plugins/komga/settings', function ($request, $response, $args) {
    $komgaPlugin = new KomgaPlugin();
    if ($komgaPlugin->checkRoute($request)) {
        if ($komgaPlugin->qualifyRequest(1, true)) {
            $GLOBALS['api']['response']['data'] = $komgaPlugin->_pluginGetSettings();
        }
    }
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
    ->withHeader('Content-Type', 'application/json;charset=UTF-8')
    ->withStatus($GLOBALS['responseCode']);
});