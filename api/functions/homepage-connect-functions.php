<?php
/** @noinspection PhpUndefinedFieldInspection */
function homepageConnect($array)
{
	switch ($array['data']['action']) {
		case 'getPlexStreams':
			return (qualifyRequest($GLOBALS['homepagePlexStreamsAuth'])) ? plexConnect('streams') : false;
			break;
		case 'getPlexRecent':
			return (qualifyRequest($GLOBALS['homepagePlexRecentAuth'])) ? plexConnect('recent') : false;
			break;
		case 'getPlexMetadata':
			return (qualifyRequest($GLOBALS['homepagePlexAuth'])) ? plexConnect('metadata', $array['data']['key']) : false;
			break;
		case 'getPlexSearch':
			return (qualifyRequest($GLOBALS['mediaSearchAuth'])) ? plexConnect('search', $array['data']['query']) : false;
			break;
		case 'getPlexPlaylists':
			return (qualifyRequest($GLOBALS['homepagePlexPlaylistAuth'])) ? getPlexPlaylists() : false;
			break;
		case 'getEmbyStreams':
			return (qualifyRequest($GLOBALS['homepageEmbyStreamsAuth']) && $GLOBALS['homepageEmbyEnabled']) ? embyConnect('streams') : false;
			break;
		case 'getEmbyRecent':
			return (qualifyRequest($GLOBALS['homepageEmbyRecentAuth']) && $GLOBALS['homepageEmbyEnabled']) ? embyConnect('recent') : false;
			break;
		case 'getEmbyMetadata':
			return (qualifyRequest($GLOBALS['homepageEmbyAuth']) && $GLOBALS['homepageEmbyEnabled']) ? embyConnect('metadata', $array['data']['key'], true) : false;
			break;
		case 'getJdownloader':
			return jdownloaderConnect();
			break;
		case 'getSabnzbd':
			return sabnzbdConnect();
			break;
		case 'getNzbget':
			return nzbgetConnect();
			break;
		case 'getTransmission':
			return transmissionConnect();
			break;
		case 'getqBittorrent':
			return qBittorrentConnect();
			break;
		case 'getrTorrent':
			return rTorrentConnect();
			break;
		case 'getDeluge':
			return delugeConnect();
			break;
		case 'getCalendar':
			return getCalendar();
			break;
		case 'getRequests':
			return getOmbiRequests($GLOBALS['ombiLimit']);
			break;
		case 'getHealthChecks':
			return (qualifyRequest($GLOBALS['homepageHealthChecksAuth'])) ? getHealthChecks($array['data']['tags']) : false;
			break;
		default:
			# code...
			break;
	}
	return false;
}

function healthChecksTags($tags)
{
	$return = '?tag=';
	if (!$tags) {
		return '';
	} elseif ($tags == '*') {
		return '';
	} else {
		if (strpos($tags, ',') !== false) {
			$list = explode(',', $tags);
			return $return . implode("&tag=", $list);
		} else {
			return $return . $tags;
		}
	}
}

function getHealthChecks($tags = null)
{
	if ($GLOBALS['homepageHealthChecksEnabled'] && !empty($GLOBALS['healthChecksToken']) && !empty($GLOBALS['healthChecksURL']) && qualifyRequest($GLOBALS['homepageHealthChecksAuth'])) {
		$api['content']['checks'] = array();
		$tags = ($tags) ? healthChecksTags($tags) : '';
		$healthChecks = explode(',', $GLOBALS['healthChecksToken']);
		foreach ($healthChecks as $token) {
			$url = qualifyURL($GLOBALS['healthChecksURL']) . '/' . $tags;
			try {
				$headers = array('X-Api-Key' => $token);
				$options = (localURL($url)) ? array('verify' => false) : array();
				$response = Requests::get($url, $headers, $options);
				if ($response->success) {
					$healthResults = json_decode($response->body, true);
					$api['content']['checks'] = array_merge($api['content']['checks'], $healthResults['checks']);
				}
			} catch (Requests_Exception $e) {
				writeLog('error', 'HealthChecks Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			};
		}
		usort($api['content']['checks'], function ($a, $b) {
			return $a['status'] <=> $b['status'];
		});
		$api['content']['checks'] = isset($api['content']['checks']) ? $api['content']['checks'] : false;
		return $api;
	}
	return false;
}

function streamType($value)
{
	if ($value == "transcode" || $value == "Transcode") {
		return "Transcode";
	} elseif ($value == "copy" || $value == "DirectStream") {
		return "Direct Stream";
	} elseif ($value == "directplay" || $value == "DirectPlay") {
		return "Direct Play";
	} else {
		return "Direct Play";
	}
}

function resolveEmbyItem($itemDetails)
{
	// Grab Each item info from Emby (extra call)
	$id = isset($itemDetails['NowPlayingItem']['Id']) ? $itemDetails['NowPlayingItem']['Id'] : $itemDetails['Id'];
	$url = qualifyURL($GLOBALS['embyURL']);
	$url = $url . '/Items?Ids=' . $id . '&api_key=' . $GLOBALS['embyToken'] . '&Fields=Overview,People,Genres,CriticRating,Studios,Taglines';
	try {
		$options = (localURL($url)) ? array('verify' => false) : array();
		$response = Requests::get($url, array(), $options);
		if ($response->success) {
			$item = json_decode($response->body, true)['Items'][0];
		}
	} catch (Requests_Exception $e) {
		return false;
	};
	// Static Height & Width
	$height = getCacheImageSize('h');
	$width = getCacheImageSize('w');
	$nowPlayingHeight = getCacheImageSize('nph');
	$nowPlayingWidth = getCacheImageSize('npw');
	$actorHeight = 450;
	$actorWidth = 300;
	// Cache Directories
	$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
	$cacheDirectoryWeb = 'plugins/images/cache/';
	// Types
	$embyItem['array-item'] = $item;
	$embyItem['array-itemdetails'] = $itemDetails;
	switch (@$item['Type']) {
		case 'Series':
			$embyItem['type'] = 'tv';
			$embyItem['title'] = $item['Name'];
			$embyItem['secondaryTitle'] = '';
			$embyItem['summary'] = '';
			$embyItem['ratingKey'] = $item['Id'];
			$embyItem['thumb'] = $item['Id'];
			$embyItem['key'] = $item['Id'] . "-list";
			$embyItem['nowPlayingThumb'] = $item['Id'];
			$embyItem['nowPlayingKey'] = $item['Id'] . "-np";
			$embyItem['metadataKey'] = $item['Id'];
			$embyItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? 'Thumb' : (isset($item['BackdropImageTags'][0]) ? 'Backdrop' : '');
			break;
		case 'Episode':
			$embyItem['type'] = 'tv';
			$embyItem['title'] = $item['SeriesName'];
			$embyItem['secondaryTitle'] = '';
			$embyItem['summary'] = '';
			$embyItem['ratingKey'] = $item['Id'];
			$embyItem['thumb'] = (isset($item['SeriesId']) ? $item['SeriesId'] : $item['Id']);
			$embyItem['key'] = (isset($item['SeriesId']) ? $item['SeriesId'] : $item['Id']) . "-list";
			$embyItem['nowPlayingThumb'] = isset($item['ParentThumbItemId']) ? $item['ParentThumbItemId'] : (isset($item['ParentBackdropItemId']) ? $item['ParentBackdropItemId'] : false);
			$embyItem['nowPlayingKey'] = isset($item['ParentThumbItemId']) ? $item['ParentThumbItemId'] . '-np' : (isset($item['ParentBackdropItemId']) ? $item['ParentBackdropItemId'] . '-np' : false);
			$embyItem['metadataKey'] = $item['Id'];
			$embyItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? 'Thumb' : (isset($item['ParentBackdropImageTags'][0]) ? 'Backdrop' : '');
			$embyItem['nowPlayingTitle'] = @$item['SeriesName'] . ' - ' . @$item['Name'];
			$embyItem['nowPlayingBottom'] = 'S' . @$item['ParentIndexNumber'] . ' · E' . @$item['IndexNumber'];
			break;
		case 'MusicAlbum':
		case 'Audio':
			$embyItem['type'] = 'music';
			$embyItem['title'] = $item['Name'];
			$embyItem['secondaryTitle'] = '';
			$embyItem['summary'] = '';
			$embyItem['ratingKey'] = $item['Id'];
			$embyItem['thumb'] = $item['Id'];
			$embyItem['key'] = $item['Id'] . "-list";
			$embyItem['nowPlayingThumb'] = (isset($item['AlbumId']) ? $item['AlbumId'] : @$item['ParentBackdropItemId']);
			$embyItem['nowPlayingKey'] = $item['Id'] . "-np";
			$embyItem['metadataKey'] = isset($item['AlbumId']) ? $item['AlbumId'] : $item['Id'];
			$embyItem['nowPlayingImageType'] = (isset($item['ParentBackdropItemId']) ? "Primary" : "Backdrop");
			$embyItem['nowPlayingTitle'] = @$item['AlbumArtist'] . ' - ' . @$item['Name'];
			$embyItem['nowPlayingBottom'] = @$item['Album'];
			break;
		case 'Movie':
			$embyItem['type'] = 'movie';
			$embyItem['title'] = $item['Name'];
			$embyItem['secondaryTitle'] = '';
			$embyItem['summary'] = '';
			$embyItem['ratingKey'] = $item['Id'];
			$embyItem['thumb'] = $item['Id'];
			$embyItem['key'] = $item['Id'] . "-list";
			$embyItem['nowPlayingThumb'] = $item['Id'];
			$embyItem['nowPlayingKey'] = $item['Id'] . "-np";
			$embyItem['metadataKey'] = $item['Id'];
			$embyItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? "Thumb" : (isset($item['BackdropImageTags']) ? "Backdrop" : false);
			$embyItem['nowPlayingTitle'] = @$item['Name'];
			$embyItem['nowPlayingBottom'] = @$item['ProductionYear'];
			break;
		case 'Video':
			$embyItem['type'] = 'video';
			$embyItem['title'] = $item['Name'];
			$embyItem['secondaryTitle'] = '';
			$embyItem['summary'] = '';
			$embyItem['ratingKey'] = $item['Id'];
			$embyItem['thumb'] = $item['Id'];
			$embyItem['key'] = $item['Id'] . "-list";
			$embyItem['nowPlayingThumb'] = $item['Id'];
			$embyItem['nowPlayingKey'] = $item['Id'] . "-np";
			$embyItem['metadataKey'] = $item['Id'];
			$embyItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? "Thumb" : (isset($item['BackdropImageTags']) ? "Backdrop" : false);
			$embyItem['nowPlayingTitle'] = @$item['Name'];
			$embyItem['nowPlayingBottom'] = @$item['ProductionYear'];
			break;
		default:
			return false;
	}
	$embyItem['uid'] = $item['Id'];
	$embyItem['imageType'] = (isset($item['ImageTags']['Primary']) ? "Primary" : false);
	$embyItem['elapsed'] = isset($itemDetails['PlayState']['PositionTicks']) && $itemDetails['PlayState']['PositionTicks'] !== '0' ? (int)$itemDetails['PlayState']['PositionTicks'] : null;
	$embyItem['duration'] = isset($itemDetails['NowPlayingItem']['RunTimeTicks']) ? (int)$itemDetails['NowPlayingItem']['RunTimeTicks'] : (int)(isset($item['RunTimeTicks']) ? $item['RunTimeTicks'] : '');
	$embyItem['watched'] = ($embyItem['elapsed'] && $embyItem['duration'] ? floor(($embyItem['elapsed'] / $embyItem['duration']) * 100) : 0);
	$embyItem['transcoded'] = isset($itemDetails['TranscodingInfo']['CompletionPercentage']) ? floor((int)$itemDetails['TranscodingInfo']['CompletionPercentage']) : 100;
	$embyItem['stream'] = @$itemDetails['PlayState']['PlayMethod'];
	$embyItem['id'] = $item['ServerId'];
	$embyItem['session'] = @$itemDetails['DeviceId'];
	$embyItem['bandwidth'] = isset($itemDetails['TranscodingInfo']['Bitrate']) ? $itemDetails['TranscodingInfo']['Bitrate'] / 1000 : '';
	$embyItem['bandwidthType'] = 'wan';
	$embyItem['sessionType'] = (@$itemDetails['PlayState']['PlayMethod'] == 'Transcode') ? 'Transcoding' : 'Direct Playing';
	$embyItem['state'] = ((@(string)$itemDetails['PlayState']['IsPaused'] == '1') ? "pause" : "play");
	$embyItem['user'] = ($GLOBALS['homepageShowStreamNames'] && qualifyRequest($GLOBALS['homepageShowStreamNamesAuth'])) ? @(string)$itemDetails['UserName'] : "";
	$embyItem['userThumb'] = '';
	$embyItem['userAddress'] = (isset($itemDetails['RemoteEndPoint']) ? $itemDetails['RemoteEndPoint'] : "x.x.x.x");
	$embyItem['address'] = $GLOBALS['embyTabURL'] ? rtrim($GLOBALS['embyTabURL'], '/') . "/web/#!/itemdetails.html?id=" . $embyItem['uid'] : "https://app.emby.media/#!/itemdetails.html?id=" . $embyItem['uid'] . "&serverId=" . $embyItem['id'];
	$embyItem['nowPlayingOriginalImage'] = 'api/?v1/image&source=emby&type=' . $embyItem['nowPlayingImageType'] . '&img=' . $embyItem['nowPlayingThumb'] . '&height=' . $nowPlayingHeight . '&width=' . $nowPlayingWidth . '&key=' . $embyItem['nowPlayingKey'] . '$' . randString();
	$embyItem['originalImage'] = 'api/?v1/image&source=emby&type=' . $embyItem['imageType'] . '&img=' . $embyItem['thumb'] . '&height=' . $height . '&width=' . $width . '&key=' . $embyItem['key'] . '$' . randString();
	$embyItem['openTab'] = $GLOBALS['embyTabURL'] && $GLOBALS['embyTabName'] ? true : false;
	$embyItem['tabName'] = $GLOBALS['embyTabName'] ? $GLOBALS['embyTabName'] : '';
	// Stream info
	$embyItem['userStream'] = array(
		'platform' => @(string)$itemDetails['Client'],
		'product' => @(string)$itemDetails['Client'],
		'device' => @(string)$itemDetails['DeviceName'],
		'stream' => @$itemDetails['PlayState']['PlayMethod'],
		'videoResolution' => isset($itemDetails['NowPlayingItem']['MediaStreams'][0]['Width']) ? $itemDetails['NowPlayingItem']['MediaStreams'][0]['Width'] : '',
		'throttled' => false,
		'sourceVideoCodec' => isset($itemDetails['NowPlayingItem']['MediaStreams'][0]) ? $itemDetails['NowPlayingItem']['MediaStreams'][0]['Codec'] : '',
		'videoCodec' => @$itemDetails['TranscodingInfo']['VideoCodec'],
		'audioCodec' => @$itemDetails['TranscodingInfo']['AudioCodec'],
		'sourceAudioCodec' => isset($itemDetails['NowPlayingItem']['MediaStreams'][1]) ? $itemDetails['NowPlayingItem']['MediaStreams'][1]['Codec'] : (isset($itemDetails['NowPlayingItem']['MediaStreams'][0]) ? $itemDetails['NowPlayingItem']['MediaStreams'][0]['Codec'] : ''),
		'videoDecision' => streamType(@$itemDetails['PlayState']['PlayMethod']),
		'audioDecision' => streamType(@$itemDetails['PlayState']['PlayMethod']),
		'container' => isset($itemDetails['NowPlayingItem']['Container']) ? $itemDetails['NowPlayingItem']['Container'] : '',
		'audioChannels' => @$itemDetails['TranscodingInfo']['AudioChannels']
	);
	// Genre catch all
	if (isset($item['Genres'])) {
		$genres = array();
		foreach ($item['Genres'] as $genre) {
			$genres[] = $genre;
		}
	}
	// Actor catch all
	if (isset($item['People'])) {
		$actors = array();
		foreach ($item['People'] as $key => $value) {
			if (@$value['PrimaryImageTag'] && @$value['Role']) {
				if (file_exists($cacheDirectory . (string)$value['Id'] . '-cast.jpg')) {
					$actorImage = $cacheDirectoryWeb . (string)$value['Id'] . '-cast.jpg';
				}
				if (file_exists($cacheDirectory . (string)$value['Id'] . '-cast.jpg') && (time() - 604800) > filemtime($cacheDirectory . (string)$value['Id'] . '-cast.jpg') || !file_exists($cacheDirectory . (string)$value['Id'] . '-cast.jpg')) {
					$actorImage = 'api/?v1/image&source=emby&type=Primary&img=' . (string)$value['Id'] . '&height=' . $actorHeight . '&width=' . $actorWidth . '&key=' . (string)$value['Id'] . '-cast';
				}
				$actors[] = array(
					'name' => (string)$value['Name'],
					'role' => (string)$value['Role'],
					'thumb' => $actorImage
				);
			}
		}
	}
	// Metadata information
	$embyItem['metadata'] = array(
		'guid' => $item['Id'],
		'summary' => @(string)$item['Overview'],
		'rating' => @(string)$item['CommunityRating'],
		'duration' => @(string)$item['RunTimeTicks'],
		'originallyAvailableAt' => @(string)$item['PremiereDate'],
		'year' => (string)isset($item['ProductionYear']) ? $item['ProductionYear'] : '',
		//'studio' => (string)$item['studio'],
		'tagline' => @(string)$item['Taglines'][0],
		'genres' => (isset($item['Genres'])) ? $genres : '',
		'actors' => (isset($item['People'])) ? $actors : ''
	);
	if (file_exists($cacheDirectory . $embyItem['nowPlayingKey'] . '.jpg')) {
		$embyItem['nowPlayingImageURL'] = $cacheDirectoryWeb . $embyItem['nowPlayingKey'] . '.jpg';
	}
	if (file_exists($cacheDirectory . $embyItem['key'] . '.jpg')) {
		$embyItem['imageURL'] = $cacheDirectoryWeb . $embyItem['key'] . '.jpg';
	}
	if (file_exists($cacheDirectory . $embyItem['nowPlayingKey'] . '.jpg') && (time() - 604800) > filemtime($cacheDirectory . $embyItem['nowPlayingKey'] . '.jpg') || !file_exists($cacheDirectory . $embyItem['nowPlayingKey'] . '.jpg')) {
		$embyItem['nowPlayingImageURL'] = 'api/?v1/image&source=emby&type=' . $embyItem['nowPlayingImageType'] . '&img=' . $embyItem['nowPlayingThumb'] . '&height=' . $nowPlayingHeight . '&width=' . $nowPlayingWidth . '&key=' . $embyItem['nowPlayingKey'] . '';
	}
	if (file_exists($cacheDirectory . $embyItem['key'] . '.jpg') && (time() - 604800) > filemtime($cacheDirectory . $embyItem['key'] . '.jpg') || !file_exists($cacheDirectory . $embyItem['key'] . '.jpg')) {
		$embyItem['imageURL'] = 'api/?v1/image&source=emby&type=' . $embyItem['imageType'] . '&img=' . $embyItem['thumb'] . '&height=' . $height . '&width=' . $width . '&key=' . $embyItem['key'] . '';
	}
	if (!$embyItem['nowPlayingThumb']) {
		$embyItem['nowPlayingOriginalImage'] = $embyItem['nowPlayingImageURL'] = "plugins/images/cache/no-np.png";
		$embyItem['nowPlayingKey'] = "no-np";
	}
	if (!$embyItem['thumb']) {
		$embyItem['originalImage'] = $embyItem['imageURL'] = "plugins/images/cache/no-list.png";
		$embyItem['key'] = "no-list";
	}
	if (isset($useImage)) {
		$embyItem['useImage'] = $useImage;
	}
	return $embyItem;
}

function getCacheImageSize($type)
{
	switch ($type) {
		case 'height':
		case 'h':
			return 300 * $GLOBALS['cacheImageSize'];
			break;
		case 'width':
		case 'w':
			return 200 * $GLOBALS['cacheImageSize'];
			break;
		case 'nowPlayingHeight':
		case 'nph':
			return 675 * $GLOBALS['cacheImageSize'];
			break;
		case 'nowPlayingWidth':
		case 'npw':
			return 1200 * $GLOBALS['cacheImageSize'];
			break;
	}
}

function resolvePlexItem($item)
{
	// Static Height & Width
	$height = getCacheImageSize('h');
	$width = getCacheImageSize('w');
	$nowPlayingHeight = getCacheImageSize('nph');
	$nowPlayingWidth = getCacheImageSize('npw');
	// Cache Directories
	$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
	$cacheDirectoryWeb = 'plugins/images/cache/';
	// Types
	switch ($item['type']) {
		case 'season':
			$plexItem['type'] = 'tv';
			$plexItem['title'] = (string)$item['parentTitle'];
			$plexItem['secondaryTitle'] = (string)$item['title'];
			$plexItem['summary'] = (string)$item['parentSummary'];
			$plexItem['ratingKey'] = (string)$item['parentRatingKey'];
			$plexItem['thumb'] = (string)$item['thumb'];
			$plexItem['key'] = (string)$item['ratingKey'] . "-list";
			$plexItem['nowPlayingThumb'] = (string)$item['art'];
			$plexItem['nowPlayingKey'] = (string)$item['ratingKey'] . "-np";
			$plexItem['metadataKey'] = (string)$item['parentRatingKey'];
			break;
		case 'episode':
			$plexItem['type'] = 'tv';
			$plexItem['title'] = (string)$item['grandparentTitle'];
			$plexItem['secondaryTitle'] = (string)$item['parentTitle'];
			$plexItem['summary'] = (string)$item['title'];
			$plexItem['ratingKey'] = (string)$item['parentRatingKey'];
			$plexItem['thumb'] = ($item['parentThumb'] ? (string)$item['parentThumb'] : (string)$item['grandparentThumb']);
			$plexItem['key'] = (string)$item['ratingKey'] . "-list";
			$plexItem['nowPlayingThumb'] = (string)$item['grandparentArt'];
			$plexItem['nowPlayingKey'] = (string)$item['grandparentRatingKey'] . "-np";
			$plexItem['nowPlayingTitle'] = (string)$item['grandparentTitle'] . ' - ' . (string)$item['title'];
			$plexItem['nowPlayingBottom'] = 'S' . (string)$item['parentIndex'] . ' · E' . (string)$item['index'];
			$plexItem['metadataKey'] = (string)$item['grandparentRatingKey'];
			break;
		case 'clip':
			$useImage = (isset($item['live']) ? "plugins/images/cache/livetv.png" : null);
			$plexItem['type'] = 'clip';
			$plexItem['title'] = (isset($item['live']) ? 'Live TV' : (string)$item['title']);
			$plexItem['secondaryTitle'] = '';
			$plexItem['summary'] = (string)$item['summary'];
			$plexItem['ratingKey'] = (string)$item['parentRatingKey'];
			$plexItem['thumb'] = (string)$item['thumb'];
			$plexItem['key'] = (string)$item['ratingKey'] . "-list";
			$plexItem['nowPlayingThumb'] = (string)$item['art'];
			$plexItem['nowPlayingKey'] = isset($item['ratingKey']) ? (string)$item['ratingKey'] . "-np" : (isset($item['live']) ? "livetv.png" : ":)");
			$plexItem['nowPlayingTitle'] = $plexItem['title'];
			$plexItem['nowPlayingBottom'] = isset($item['extraType']) ? "Trailer" : (isset($item['live']) ? "Live TV" : ":)");
			break;
		case 'album':
		case 'track':
			$plexItem['type'] = 'music';
			$plexItem['title'] = (string)$item['parentTitle'];
			$plexItem['secondaryTitle'] = (string)$item['title'];
			$plexItem['summary'] = (string)$item['title'];
			$plexItem['ratingKey'] = (string)$item['parentRatingKey'];
			$plexItem['thumb'] = (string)$item['thumb'];
			$plexItem['key'] = (string)$item['ratingKey'] . "-list";
			$plexItem['nowPlayingThumb'] = ($item['parentThumb']) ? (string)$item['parentThumb'] : (string)$item['art'];
			$plexItem['nowPlayingKey'] = (string)$item['parentRatingKey'] . "-np";
			$plexItem['nowPlayingTitle'] = (string)$item['grandparentTitle'] . ' - ' . (string)$item['title'];
			$plexItem['nowPlayingBottom'] = (string)$item['parentTitle'];
			$plexItem['metadataKey'] = isset($item['grandparentRatingKey']) ? (string)$item['grandparentRatingKey'] : (string)$item['parentRatingKey'];
			break;
		default:
			$plexItem['type'] = 'movie';
			$plexItem['title'] = (string)$item['title'];
			$plexItem['secondaryTitle'] = (string)$item['year'];
			$plexItem['summary'] = (string)$item['summary'];
			$plexItem['ratingKey'] = (string)$item['ratingKey'];
			$plexItem['thumb'] = (string)$item['thumb'];
			$plexItem['key'] = (string)$item['ratingKey'] . "-list";
			$plexItem['nowPlayingThumb'] = (string)$item['art'];
			$plexItem['nowPlayingKey'] = (string)$item['ratingKey'] . "-np";
			$plexItem['nowPlayingTitle'] = (string)$item['title'];
			$plexItem['nowPlayingBottom'] = (string)$item['year'];
			$plexItem['metadataKey'] = (string)$item['ratingKey'];
	}
	$plexItem['uid'] = (string)$item['ratingKey'];
	$plexItem['elapsed'] = isset($item['viewOffset']) && $item['viewOffset'] !== '0' ? (int)$item['viewOffset'] : null;
	$plexItem['duration'] = isset($item['duration']) ? (int)$item['duration'] : (int)$item->Media['duration'];
	$plexItem['addedAt'] = isset($item['addedAt']) ? (int)$item['addedAt'] : null;
	$plexItem['watched'] = ($plexItem['elapsed'] && $plexItem['duration'] ? floor(($plexItem['elapsed'] / $plexItem['duration']) * 100) : 0);
	$plexItem['transcoded'] = isset($item->TranscodeSession['progress']) ? floor((int)$item->TranscodeSession['progress'] - $plexItem['watched']) : '';
	$plexItem['stream'] = isset($item->Media->Part->Stream['decision']) ? (string)$item->Media->Part->Stream['decision'] : '';
	$plexItem['id'] = str_replace('"', '', (string)$item->Player['machineIdentifier']);
	$plexItem['session'] = (string)$item->Session['id'];
	$plexItem['bandwidth'] = (string)$item->Session['bandwidth'];
	$plexItem['bandwidthType'] = (string)$item->Session['location'];
	$plexItem['sessionType'] = isset($item->TranscodeSession['progress']) ? 'Transcoding' : 'Direct Playing';
	$plexItem['state'] = (((string)$item->Player['state'] == "paused") ? "pause" : "play");
	$plexItem['user'] = ($GLOBALS['homepageShowStreamNames'] && qualifyRequest($GLOBALS['homepageShowStreamNamesAuth'])) ? (string)$item->User['title'] : "";
	$plexItem['userThumb'] = ($GLOBALS['homepageShowStreamNames'] && qualifyRequest($GLOBALS['homepageShowStreamNamesAuth'])) ? (string)$item->User['thumb'] : "";
	$plexItem['userAddress'] = ($GLOBALS['homepageShowStreamNames'] && qualifyRequest($GLOBALS['homepageShowStreamNamesAuth'])) ? (string)$item->Player['address'] : "x.x.x.x";
	$plexItem['address'] = $GLOBALS['plexTabURL'] ? $GLOBALS['plexTabURL'] . "/web/index.html#!/server/" . $GLOBALS['plexID'] . "/details?key=/library/metadata/" . $item['ratingKey'] : "https://app.plex.tv/web/app#!/server/" . $GLOBALS['plexID'] . "/details?key=/library/metadata/" . $item['ratingKey'];
	$plexItem['nowPlayingOriginalImage'] = 'api/?v1/image&source=plex&img=' . $plexItem['nowPlayingThumb'] . '&height=' . $nowPlayingHeight . '&width=' . $nowPlayingWidth . '&key=' . $plexItem['nowPlayingKey'] . '$' . randString();
	$plexItem['originalImage'] = 'api/?v1/image&source=plex&img=' . $plexItem['thumb'] . '&height=' . $height . '&width=' . $width . '&key=' . $plexItem['key'] . '$' . randString();
	$plexItem['openTab'] = $GLOBALS['plexTabURL'] && $GLOBALS['plexTabName'] ? true : false;
	$plexItem['tabName'] = $GLOBALS['plexTabName'] ? $GLOBALS['plexTabName'] : '';
	// Stream info
	$plexItem['userStream'] = array(
		'platform' => (string)$item->Player['platform'],
		'product' => (string)$item->Player['product'],
		'device' => (string)$item->Player['device'],
		'stream' => (string)$item->Media->Part['decision'] . ($item->TranscodeSession['throttled'] == '1' ? ' (Throttled)' : ''),
		'videoResolution' => (string)$item->Media['videoResolution'],
		'throttled' => ($item->TranscodeSession['throttled'] == 1) ? true : false,
		'sourceVideoCodec' => (string)$item->TranscodeSession['sourceVideoCodec'],
		'videoCodec' => (string)$item->TranscodeSession['videoCodec'],
		'audioCodec' => (string)$item->TranscodeSession['audioCodec'],
		'sourceAudioCodec' => (string)$item->TranscodeSession['sourceAudioCodec'],
		'videoDecision' => streamType((string)$item->TranscodeSession['videoDecision']),
		'audioDecision' => streamType((string)$item->TranscodeSession['audioDecision']),
		'container' => (string)$item->TranscodeSession['container'],
		'audioChannels' => (string)$item->TranscodeSession['audioChannels']
	);
	// Genre catch all
	if ($item->Genre) {
		$genres = array();
		foreach ($item->Genre as $key => $value) {
			$genres[] = (string)$value['tag'];
		}
	}
	// Actor catch all
	if ($item->Role) {
		$actors = array();
		foreach ($item->Role as $key => $value) {
			if ($value['thumb']) {
				$actors[] = array(
					'name' => (string)$value['tag'],
					'role' => (string)$value['role'],
					'thumb' => (string)$value['thumb']
				);
			}
		}
	}
	// Metadata information
	$plexItem['metadata'] = array(
		'guid' => (string)$item['guid'],
		'summary' => (string)$item['summary'],
		'rating' => (string)$item['rating'],
		'duration' => (string)$item['duration'],
		'originallyAvailableAt' => (string)$item['originallyAvailableAt'],
		'year' => (string)$item['year'],
		'studio' => (string)$item['studio'],
		'tagline' => (string)$item['tagline'],
		'genres' => ($item->Genre) ? $genres : '',
		'actors' => ($item->Role) ? $actors : ''
	);
	if (file_exists($cacheDirectory . $plexItem['nowPlayingKey'] . '.jpg')) {
		$plexItem['nowPlayingImageURL'] = $cacheDirectoryWeb . $plexItem['nowPlayingKey'] . '.jpg';
	}
	if (file_exists($cacheDirectory . $plexItem['key'] . '.jpg')) {
		$plexItem['imageURL'] = $cacheDirectoryWeb . $plexItem['key'] . '.jpg';
	}
	if (file_exists($cacheDirectory . $plexItem['nowPlayingKey'] . '.jpg') && (time() - 604800) > filemtime($cacheDirectory . $plexItem['nowPlayingKey'] . '.jpg') || !file_exists($cacheDirectory . $plexItem['nowPlayingKey'] . '.jpg')) {
		$plexItem['nowPlayingImageURL'] = 'api/?v1/image&source=plex&img=' . $plexItem['nowPlayingThumb'] . '&height=' . $nowPlayingHeight . '&width=' . $nowPlayingWidth . '&key=' . $plexItem['nowPlayingKey'] . '';
	}
	if (file_exists($cacheDirectory . $plexItem['key'] . '.jpg') && (time() - 604800) > filemtime($cacheDirectory . $plexItem['key'] . '.jpg') || !file_exists($cacheDirectory . $plexItem['key'] . '.jpg')) {
		$plexItem['imageURL'] = 'api/?v1/image&source=plex&img=' . $plexItem['thumb'] . '&height=' . $height . '&width=' . $width . '&key=' . $plexItem['key'] . '';
	}
	if (!$plexItem['nowPlayingThumb']) {
		$plexItem['nowPlayingOriginalImage'] = $plexItem['nowPlayingImageURL'] = "plugins/images/cache/no-np.png";
		$plexItem['nowPlayingKey'] = "no-np";
	}
	if (!$plexItem['thumb']) {
		$plexItem['originalImage'] = $plexItem['imageURL'] = "plugins/images/cache/no-list.png";
		$plexItem['key'] = "no-list";
	}
	if (isset($useImage)) {
		$plexItem['useImage'] = $useImage;
	}
	return $plexItem;
}

function plexConnect($action, $key = null)
{
	if ($GLOBALS['homepagePlexEnabled'] && !empty($GLOBALS['plexURL']) && !empty($GLOBALS['plexToken']) && !empty($GLOBALS['plexID'] && qualifyRequest($GLOBALS['homepagePlexAuth']))) {
		$url = qualifyURL($GLOBALS['plexURL']);
		$multipleURL = false;
		$ignore = array();
		$resolve = true;
		switch ($action) {
			case 'streams':
				$url = $url . "/status/sessions?X-Plex-Token=" . $GLOBALS['plexToken'];
				break;
			case 'libraries':
				$url = $url . "/library/sections?X-Plex-Token=" . $GLOBALS['plexToken'];
				$resolve = false;
				break;
			case 'recent':
				//$url = $url . "/library/recentlyAdded?X-Plex-Token=" . $GLOBALS['plexToken'] . "&limit=" . $GLOBALS['homepageRecentLimit'];
				$urls['movie'] = $url . "/hubs/home/recentlyAdded?X-Plex-Token=" . $GLOBALS['plexToken'] . "&X-Plex-Container-Start=0&X-Plex-Container-Size=" . $GLOBALS['homepageRecentLimit'] . "&type=1";
				$urls['tv'] = $url . "/hubs/home/recentlyAdded?X-Plex-Token=" . $GLOBALS['plexToken'] . "&X-Plex-Container-Start=0&X-Plex-Container-Size=" . $GLOBALS['homepageRecentLimit'] . "&type=2";
				$urls['music'] = $url . "/hubs/home/recentlyAdded?X-Plex-Token=" . $GLOBALS['plexToken'] . "&X-Plex-Container-Start=0&X-Plex-Container-Size=" . $GLOBALS['homepageRecentLimit'] . "&type=8";
				$multipleURL = true;
				break;
			case 'metadata':
				$url = $url . "/library/metadata/" . $key . "?X-Plex-Token=" . $GLOBALS['plexToken'];
				break;
			case 'playlists':
				$url = $url . "/playlists?X-Plex-Token=" . $GLOBALS['plexToken'];
				break;
			case 'search':
				$url = $url . "/search?query=" . rawurlencode($key) . "&X-Plex-Token=" . $GLOBALS['plexToken'];
				$ignore = array('artist', 'episode');
				break;
			default:
				# code...
				break;
		}
		try {
			if (!$multipleURL) {
				$options = (localURL($url)) ? array('verify' => false) : array();
				$response = Requests::get($url, array(), $options);
				libxml_use_internal_errors(true);
				if ($response->success) {
					$items = array();
					$plex = simplexml_load_string($response->body);
					foreach ($plex as $child) {
						if (!in_array($child['type'], $ignore) && isset($child['librarySectionID'])) {
							$items[] = resolvePlexItem($child);
						}
					}
					$api['content'] = ($resolve) ? $items : $plex;
					$api['plexID'] = $GLOBALS['plexID'];
					$api['showNames'] = true;
					$api['group'] = '1';
					return $api;
				}
			} else {
				foreach ($urls as $k => $v) {
					$options = (localURL($v)) ? array('verify' => false) : array();
					$response = Requests::get($v, array(), $options);
					libxml_use_internal_errors(true);
					if ($response->success) {
						$items = array();
						$plex = simplexml_load_string($response->body);
						foreach ($plex as $child) {
							if (!in_array($child['type'], $ignore) && isset($child['librarySectionID'])) {
								$items[] = resolvePlexItem($child);
							}
						}
						if (isset($api)) {
							$api['content'] = array_merge($api['content'], ($resolve) ? $items : $plex);
						} else {
							$api['content'] = ($resolve) ? $items : $plex;
						}
					}
				}
				if (isset($api['content'])) {
					usort($api['content'], function ($a, $b) {
						return $b['addedAt'] <=> $a['addedAt'];
					});
				}
				$api['plexID'] = $GLOBALS['plexID'];
				$api['showNames'] = true;
				$api['group'] = '1';
				return $api;
			}
		} catch (Requests_Exception $e) {
			writeLog('error', 'Plex Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		};
	}
	return false;
}

function getPlexPlaylists()
{
	if ($GLOBALS['homepagePlexEnabled'] && !empty($GLOBALS['plexURL']) && !empty($GLOBALS['plexToken']) && !empty($GLOBALS['plexID'] && qualifyRequest($GLOBALS['homepagePlexAuth']) && qualifyRequest($GLOBALS['homepagePlexPlaylistAuth']) && $GLOBALS['homepagePlexPlaylist'])) {
		$url = qualifyURL($GLOBALS['plexURL']);
		$url = $url . "/playlists?X-Plex-Token=" . $GLOBALS['plexToken'];
		try {
			$options = (localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			libxml_use_internal_errors(true);
			if ($response->success) {
				$items = array();
				$plex = simplexml_load_string($response->body);
				foreach ($plex as $child) {
					if ($child['playlistType'] == "video" && strpos(strtolower($child['title']), 'private') === false) {
						$playlistTitleClean = preg_replace("/(\W)+/", "", (string)$child['title']);
						$playlistURL = qualifyURL($GLOBALS['plexURL']);
						$playlistURL = $playlistURL . $child['key'] . "?X-Plex-Token=" . $GLOBALS['plexToken'];
						$options = (localURL($url)) ? array('verify' => false) : array();
						$playlistResponse = Requests::get($playlistURL, array(), $options);
						if ($playlistResponse->success) {
							$playlistResponse = simplexml_load_string($playlistResponse->body);
							$items[$playlistTitleClean]['title'] = (string)$child['title'];
							foreach ($playlistResponse->Video as $playlistItem) {
								$items[$playlistTitleClean][] = resolvePlexItem($playlistItem);
							}
						}
					}
				}
				$api['content'] = $items;
				$api['plexID'] = $GLOBALS['plexID'];
				$api['showNames'] = true;
				$api['group'] = '1';
				return $api;
			}
		} catch (Requests_Exception $e) {
			writeLog('error', 'Plex Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		};
	}
	return false;
}

function embyConnect($action, $key = null, $skip = false)
{
	if ($GLOBALS['homepageEmbyEnabled'] && !empty($GLOBALS['embyURL']) && !empty($GLOBALS['embyToken']) && qualifyRequest($GLOBALS['homepageEmbyAuth'])) {
		$url = qualifyURL($GLOBALS['embyURL']);
		switch ($action) {
			case 'streams':
				$url = $url . '/Sessions?api_key=' . $GLOBALS['embyToken'];
				break;
			case 'recent':
				$username = false;
				if (isset($GLOBALS['organizrUser']['username'])) {
					$username = strtolower($GLOBALS['organizrUser']['username']);
				}
				// Get A User
				$userIds = $url . "/Users?api_key=" . $GLOBALS['embyToken'];
				$showPlayed = true;
				try {
					$options = (localURL($userIds)) ? array('verify' => false) : array();
					$response = Requests::get($userIds, array(), $options);
					if ($response->success) {
						$emby = json_decode($response->body, true);
						foreach ($emby as $value) { // Scan for admin user
							if (isset($value['Policy']) && isset($value['Policy']['IsAdministrator']) && $value['Policy']['IsAdministrator']) {
								$userId = $value['Id'];
							}
							if ($username && strtolower($value['Name']) == $username) {
								$userId = $value['Id'];
								$showPlayed = false;
								break;
							}
						}
						$url = $url . '/Users/' . $userId . '/Items/Latest?EnableImages=false&Limit=' . $GLOBALS['homepageRecentLimit'] . '&api_key=' . $GLOBALS['embyToken'] . ($showPlayed ? '' : '&IsPlayed=false');
					}
				} catch (Requests_Exception $e) {
					writeLog('error', 'Emby Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
				};
				break;
			case 'metadata':
				$skip = true;
				break;
			default:
				# code...
				break;
		}
		if ($skip && $key) {
			$items[] = resolveEmbyItem(array('Id' => $key));
			$api['content'] = $items;
			return $api;
		}
		try {
			$options = (localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$items = array();
				$emby = json_decode($response->body, true);
				foreach ($emby as $child) {
					if (isset($child['NowPlayingItem']) || isset($child['Name'])) {
						$items[] = resolveEmbyItem($child);
					}
				}
				$api['content'] = array_filter($items);
				return $api;
			}
		} catch (Requests_Exception $e) {
			writeLog('error', 'Emby Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		};
	}
	return false;
}

function jdownloaderConnect()
{
    if ($GLOBALS['homepageJdownloaderEnabled'] && !empty($GLOBALS['jdownloaderURL']) && qualifyRequest($GLOBALS['homepageJdownloaderAuth'])) {
        $url = qualifyURL($GLOBALS['jdownloaderURL']);
        $url = $url . '/';
        try {
            $options = (localURL($url)) ? array('verify' => false) : array();
            $response = Requests::get($url, array(), $options);
            if ($response->success) {
                $temp = json_decode($response->body, true);
                $packages = $temp['packages'];
                if ($packages['downloader']) {
                    $api['content']['queueItems'] = $packages['downloader'];
                } else {
                    $api['content']['queueItems'] = [];
                }
                $grabbed = array();
                if ($packages['linkgrabber_decrypted']) {
                    $grabbed = array_merge($grabbed, $packages['linkgrabber_decrypted']);
                }
                if ($packages['linkgrabber_failed']) {
                    $grabbed = array_merge($grabbed, $packages['linkgrabber_failed']);
                }
                if ($packages['linkgrabber_offline']) {
                    $grabbed = array_merge($grabbed, $packages['linkgrabber_offline']);
                }
                $api['content']['grabberItems'] = $grabbed;

                $status = array($temp['downloader_state'], $temp['grabber_collecting'], $temp['update_ready']);
                $api['content']['$status'] = $status;
            }
        } catch (Requests_Exception $e) {
            writeLog('error', 'JDownloader Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
        };
        $api['content'] = isset($api['content']) ? $api['content'] : false;
        return $api;
    }
    return false;
}

function sabnzbdConnect()
{
	if ($GLOBALS['homepageSabnzbdEnabled'] && !empty($GLOBALS['sabnzbdURL']) && !empty($GLOBALS['sabnzbdToken']) && qualifyRequest($GLOBALS['homepageSabnzbdAuth'])) {
		$url = qualifyURL($GLOBALS['sabnzbdURL']);
		$url = $url . '/api?mode=queue&output=json&apikey=' . $GLOBALS['sabnzbdToken'];
		try {
			$options = (localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$api['content']['queueItems'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			writeLog('error', 'SabNZBd Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		};
		$url = qualifyURL($GLOBALS['sabnzbdURL']);
		$url = $url . '/api?mode=history&output=json&limit=100&apikey=' . $GLOBALS['sabnzbdToken'];
		try {
			$options = (localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$api['content']['historyItems'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			writeLog('error', 'SabNZBd Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		return $api;
	}
	return false;
}

function nzbgetConnect()
{
	if ($GLOBALS['homepageNzbgetEnabled'] && !empty($GLOBALS['nzbgetURL']) && qualifyRequest($GLOBALS['homepageNzbgetAuth'])) {
		$url = qualifyURL($GLOBALS['nzbgetURL']);
		if (!empty($GLOBALS['nzbgetUsername']) && !empty($GLOBALS['nzbgetPassword'])) {
			$url = $url . '/' . $GLOBALS['nzbgetUsername'] . ':' . decrypt($GLOBALS['nzbgetPassword']) . '/jsonrpc/listgroups';
		} else {
			$url = $url . '/jsonrpc/listgroups';
		}
		try {
			$options = (localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$api['content']['queueItems'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			writeLog('error', 'NZBGet Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		};
		$url = qualifyURL($GLOBALS['nzbgetURL']);
		if (!empty($GLOBALS['nzbgetUsername']) && !empty($GLOBALS['nzbgetPassword'])) {
			$url = $url . '/' . $GLOBALS['nzbgetUsername'] . ':' . decrypt($GLOBALS['nzbgetPassword']) . '/jsonrpc/history';
		} else {
			$url = $url . '/jsonrpc/history';
		}
		try {
			$options = (localURL($url)) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$api['content']['historyItems'] = json_decode($response->body, true);
			}
		} catch (Requests_Exception $e) {
			writeLog('error', 'NZBGet Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		return $api;
	}
	return false;
}

function transmissionConnect()
{
	if ($GLOBALS['homepageTransmissionEnabled'] && !empty($GLOBALS['transmissionURL']) && qualifyRequest($GLOBALS['homepageTransmissionAuth'])) {
		$digest = qualifyURL($GLOBALS['transmissionURL'], true);
		$passwordInclude = ($GLOBALS['transmissionUsername'] != '' && $GLOBALS['transmissionPassword'] != '') ? $GLOBALS['transmissionUsername'] . ':' . decrypt($GLOBALS['transmissionPassword']) . "@" : '';
		$url = $digest['scheme'] . '://' . $passwordInclude . $digest['host'] . $digest['port'] . $digest['path'] . '/rpc';
		try {
			$options = (localURL($GLOBALS['transmissionURL'])) ? array('verify' => false) : array();
			$response = Requests::get($url, array(), $options);
			if ($response->headers['x-transmission-session-id']) {
				$headers = array(
					'X-Transmission-Session-Id' => $response->headers['x-transmission-session-id'],
					'Content-Type' => 'application/json'
				);
				$data = array(
					'method' => 'torrent-get',
					'arguments' => array(
						'fields' => array(
							"id", "name", "totalSize", "eta", "isFinished", "isStalled", "percentDone", "rateDownload", "status", "downloadDir", "errorString"
						),
					),
					'tags' => ''
				);
				$response = Requests::post($url, $headers, json_encode($data), $options);
				if ($response->success) {
					$torrentList = json_decode($response->body, true)['arguments']['torrents'];
					if ($GLOBALS['transmissionHideSeeding'] || $GLOBALS['transmissionHideCompleted']) {
						$filter = array();
						$torrents['arguments']['torrents'] = array();
						if ($GLOBALS['transmissionHideSeeding']) {
							array_push($filter, 6, 5);
						}
						if ($GLOBALS['transmissionHideCompleted']) {
							array_push($filter, 0);
						}
						foreach ($torrentList as $key => $value) {
							if (!in_array($value['status'], $filter)) {
								$torrents['arguments']['torrents'][] = $value;
							}
						}
					} else {
						$torrents = json_decode($response->body, true);
					}
					$api['content']['queueItems'] = $torrents;
					$api['content']['historyItems'] = false;
				}
			} else {
				writeLog('error', 'Transmission Connect Function - Error: Could not get session ID', 'SYSTEM');
			}
		} catch (Requests_Exception $e) {
			writeLog('error', 'Transmission Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		return $api;
	}
	return false;
}

function rTorrentStatus($completed, $state, $status)
{
	if ($completed && $state && $status == 'seed') {
		$state = 'Seeding';
	} elseif (!$completed && !$state && $status == 'leech') {
		$state = 'Stopped';
	} elseif (!$completed && $state && $status == 'leech') {
		$state = 'Downloading';
	} elseif ($completed && !$state && $status == 'seed') {
		$state = 'Finished';
	}
	return ($state) ? $state : $status;
}

function rTorrentConnect()
{
	if ($GLOBALS['homepagerTorrentEnabled'] && !empty($GLOBALS['rTorrentURL']) && qualifyRequest($GLOBALS['homepagerTorrentAuth'])) {
		try {
			$torrents = array();
			$digest = (empty($GLOBALS['rTorrentURLOverride'])) ? qualifyURL($GLOBALS['rTorrentURL'], true) : qualifyURL(checkOverrideURL($GLOBALS['rTorrentURL'], $GLOBALS['rTorrentURLOverride']), true);
			$passwordInclude = ($GLOBALS['rTorrentUsername'] != '' && $GLOBALS['rTorrentPassword'] != '') ? $GLOBALS['rTorrentUsername'] . ':' . decrypt($GLOBALS['rTorrentPassword']) . "@" : '';
			$extraPath = (strpos($GLOBALS['rTorrentURL'], '.php') !== false) ? '' : '/RPC2';
			$extraPath = (empty($GLOBALS['rTorrentURLOverride'])) ? $extraPath : '';
			$url = $digest['scheme'] . '://' . $passwordInclude . $digest['host'] . $digest['port'] . $digest['path'] . $extraPath;
			$options = (localURL($url)) ? array('verify' => false) : array();
			$data = xmlrpc_encode_request("d.multicall2", array(
				"",
				"main",
				"d.name=",
				"d.base_path=",
				"d.up.total=",
				"d.size_bytes=",
				"d.down.total=",
				"d.completed_bytes=",
				"d.connection_current=",
				"d.down.rate=",
				"d.up.rate=",
				"d.timestamp.started=",
				"d.state=",
				"d.group.name=",
				"d.hash=",
				"d.complete=",
				"d.ratio=",
				"d.chunk_size=",
				"f.size_bytes=",
				"f.size_chunks=",
				"f.completed_chunks=",
				"d.custom=",
				"d.custom1=",
				"d.custom2=",
				"d.custom3=",
				"d.custom4=",
				"d.custom5=",
			), array());
			$response = Requests::post($url, array(), $data, $options);
			if ($response->success) {
				$torrentList = xmlrpc_decode(str_replace('i8>', 'string>', $response->body));
				foreach ($torrentList as $key => $value) {
					$tempStatus = rTorrentStatus($value[13], $value[10], $value[6]);
					if ($tempStatus == 'Seeding' && $GLOBALS['rTorrentHideSeeding']) {
						//do nothing
					} elseif ($tempStatus == 'Finished' && $GLOBALS['rTorrentHideCompleted']) {
						//do nothing
					} else {
						$torrents[$key] = array(
							'name' => $value[0],
							'base' => $value[1],
							'upTotal' => $value[2],
							'size' => $value[3],
							'downTotal' => $value[4],
							'downloaded' => $value[5],
							'connectionState' => $value[6],
							'leech' => $value[7],
							'seed' => $value[8],
							'date' => $value[9],
							'state' => ($value[10]) ? 'on' : 'off',
							'group' => $value[11],
							'hash' => $value[12],
							'complete' => ($value[13]) ? 'yes' : 'no',
							'ratio' => $value[14],
							'label' => $value[20],
							'status' => $tempStatus,
							'temp' => $value[16] . ' - ' . $value[17] . ' - ' . $value[18],
							'custom' => $value[19] . ' - ' . $value[20] . ' - ' . $value[21],
							'custom2' => $value[22] . ' - ' . $value[23] . ' - ' . $value[24],
						);
					}
				}
				if (count($torrents) !== 0) {
					usort($torrents, function ($a, $b) {
						$direction = substr($GLOBALS['rTorrentSortOrder'], -1);
						$sort = substr($GLOBALS['rTorrentSortOrder'], 0, strlen($GLOBALS['rTorrentSortOrder']) - 1);
						switch ($direction) {
							case 'a':
								return $a[$sort] <=> $b[$sort];
								break;
							case 'd':
								return $b[$sort] <=> $a[$sort];
								break;
							default:
								return $b['date'] <=> $a['date'];
						}
					});
				}
				$api['content']['queueItems'] = $torrents;
				$api['content']['historyItems'] = false;
			}
		} catch
		(Requests_Exception $e) {
			writeLog('error', 'rTorrent Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		return $api;
	}
	return false;
}

function qBittorrentConnect()
{
	if ($GLOBALS['homepageqBittorrentEnabled'] && !empty($GLOBALS['qBittorrentURL']) && qualifyRequest($GLOBALS['homepageqBittorrentAuth'])) {
		$digest = qualifyURL($GLOBALS['qBittorrentURL'], true);
		$data = array('username' => $GLOBALS['qBittorrentUsername'], 'password' => decrypt($GLOBALS['qBittorrentPassword']));
		$url = $digest['scheme'] . '://' . $digest['host'] . $digest['port'] . $digest['path'] . '/login';
		try {
			$options = (localURL($GLOBALS['qBittorrentURL'])) ? array('verify' => false) : array();
			$response = Requests::post($url, array(), $data, $options);
			$reflection = new ReflectionClass($response->cookies);
			$cookie = $reflection->getProperty("cookies");
			$cookie->setAccessible(true);
			$cookie = $cookie->getValue($response->cookies);
			if ($cookie) {
				$headers = array(
					'Cookie' => 'SID=' . $cookie['SID']->value
				);
				$reverse = $GLOBALS['qBittorrentReverseSorting'] ? 'true' : 'false';
				$url = $digest['scheme'] . '://' . $digest['host'] . $digest['port'] . $digest['path'] . '/query/torrents?sort=' . $GLOBALS['qBittorrentSortOrder'] . '&reverse=' . $reverse;
				$response = Requests::get($url, $headers, $options);
				if ($response) {
					$torrentList = json_decode($response->body, true);
					if ($GLOBALS['qBittorrentHideSeeding'] || $GLOBALS['qBittorrentHideCompleted']) {
						$filter = array();
						$torrents['arguments']['torrents'] = array();
						if ($GLOBALS['qBittorrentHideSeeding']) {
							array_push($filter, 'uploading', 'stalledUP', 'queuedUP');
						}
						if ($GLOBALS['qBittorrentHideCompleted']) {
							array_push($filter, 'pausedUP');
						}
						foreach ($torrentList as $key => $value) {
							if (!in_array($value['state'], $filter)) {
								$torrents['arguments']['torrents'][] = $value;
							}
						}
					} else {
						$torrents['arguments']['torrents'] = json_decode($response->body, true);
					}
					$api['content']['queueItems'] = $torrents;
					$api['content']['historyItems'] = false;
				}
			} else {
				writeLog('error', 'qBittorrent Connect Function - Error: Could not get session ID', 'SYSTEM');
			}
		} catch (Requests_Exception $e) {
			writeLog('error', 'qBittorrent Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		return $api;
	}
	return false;
}

function delugeStatus($queued, $status, $state)
{
	if ($queued == '-1' && $state == '100' && ($status == 'Seeding' || $status == 'Queued' || $status == 'Paused')) {
		$state = 'Seeding';
	} elseif ($state !== '100') {
		$state = 'Downloading';
	} else {
		$state = 'Finished';
	}
	return ($state) ? $state : $status;
}

function delugeConnect()
{
	if ($GLOBALS['homepageDelugeEnabled'] && !empty($GLOBALS['delugeURL']) && !empty($GLOBALS['delugePassword']) && qualifyRequest($GLOBALS['homepageDelugeAuth'])) {
		try {
			$deluge = new deluge($GLOBALS['delugeURL'], decrypt($GLOBALS['delugePassword']));
			$torrents = $deluge->getTorrents(null, 'comment, download_payload_rate, eta, hash, is_finished, is_seed, message, name, paused, progress, queue, state, total_size, upload_payload_rate');
			foreach ($torrents as $key => $value) {
				$tempStatus = delugeStatus($value->queue, $value->state, $value->progress);
				if ($tempStatus == 'Seeding' && $GLOBALS['delugeHideSeeding']) {
					//do nothing
				} elseif ($tempStatus == 'Finished' && $GLOBALS['delugeHideCompleted']) {
					//do nothing
				} else {
					$api['content']['queueItems'][] = $value;
				}
			}
			$api['content']['queueItems'] = (empty($api['content']['queueItems'])) ? [] : $api['content']['queueItems'];
			$api['content']['historyItems'] = false;
		} catch (Excecption $e) {
			writeLog('error', 'Deluge Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		}
	}
	$api['content'] = isset($api['content']) ? $api['content'] : false;
	return $api;
}

function getCalendar()
{
	$startDate = date('Y-m-d', strtotime("-" . $GLOBALS['calendarStart'] . " days"));
	$endDate = date('Y-m-d', strtotime("+" . $GLOBALS['calendarEnd'] . " days"));
	$icalCalendarSources = array();
	$calendarItems = array();
	// SONARR CONNECT
	if ($GLOBALS['homepageSonarrEnabled'] && qualifyRequest($GLOBALS['homepageSonarrAuth']) && !empty($GLOBALS['sonarrURL']) && !empty($GLOBALS['sonarrToken'])) {
		$sonarrs = array();
		$sonarrURLList = explode(',', $GLOBALS['sonarrURL']);
		$sonarrTokenList = explode(',', $GLOBALS['sonarrToken']);
		if (count($sonarrURLList) == count($sonarrTokenList)) {
			foreach ($sonarrURLList as $key => $value) {
				$sonarrs[$key] = array(
					'url' => $value,
					'token' => $sonarrTokenList[$key]
				);
			}
			foreach ($sonarrs as $key => $value) {
				try {
					$sonarr = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token']);
					$sonarr = $sonarr->getCalendar($startDate, $endDate, $GLOBALS['sonarrUnmonitored']);
					$result = json_decode($sonarr, true);
					if (is_array($result) || is_object($result)) {
						$sonarrCalendar = (array_key_exists('error', $result)) ? '' : getSonarrCalendar($sonarr, $key);
					} else {
						$sonarrCalendar = '';
					}
				} catch (Exception $e) {
					writeLog('error', 'Sonarr Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
				}
				if (!empty($sonarrCalendar)) {
					$calendarItems = array_merge($calendarItems, $sonarrCalendar);
				}
			}
		}
	}
	// LIDARR CONNECT
	if ($GLOBALS['homepageLidarrEnabled'] && qualifyRequest($GLOBALS['homepageLidarrAuth']) && !empty($GLOBALS['lidarrURL']) && !empty($GLOBALS['lidarrToken'])) {
		$lidarrs = array();
		$lidarrURLList = explode(',', $GLOBALS['lidarrURL']);
		$lidarrTokenList = explode(',', $GLOBALS['lidarrToken']);
		if (count($lidarrURLList) == count($lidarrTokenList)) {
			foreach ($lidarrURLList as $key => $value) {
				$lidarrs[$key] = array(
					'url' => $value,
					'token' => $lidarrTokenList[$key]
				);
			}
			foreach ($lidarrs as $key => $value) {
				try {
					$lidarr = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], true);
					$lidarr = $lidarr->getCalendar($startDate, $endDate);
					$result = json_decode($lidarr, true);
					if (is_array($result) || is_object($result)) {
						$lidarrCalendar = (array_key_exists('error', $result)) ? '' : getLidarrCalendar($lidarr, $key);
					} else {
						$lidarrCalendar = '';
					}
				} catch (Exception $e) {
					writeLog('error', 'Lidarr Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
				}
				if (!empty($lidarrCalendar)) {
					$calendarItems = array_merge($calendarItems, $lidarrCalendar);
				}
			}
		}
	}
	// RADARR CONNECT
	if ($GLOBALS['homepageRadarrEnabled'] && qualifyRequest($GLOBALS['homepageRadarrAuth']) && !empty($GLOBALS['radarrURL']) && !empty($GLOBALS['radarrToken'])) {
		$radarrs = array();
		$radarrURLList = explode(',', $GLOBALS['radarrURL']);
		$radarrTokenList = explode(',', $GLOBALS['radarrToken']);
		if (count($radarrURLList) == count($radarrTokenList)) {
			foreach ($radarrURLList as $key => $value) {
				$radarrs[$key] = array(
					'url' => $value,
					'token' => $radarrTokenList[$key]
				);
			}
			foreach ($radarrs as $key => $value) {
				try {
					$radarr = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token']);
					$radarr = $radarr->getCalendar($startDate, $endDate);
					$result = json_decode($radarr, true);
					if (is_array($result) || is_object($result)) {
						$radarrCalendar = (array_key_exists('error', $result)) ? '' : getRadarrCalendar($radarr, $key, $value['url']);
					} else {
						$radarrCalendar = '';
					}
				} catch (Exception $e) {
					writeLog('error', 'Radarr Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
				}
				if (!empty($radarrCalendar)) {
					$calendarItems = array_merge($calendarItems, $radarrCalendar);
				}
			}
		}
	}
	// SICKRAGE/BEARD/MEDUSA CONNECT
	if ($GLOBALS['homepageSickrageEnabled'] && qualifyRequest($GLOBALS['homepageSickrageAuth']) && !empty($GLOBALS['sickrageURL']) && !empty($GLOBALS['sickrageToken'])) {
		$sicks = array();
		$sickURLList = explode(',', $GLOBALS['sickrageURL']);
		$sickTokenList = explode(',', $GLOBALS['sickrageToken']);
		if (count($sickURLList) == count($sickTokenList)) {
			foreach ($sickURLList as $key => $value) {
				$sicks[$key] = array(
					'url' => $value,
					'token' => $sickTokenList[$key]
				);
			}
			foreach ($sicks as $key => $value) {
				try {
					$sickrage = new Kryptonit3\SickRage\SickRage($value['url'], $value['token']);
					$sickrageFuture = getSickrageCalendarWanted($sickrage->future(), $key);
					$sickrageHistory = getSickrageCalendarHistory($sickrage->history("100", "downloaded"), $key);
					if (!empty($sickrageFuture)) {
						$calendarItems = array_merge($calendarItems, $sickrageFuture);
					}
					if (!empty($sickrageHistory)) {
						$calendarItems = array_merge($calendarItems, $sickrageHistory);
					}
				} catch (Exception $e) {
					writeLog('error', 'Sickrage Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
				}
			}
		}
	}
	// COUCHPOTATO CONNECT
	if ($GLOBALS['homepageCouchpotatoEnabled'] && qualifyRequest($GLOBALS['homepageCouchpotatoAuth']) && !empty($GLOBALS['couchpotatoURL']) && !empty($GLOBALS['couchpotatoToken'])) {
		$couchs = array();
		$couchpotatoURLList = explode(',', $GLOBALS['couchpotatoURL']);
		$couchpotatoTokenList = explode(',', $GLOBALS['couchpotatoToken']);
		if (count($couchpotatoURLList) == count($couchpotatoTokenList)) {
			foreach ($couchpotatoURLList as $key => $value) {
				$couchs[$key] = array(
					'url' => $value,
					'token' => $couchpotatoTokenList[$key]
				);
			}
			foreach ($couchs as $key => $value) {
				try {
					$couchpotato = new Kryptonit3\CouchPotato\CouchPotato($value['url'], $value['token']);
					$couchCalendar = getCouchCalendar($couchpotato->getMediaList(), $key);
					if (!empty($couchCalendar)) {
						$calendarItems = array_merge($calendarItems, $couchCalendar);
					}
				} catch (Exception $e) {
					writeLog('error', 'Sickrage Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
				}
			}
		}
	}
	// iCal URL
	if ($GLOBALS['homepageCalendarEnabled'] && qualifyRequest($GLOBALS['homepageCalendarAuth']) && !empty($GLOBALS['calendariCal'])) {
		$calendars = array();
		$calendarURLList = explode(',', $GLOBALS['calendariCal']);
		$icalEvents = array();
		foreach ($calendarURLList as $key => $value) {
			$icsEvents = getIcsEventsAsArray($value);
			if (isset($icsEvents) && !empty($icsEvents)) {
				$timeZone = isset($icsEvents [1] ['X-WR-TIMEZONE']) ? trim($icsEvents[1]['X-WR-TIMEZONE']) : date_default_timezone_get();
				$originalTimeZone = isset($icsEvents [1] ['X-WR-TIMEZONE']) ? trim($icsEvents[1]['X-WR-TIMEZONE']) : false;
				unset($icsEvents [1]);
				foreach ($icsEvents as $icsEvent) {
					$startKeys = array_filter_key($icsEvent, function ($key) {
						return strpos($key, 'DTSTART') === 0;
					});
					$endKeys = array_filter_key($icsEvent, function ($key) {
						return strpos($key, 'DTEND') === 0;
					});
					if (!empty($startKeys) && !empty($endKeys) && isset($icsEvent['SUMMARY'])) {
						/* Getting start date and time */
						$repeat = isset($icsEvent ['RRULE']) ? $icsEvent ['RRULE'] : false;
						if (!$originalTimeZone) {
							$tzKey = array_keys($startKeys);
							if (strpos($tzKey[0], 'TZID=') !== false) {
								$originalTimeZone = explode('TZID=', (string)$tzKey[0]);
								$originalTimeZone = (count($originalTimeZone) >= 2) ? $originalTimeZone[1] : false;
							}
						}
						$start = reset($startKeys);
						$end = reset($endKeys);
						$totalDays = $GLOBALS['calendarStart'] + $GLOBALS['calendarEnd'];
						if ($repeat) {
							$repeatOverride = getCalenderRepeatCount(trim($icsEvent["RRULE"]));
							switch (trim(strtolower(getCalenderRepeat($repeat)))) {
								case 'daily':
									$repeat = ($repeatOverride) ? $repeatOverride : $totalDays;
									$term = 'days';
									break;
								case 'weekly':
									$repeat = ($repeatOverride) ? $repeatOverride : round($totalDays / 7);
									$term = 'weeks';
									break;
								case 'monthly':
									$repeat = ($repeatOverride) ? $repeatOverride : round($totalDays / 30);
									$term = 'months';
									break;
								case 'yearly':
									$repeat = ($repeatOverride) ? $repeatOverride : round($totalDays / 365);
									$term = 'years';
									break;
								default:
									$repeat = ($repeatOverride) ? $repeatOverride : $totalDays;
									$term = 'days';
									break;
							}
						} else {
							$repeat = 1;
							$term = 'day';
						}
						$calendarTimes = 0;
						while ($calendarTimes < $repeat) {
							$currentDate = new DateTime ($GLOBALS['currentTime']);
							$oldestDay = new DateTime ($GLOBALS['currentTime']);
							$oldestDay->modify('-' . $GLOBALS['calendarStart'] . ' days');
							$newestDay = new DateTime ($GLOBALS['currentTime']);
							$newestDay->modify('+' . $GLOBALS['calendarEnd'] . ' days');
							/* Converting to datetime and apply the timezone to get proper date time */
							$startDt = new DateTime ($start);
							/* Getting end date with time */
							$endDt = new DateTime ($end);
							if ($calendarTimes !== 0) {
								$dateDiff = date_diff($startDt, $currentDate);
								$startDt->modify($dateDiff->format('%R') . (round(($dateDiff->days) / 7)) . ' weeks');
								$startDt->modify('+' . $calendarTimes . ' ' . $term);
								$endDt->modify($dateDiff->format('%R') . (round(($dateDiff->days) / 7)) . ' weeks');
								$endDt->modify('+' . $calendarTimes . ' ' . $term);
							} elseif ($calendarTimes == 0 && $repeat !== 1) {
								$dateDiff = date_diff($startDt, $currentDate);
								$startDt->modify($dateDiff->format('%R') . (round(($dateDiff->days) / 7)) . ' weeks');
								$endDt->modify($dateDiff->format('%R') . (round(($dateDiff->days) / 7)) . ' weeks');
							}
							$calendarStartDiff = date_diff($startDt, $newestDay);
							$calendarEndDiff = date_diff($startDt, $oldestDay);
							if ($originalTimeZone && $originalTimeZone !== 'UTC' && (strpos($start, 'Z') == false)) {
								$dateTimeOriginalTZ = new DateTimeZone($originalTimeZone);
								$dateTimeOriginal = new DateTime('now', $dateTimeOriginalTZ);
								$dateTimeUTCTZ = new DateTimeZone(date_default_timezone_get());
								$dateTimeUTC = new DateTime('now', $dateTimeUTCTZ);
								$dateTimeOriginalOffset = $dateTimeOriginal->getOffset() / 3600;
								$dateTimeUTCOffset = $dateTimeUTC->getOffset() / 3600;
								$diff = $dateTimeUTCOffset - $dateTimeOriginalOffset;
								$startDt->modify('+ ' . $diff . ' hour');
								$endDt->modify('+ ' . $diff . ' hour');
							}
							$startDt->setTimeZone(new DateTimezone ($timeZone));
							$endDt->setTimeZone(new DateTimezone ($timeZone));
							$startDate = $startDt->format(DateTime::ATOM);
							$endDate = $endDt->format(DateTime::ATOM);
							if (new DateTime() < $endDt) {
								$extraClass = 'text-info';
							} else {
								$extraClass = 'text-success';
							}
							/* Getting the name of event */
							$eventName = $icsEvent['SUMMARY'];
							if (!calendarDaysCheck($calendarStartDiff->format('%R') . $calendarStartDiff->days, $calendarEndDiff->format('%R') . $calendarEndDiff->days)) {
								break;
							}
							if (isset($icsEvent["RRULE"]) && getCalenderRepeatUntil(trim($icsEvent["RRULE"]))) {
								$untilDate = new DateTime (getCalenderRepeatUntil(trim($icsEvent["RRULE"])));
								$untilDiff = date_diff($currentDate, $untilDate);
								if ($untilDiff->days > 0) {
									break;
								}
							}
							$icalEvents[] = array(
								'title' => $eventName,
								'imagetype' => 'calendar-o text-warning text-custom-calendar ' . $extraClass,
								'imagetypeFilter' => 'ical',
								'className' => 'bg-calendar calendar-item bg-custom-calendar',
								'start' => $startDate,
								'end' => $endDate,
								'bgColor' => str_replace('text', 'bg', $extraClass),
							);
							$calendarTimes = $calendarTimes + 1;
						}
					}
				}
			}
		}
		$calendarSources['ical'] = $icalEvents;
	}
	$calendarSources['events'] = $calendarItems;
	return ($calendarSources) ? $calendarSources : false;
}

function calendarDaysCheck($entryStart, $entryEnd)
{
	$success = false;
	$entryStart = intval($entryStart);
	$entryEnd = intval($entryEnd);
	if ($entryStart >= 0 && $entryEnd <= 0) {
		$success = true;
	}
	return $success;
}

function getCalenderRepeat($value)
{
	//FREQ=DAILY
	//RRULE:FREQ=WEEKLY;BYDAY=TH
	$first = explode('=', $value);
	if (count($first) > 1) {
		$second = explode(';', $first[1]);
	} else {
		return $value;
	}
	if ($second) {
		return $second[0];
	} else {
		return $first[1];
	}
}

function getCalenderRepeatUntil($value)
{
	$first = explode('UNTIL=', $value);
	if (count($first) > 1) {
		return $first[1];
	} else {
		return false;
	}
}

function getCalenderRepeatCount($value)
{
	$first = explode('COUNT=', $value);
	if (count($first) > 1) {
		return $first[1];
	} else {
		return false;
	}
}

function getSonarrCalendar($array, $number)
{
	$array = json_decode($array, true);
	$gotCalendar = array();
	$i = 0;
	foreach ($array as $child) {
		$i++;
		$seriesName = $child['series']['title'];
		$seriesID = $child['series']['tvdbId'];
		$episodeID = $child['series']['tvdbId'];
		$monitored = $child['monitored'];
		if (!isset($episodeID)) {
			$episodeID = "";
		}
		//$episodeName = htmlentities($child['title'], ENT_QUOTES);
		$episodeAirDate = $child['airDateUtc'];
		$episodeAirDate = strtotime($episodeAirDate);
		$episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
		if (new DateTime() < new DateTime($episodeAirDate)) {
			$unaired = true;
		}
		if ($child['episodeNumber'] == "1") {
			$episodePremier = "true";
		} else {
			$episodePremier = "false";
			$date = new DateTime($episodeAirDate);
			$date->add(new DateInterval("PT1S"));
			$date->format(DateTime::ATOM);
			$child['airDateUtc'] = gmdate('Y-m-d\TH:i:s\Z', strtotime($date->format(DateTime::ATOM)));
		}
		$downloaded = $child['hasFile'];
		if ($downloaded == "0" && isset($unaired) && $episodePremier == "true") {
			$downloaded = "text-primary animated flash";
		} elseif ($downloaded == "0" && isset($unaired) && $monitored == "0") {
			$downloaded = "text-dark";
		} elseif ($downloaded == "0" && isset($unaired)) {
			$downloaded = "text-info";
		} elseif ($downloaded == "1") {
			$downloaded = "text-success";
		} else {
			$downloaded = "text-danger";
		}
		$fanart = "/plugins/images/cache/no-np.png";
		foreach ($child['series']['images'] as $image) {
			if ($image['coverType'] == "fanart") {
				$fanart = $image['url'];
			}
		}
		if ($fanart !== "/plugins/images/cache/no-np.png") {
			$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
			$imageURL = $fanart;
			$cacheFile = $cacheDirectory . $seriesID . '.jpg';
			$fanart = 'plugins/images/cache/' . $seriesID . '.jpg';
			if (!file_exists($cacheFile)) {
				cacheImage($imageURL, $seriesID);
				unset($imageURL);
				unset($cacheFile);
			}
		}
		$bottomTitle = 'S' . sprintf("%02d", $child['seasonNumber']) . 'E' . sprintf("%02d", $child['episodeNumber']) . ' - ' . $child['title'];
		$details = array(
			"seasonCount" => $child['series']['seasonCount'],
			"status" => $child['series']['status'],
			"topTitle" => $seriesName,
			"bottomTitle" => $bottomTitle,
			"overview" => isset($child['overview']) ? $child['overview'] : '',
			"runtime" => $child['series']['runtime'],
			"image" => $fanart,
			"ratings" => $child['series']['ratings']['value'],
			"videoQuality" => $child["hasFile"] && isset($child['episodeFile']['quality']['quality']['name']) ? $child['episodeFile']['quality']['quality']['name'] : "unknown",
			"audioChannels" => $child["hasFile"] && isset($child['episodeFile']['mediaInfo']) ? $child['episodeFile']['mediaInfo']['audioChannels'] : "unknown",
			"audioCodec" => $child["hasFile"] && isset($child['episodeFile']['mediaInfo']) ? $child['episodeFile']['mediaInfo']['audioCodec'] : "unknown",
			"videoCodec" => $child["hasFile"] && isset($child['episodeFile']['mediaInfo']) ? $child['episodeFile']['mediaInfo']['videoCodec'] : "unknown",
			"size" => $child["hasFile"] && isset($child['episodeFile']['size']) ? $child['episodeFile']['size'] : "unknown",
			"genres" => $child['series']['genres'],
		);
		array_push($gotCalendar, array(
			"id" => "Sonarr-" . $number . "-" . $i,
			"title" => $seriesName,
			"start" => $child['airDateUtc'],
			"className" => "inline-popups bg-calendar calendar-item tvID--" . $episodeID,
			"imagetype" => "tv " . $downloaded,
			"imagetypeFilter" => "tv",
			"downloadFilter" => $downloaded,
			"bgColor" => str_replace('text', 'bg', $downloaded),
			"details" => $details
		));
	}
	if ($i != 0) {
		return $gotCalendar;
	}
	return false;
}

function getLidarrCalendar($array, $number)
{
	$array = json_decode($array, true);
	$gotCalendar = array();
	$i = 0;
	foreach ($array as $child) {
		$i++;
		$albumName = $child['title'];
		$artistName = $child['artist']['artistName'];
		$albumID = '';
		$releaseDate = $child['releaseDate'];
		$releaseDate = strtotime($releaseDate);
		$releaseDate = date("Y-m-d H:i:s", $releaseDate);
		if (new DateTime() < new DateTime($releaseDate)) {
			$unaired = true;
		}
		if (isset($child['statistics']['percentOfTracks'])) {
			if ($child['statistics']['percentOfTracks'] == '100.0') {
				$downloaded = '1';
			} else {
				$downloaded = '0';
			}
		} else {
			$downloaded = '0';
		}
		if ($downloaded == "0" && isset($unaired)) {
			$downloaded = "text-info";
		} elseif ($downloaded == "1") {
			$downloaded = "text-success";
		} else {
			$downloaded = "text-danger";
		}
		$fanart = "/plugins/images/cache/no-np.png";
		foreach ($child['artist']['images'] as $image) {
			if ($image['coverType'] == "fanart") {
				$fanart = str_replace('http://', 'https://', $image['url']);
			}
		}
		$details = array(
			"seasonCount" => '',
			"status" => '',
			"topTitle" => $albumName,
			"bottomTitle" => $artistName,
			"overview" => isset($child['artist']['overview']) ? $child['artist']['overview'] : '',
			"runtime" => '',
			"image" => $fanart,
			"ratings" => $child['artist']['ratings']['value'],
			"videoQuality" => "unknown",
			"audioChannels" => "unknown",
			"audioCodec" => "unknown",
			"videoCodec" => "unknown",
			"size" => "unknown",
			"genres" => $child['genres'],
		);
		array_push($gotCalendar, array(
			"id" => "Lidarr-" . $number . "-" . $i,
			"title" => $artistName,
			"start" => $child['releaseDate'],
			"className" => "inline-popups bg-calendar calendar-item musicID--",
			"imagetype" => "music " . $downloaded,
			"imagetypeFilter" => "music",
			"downloadFilter" => $downloaded,
			"bgColor" => str_replace('text', 'bg', $downloaded),
			"details" => $details,
			"data" => $child
		));
	}
	if ($i != 0) {
		return $gotCalendar;
	}
	return false;
}

function getRadarrCalendar($array, $number, $url)
{
	$array = json_decode($array, true);
	$gotCalendar = array();
	$i = 0;
	foreach ($array as $child) {
		if (isset($child['physicalRelease'])) {
			$i++;
			$movieName = $child['title'];
			$movieID = $child['tmdbId'];
			if (!isset($movieID)) {
				$movieID = "";
			}
			$physicalRelease = $child['physicalRelease'];
			$physicalRelease = strtotime($physicalRelease);
			$physicalRelease = date("Y-m-d", $physicalRelease);
			if (new DateTime() < new DateTime($physicalRelease)) {
				$notReleased = "true";
			} else {
				$notReleased = "false";
			}
			$downloaded = $child['hasFile'];
			if ($downloaded == "0" && $notReleased == "true") {
				$downloaded = "text-info";
			} elseif ($downloaded == "1") {
				$downloaded = "text-success";
			} else {
				$downloaded = "text-danger";
			}
			$banner = "/plugins/images/cache/no-np.png";
			foreach ($child['images'] as $image) {
				if ($image['coverType'] == "banner" || $image['coverType'] == "fanart") {
					$url = rtrim($url, '/'); //remove trailing slash
					$url = $url . '/api';
					$imageUrl = $image['url'];
					$urlParts = explode("/", $url);
					$imageParts = explode("/", $image['url']);
					if ($imageParts[1] == end($urlParts)) {
						unset($imageParts[1]);
						$imageUrl = implode("/", $imageParts);
					}
					$banner = $url . $imageUrl . '?apikey=' . $GLOBALS['radarrToken'];
				}
			}
			if ($banner !== "/plugins/images/cache/no-np.png") {
				$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
				$imageURL = $banner;
				$cacheFile = $cacheDirectory . $movieID . '.jpg';
				$banner = 'plugins/images/cache/' . $movieID . '.jpg';
				if (!file_exists($cacheFile)) {
					cacheImage($imageURL, $movieID);
					unset($imageURL);
					unset($cacheFile);
				}
			}
			$alternativeTitles = "";
			foreach ($child['alternativeTitles'] as $alternative) {
				$alternativeTitles .= $alternative['title'] . ', ';
			}
			$alternativeTitles = empty($child['alternativeTitles']) ? "" : substr($alternativeTitles, 0, -2);
			$details = array(
				"topTitle" => $movieName,
				"bottomTitle" => $alternativeTitles,
				"status" => $child['status'],
				"overview" => $child['overview'],
				"runtime" => $child['runtime'],
				"image" => $banner,
				"ratings" => $child['ratings']['value'],
				"videoQuality" => $child["hasFile"] ? @$child['movieFile']['quality']['quality']['name'] : "unknown",
				"audioChannels" => $child["hasFile"] ? @$child['movieFile']['mediaInfo']['audioChannels'] : "unknown",
				"audioCodec" => $child["hasFile"] ? @$child['movieFile']['mediaInfo']['audioFormat'] : "unknown",
				"videoCodec" => $child["hasFile"] ? @$child['movieFile']['mediaInfo']['videoCodec'] : "unknown",
				"size" => $child["hasFile"] ? @$child['movieFile']['size'] : "unknown",
				"genres" => $child['genres'],
				"year" => isset($child['year']) ? $child['year'] : '',
				"studio" => isset($child['studio']) ? $child['studio'] : '',
			);
			array_push($gotCalendar, array(
				"id" => "Radarr-" . $number . "-" . $i,
				"title" => $movieName,
				"start" => $physicalRelease,
				"className" => "inline-popups bg-calendar movieID--" . $movieID,
				"imagetype" => "film " . $downloaded,
				"imagetypeFilter" => "film",
				"downloadFilter" => $downloaded,
				"bgColor" => str_replace('text', 'bg', $downloaded),
				"details" => $details
			));
		}
	}
	if ($i != 0) {
		return $gotCalendar;
	}
	return false;
}

function getCouchCalendar($array, $number)
{
	$api = json_decode($array, true);
	$gotCalendar = array();
	$i = 0;
	foreach ($api['movies'] as $child) {
		if ($child['status'] == "active" || $child['status'] == "done") {
			$i++;
			$movieName = $child['info']['original_title'];
			$movieID = $child['info']['tmdb_id'];
			if (!isset($movieID)) {
				$movieID = "";
			}
			$physicalRelease = (isset($child['info']['released']) ? $child['info']['released'] : null);
			$backupRelease = (isset($child['info']['release_date']['theater']) ? $child['info']['release_date']['theater'] : null);
			$physicalRelease = (isset($physicalRelease) ? $physicalRelease : $backupRelease);
			$physicalRelease = strtotime($physicalRelease);
			$physicalRelease = date("Y-m-d", $physicalRelease);
			if (new DateTime() < new DateTime($physicalRelease)) {
				$notReleased = "true";
			} else {
				$notReleased = "false";
			}
			$downloaded = ($child['status'] == "active") ? "0" : "1";
			if ($downloaded == "0" && $notReleased == "true") {
				$downloaded = "text-info";
			} elseif ($downloaded == "1") {
				$downloaded = "text-success";
			} else {
				$downloaded = "text-danger";
			}
			if (!empty($child['info']['images']['backdrop_original'])) {
				$banner = $child['info']['images']['backdrop_original'][0];
			} elseif (!empty($child['info']['images']['backdrop'])) {
				$banner = $child['info']['images']['backdrop_original'][0];
			} else {
				$banner = "/plugins/images/cache/no-np.png";
			}
			if ($banner !== "/plugins/images/cache/no-np.png") {
				$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
				$imageURL = $banner;
				$cacheFile = $cacheDirectory . $movieID . '.jpg';
				$banner = 'plugins/images/cache/' . $movieID . '.jpg';
				if (!file_exists($cacheFile)) {
					cacheImage($imageURL, $movieID);
					unset($imageURL);
					unset($cacheFile);
				}
			}
			$hasFile = (!empty($child['releases']) && !empty($child['releases'][0]['files']['movie']));
			$details = array(
				"topTitle" => $movieName,
				"bottomTitle" => $child['info']['tagline'],
				"status" => $child['status'],
				"overview" => $child['info']['plot'],
				"runtime" => $child['info']['runtime'],
				"image" => $banner,
				"ratings" => isset($child['info']['rating']['imdb'][0]) ? $child['info']['rating']['imdb'][0] : '',
				"videoQuality" => $hasFile ? $child['releases'][0]['quality'] : "unknown",
				"audioChannels" => "",
				"audioCodec" => "",
				"videoCodec" => "",
				"genres" => $child['info']['genres'],
				"year" => isset($child['info']['year']) ? $child['info']['year'] : '',
				"studio" => isset($child['info']['year']) ? $child['info']['year'] : '',
			);
			array_push($gotCalendar, array(
				"id" => "CouchPotato-" . $number . "-" . $i,
				"title" => $movieName,
				"start" => $physicalRelease,
				"className" => "inline-popups bg-calendar calendar-item movieID--" . $movieID,
				"imagetype" => "film " . $downloaded,
				"imagetypeFilter" => "film",
				"downloadFilter" => $downloaded,
				"bgColor" => str_replace('text', 'bg', $downloaded),
				"details" => $details
			));
		}
	}
	if ($i != 0) {
		return $gotCalendar;
	}
	return false;
}

function getSickrageCalendarWanted($array, $number)
{
	$array = json_decode($array, true);
	$gotCalendar = array();
	$i = 0;
	foreach ($array['data']['missed'] as $child) {
		$i++;
		$seriesName = $child['show_name'];
		$seriesID = $child['tvdbid'];
		$episodeID = $child['tvdbid'];
		$episodeAirDate = $child['airdate'];
		$episodeAirDateTime = explode(" ", $child['airs']);
		$episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1] . $episodeAirDateTime[2]));
		$episodeAirDate = strtotime($episodeAirDate . $episodeAirDateTime);
		$episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
		if (new DateTime() < new DateTime($episodeAirDate)) {
			$unaired = true;
		}
		$downloaded = "0";
		if ($downloaded == "0" && isset($unaired)) {
			$downloaded = "text-info";
		} elseif ($downloaded == "1") {
			$downloaded = "text-success";
		} else {
			$downloaded = "text-danger";
		}
		$bottomTitle = 'S' . sprintf("%02d", $child['season']) . 'E' . sprintf("%02d", $child['episode']) . ' - ' . $child['ep_name'];
		$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
		$cacheFile = $cacheDirectory . $seriesID . '.jpg';
		$fanart = "/plugins/images/cache/no-np.png";
		if (file_exists($cacheFile)) {
			$fanart = 'plugins/images/cache/' . $seriesID . '.jpg';
			unset($cacheFile);
		}
		$details = array(
			"seasonCount" => "",
			"status" => $child['show_status'],
			"topTitle" => $seriesName,
			"bottomTitle" => $bottomTitle,
			"overview" => isset($child['ep_plot']) ? $child['ep_plot'] : '',
			"runtime" => "",
			"image" => $fanart,
			"ratings" => "",
			"videoQuality" => isset($child["quality"]) ? $child["quality"] : "",
			"audioChannels" => "",
			"audioCodec" => "",
			"videoCodec" => "",
			"size" => "",
			"genres" => "",
		);
		array_push($gotCalendar, array(
			"id" => "Sick-" . $number . "-Miss-" . $i,
			"title" => $seriesName,
			"start" => $episodeAirDate,
			"className" => "inline-popups bg-calendar calendar-item tvID--" . $episodeID,
			"imagetype" => "tv " . $downloaded,
			"imagetypeFilter" => "tv",
			"downloadFilter" => $downloaded,
			"bgColor" => str_replace('text', 'bg', $downloaded),
			"details" => $details,
		));
	}
	foreach ($array['data']['today'] as $child) {
		$i++;
		$seriesName = $child['show_name'];
		$seriesID = $child['tvdbid'];
		$episodeID = $child['tvdbid'];
		$episodeAirDate = $child['airdate'];
		$episodeAirDateTime = explode(" ", $child['airs']);
		$episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1] . $episodeAirDateTime[2]));
		$episodeAirDate = strtotime($episodeAirDate . $episodeAirDateTime);
		$episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
		if (new DateTime() < new DateTime($episodeAirDate)) {
			$unaired = true;
		}
		$downloaded = "0";
		if ($downloaded == "0" && isset($unaired)) {
			$downloaded = "text-info";
		} elseif ($downloaded == "1") {
			$downloaded = "text-success";
		} else {
			$downloaded = "text-danger";
		}
		$bottomTitle = 'S' . sprintf("%02d", $child['season']) . 'E' . sprintf("%02d", $child['episode']) . ' - ' . $child['ep_name'];
		$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
		$cacheFile = $cacheDirectory . $seriesID . '.jpg';
		$fanart = "/plugins/images/cache/no-np.png";
		if (file_exists($cacheFile)) {
			$fanart = 'plugins/images/cache/' . $seriesID . '.jpg';
			unset($cacheFile);
		}
		$details = array(
			"seasonCount" => "",
			"status" => $child['show_status'],
			"topTitle" => $seriesName,
			"bottomTitle" => $bottomTitle,
			"overview" => isset($child['ep_plot']) ? $child['ep_plot'] : '',
			"runtime" => "",
			"image" => $fanart,
			"ratings" => "",
			"videoQuality" => isset($child["quality"]) ? $child["quality"] : "",
			"audioChannels" => "",
			"audioCodec" => "",
			"videoCodec" => "",
			"size" => "",
			"genres" => "",
		);
		array_push($gotCalendar, array(
			"id" => "Sick-" . $number . "-Today-" . $i,
			"title" => $seriesName,
			"start" => $episodeAirDate,
			"className" => "inline-popups bg-calendar calendar-item tvID--" . $episodeID,
			"imagetype" => "tv " . $downloaded,
			"imagetypeFilter" => "tv",
			"downloadFilter" => $downloaded,
			"bgColor" => str_replace('text', 'bg', $downloaded),
			"details" => $details,
		));
	}
	foreach ($array['data']['soon'] as $child) {
		$i++;
		$seriesName = $child['show_name'];
		$seriesID = $child['tvdbid'];
		$episodeID = $child['tvdbid'];
		$episodeAirDate = $child['airdate'];
		$episodeAirDateTime = explode(" ", $child['airs']);
		$episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1] . $episodeAirDateTime[2]));
		$episodeAirDate = strtotime($episodeAirDate . $episodeAirDateTime);
		$episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
		if (new DateTime() < new DateTime($episodeAirDate)) {
			$unaired = true;
		}
		$downloaded = "0";
		if ($downloaded == "0" && isset($unaired)) {
			$downloaded = "text-info";
		} elseif ($downloaded == "1") {
			$downloaded = "text-success";
		} else {
			$downloaded = "text-danger";
		}
		$bottomTitle = 'S' . sprintf("%02d", $child['season']) . 'E' . sprintf("%02d", $child['episode']) . ' - ' . $child['ep_name'];
		$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
		$cacheFile = $cacheDirectory . $seriesID . '.jpg';
		$fanart = "/plugins/images/cache/no-np.png";
		if (file_exists($cacheFile)) {
			$fanart = 'plugins/images/cache/' . $seriesID . '.jpg';
			unset($cacheFile);
		}
		$details = array(
			"seasonCount" => "",
			"status" => $child['show_status'],
			"topTitle" => $seriesName,
			"bottomTitle" => $bottomTitle,
			"overview" => isset($child['ep_plot']) ? $child['ep_plot'] : '',
			"runtime" => "",
			"image" => $fanart,
			"ratings" => "",
			"videoQuality" => isset($child["quality"]) ? $child["quality"] : "",
			"audioChannels" => "",
			"audioCodec" => "",
			"videoCodec" => "",
			"size" => "",
			"genres" => "",
		);
		array_push($gotCalendar, array(
			"id" => "Sick-" . $number . "-Soon-" . $i,
			"title" => $seriesName,
			"start" => $episodeAirDate,
			"className" => "inline-popups bg-calendar calendar-item tvID--" . $episodeID,
			"imagetype" => "tv " . $downloaded,
			"imagetypeFilter" => "tv",
			"downloadFilter" => $downloaded,
			"bgColor" => str_replace('text', 'bg', $downloaded),
			"details" => $details,
		));
	}
	foreach ($array['data']['later'] as $child) {
		$i++;
		$seriesName = $child['show_name'];
		$seriesID = $child['tvdbid'];
		$episodeID = $child['tvdbid'];
		$episodeAirDate = $child['airdate'];
		$episodeAirDateTime = explode(" ", $child['airs']);
		$episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1] . $episodeAirDateTime[2]));
		$episodeAirDate = strtotime($episodeAirDate . $episodeAirDateTime);
		$episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
		if (new DateTime() < new DateTime($episodeAirDate)) {
			$unaired = true;
		}
		$downloaded = "0";
		if ($downloaded == "0" && isset($unaired)) {
			$downloaded = "text-info";
		} elseif ($downloaded == "1") {
			$downloaded = "text-success";
		} else {
			$downloaded = "text-danger";
		}
		$bottomTitle = 'S' . sprintf("%02d", $child['season']) . 'E' . sprintf("%02d", $child['episode']) . ' - ' . $child['ep_name'];
		$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
		$cacheFile = $cacheDirectory . $seriesID . '.jpg';
		$fanart = "/plugins/images/cache/no-np.png";
		if (file_exists($cacheFile)) {
			$fanart = 'plugins/images/cache/' . $seriesID . '.jpg';
			unset($cacheFile);
		}
		$details = array(
			"seasonCount" => "",
			"status" => $child['show_status'],
			"topTitle" => $seriesName,
			"bottomTitle" => $bottomTitle,
			"overview" => isset($child['ep_plot']) ? $child['ep_plot'] : '',
			"runtime" => "",
			"image" => $fanart,
			"ratings" => "",
			"videoQuality" => isset($child["quality"]) ? $child["quality"] : "",
			"audioChannels" => "",
			"audioCodec" => "",
			"videoCodec" => "",
			"size" => "",
			"genres" => "",
		);
		array_push($gotCalendar, array(
			"id" => "Sick-" . $number . "-Later-" . $i,
			"title" => $seriesName,
			"start" => $episodeAirDate,
			"className" => "inline-popups bg-calendar calendar-item tvID--" . $episodeID,
			"imagetype" => "tv " . $downloaded,
			"imagetypeFilter" => "tv",
			"downloadFilter" => $downloaded,
			"bgColor" => str_replace('text', 'bg', $downloaded),
			"details" => $details,
		));
	}
	if ($i != 0) {
		return $gotCalendar;
	}
	return false;
}

function getSickrageCalendarHistory($array, $number)
{
	$array = json_decode($array, true);
	$gotCalendar = array();
	$i = 0;
	foreach ($array['data'] as $child) {
		$i++;
		$seriesName = $child['show_name'];
		$seriesID = $child['tvdbid'];
		$episodeID = $child['tvdbid'];
		$episodeAirDate = $child['date'];
		$downloaded = "text-success";
		$bottomTitle = 'S' . sprintf("%02d", $child['season']) . 'E' . sprintf("%02d", $child['episode']);
		$cacheDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
		$cacheFile = $cacheDirectory . $seriesID . '.jpg';
		$fanart = "/plugins/images/cache/no-np.png";
		if (file_exists($cacheFile)) {
			$fanart = 'plugins/images/cache/' . $seriesID . '.jpg';
			unset($cacheFile);
		}
		$details = array(
			"seasonCount" => "",
			"status" => $child['status'],
			"topTitle" => $seriesName,
			"bottomTitle" => $bottomTitle,
			"overview" => '',
			"runtime" => isset($child['series']['runtime']) ? $child['series']['runtime'] : 30,
			"image" => $fanart,
			"ratings" => isset($child['series']['ratings']['value']) ? $child['series']['ratings']['value'] : "unknown",
			"videoQuality" => isset($child["quality"]) ? $child['quality'] : "unknown",
			"audioChannels" => "",
			"audioCodec" => "",
			"videoCodec" => "",
			"size" => "",
			"genres" => "",
		);
		array_push($gotCalendar, array(
			"id" => "Sick-" . $number . "-History-" . $i,
			"title" => $seriesName,
			"start" => $episodeAirDate,
			"className" => "inline-popups bg-calendar calendar-item tvID--" . $episodeID,
			"imagetype" => "tv " . $downloaded,
			"imagetypeFilter" => "tv",
			"downloadFilter" => $downloaded,
			"bgColor" => str_replace('text', 'bg', $downloaded),
			"details" => $details,
		));
	}
	if ($i != 0) {
		return $gotCalendar;
	}
	return false;
}

function ombiAPI($array)
{
	return ombiAction($array['data']['id'], $array['data']['action'], $array['data']['type'], $array['data']);
}

function ombiImport($type = null)
{
	if (!empty($GLOBALS['ombiURL']) && !empty($GLOBALS['ombiToken']) && !empty($type)) {
		try {
			$url = qualifyURL($GLOBALS['ombiURL']);
			$headers = array(
				"Accept" => "application/json",
				"Content-Type" => "application/json",
				"Apikey" => $GLOBALS['ombiToken']
			);
			$options = (localURL($url)) ? array('verify' => false) : array();
			switch ($type) {
				case 'emby':
				case 'emby_local':
				case 'emby_connect':
				case 'emby_all':
					$response = Requests::post($url . "/api/v1/Job/embyuserimporter", $headers, $options);
					break;
				case 'plex':
					$response = Requests::post($url . "/api/v1/Job/plexuserimporter", $headers, $options);
					break;
				default:
					return false;
					break;
			}
			if ($response->success) {
				writeLog('success', 'OMBI Connect Function - Ran User Import', 'SYSTEM');
				return true;
			} else {
				writeLog('error', 'OMBI Connect Function - Error: Connection Unsuccessful', 'SYSTEM');
				return false;
			}
		} catch (Requests_Exception $e) {
			writeLog('error', 'OMBI Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			return false;
		};
	}
	return false;
}

function ombiAction($id, $action, $type, $fullArray = null)
{
	if ($GLOBALS['homepageOmbiEnabled'] && !empty($GLOBALS['ombiURL']) && !empty($GLOBALS['ombiToken']) && qualifyRequest($GLOBALS['homepageOmbiAuth'])) {
		$url = qualifyURL($GLOBALS['ombiURL']);
		$headers = array(
			"Accept" => "application/json",
			"Content-Type" => "application/json",
			"Apikey" => $GLOBALS['ombiToken']
		);
		$data = array(
			'id' => $id,
		);
		switch ($type) {
			case 'season':
			case 'tv':
				$type = 'tv';
				$add = array(
					'tvDbId' => $id,
					'requestAll' => ombiTVDefault('all'),
					'latestSeason' => ombiTVDefault('last'),
					'firstSeason' => ombiTVDefault('first')
				);
				break;
			default:
				$type = 'movie';
				$add = array("theMovieDbId" => (int)$id);
				break;
		}
		$success['head'] = $headers;
		$success['act'] = $action;
		$success['data'] = $data;
		$success['add'] = $add;
		$success['type'] = $type;
		try {
			$options = (localURL($url)) ? array('verify' => false) : array();
			switch ($action) {
				case 'add':
					if (isset($_COOKIE['Auth'])) {
						$headers = array(
							"Accept" => "application/json",
							"Content-Type" => "application/json",
							"Authorization" => "Bearer " . $_COOKIE['Auth']
						);
						$success['head'] = $headers;
					} else {
						return false;
					}
					$response = Requests::post($url . "/api/v1/Request/" . $type, $headers, json_encode($add), $options);
					break;
				default:
					if (qualifyRequest(1)) {
						switch ($action) {
							case 'approve':
								$response = Requests::post($url . "/api/v1/Request/" . $type . "/approve", $headers, json_encode($data), $options);
								break;
							case 'available':
								$response = Requests::post($url . "/api/v1/Request/" . $type . "/available", $headers, json_encode($data), $options);
								break;
							case 'unavailable':
								$response = Requests::post($url . "/api/v1/Request/" . $type . "/unavailable", $headers, json_encode($data), $options);
								break;
							case 'deny':
								$response = Requests::put($url . "/api/v1/Request/" . $type . "/deny", $headers, json_encode($data), $options);
								break;
							case 'delete':
								$response = Requests::delete($url . "/api/v1/Request/" . $type . "/" . $id, $headers, $options);
								break;
							default:
								return false;
						}
					}
					break;
			}
			$success['api'] = $response;
			$success['bd'] = $response->body;
			$success['hd'] = $response->headers;
			if ($response->success) {
				$success['ok'] = true;
			}
		} catch (Requests_Exception $e) {
			writeLog('error', 'OMBI Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		};
	}
	return isset($success['ok']) ? $success : false;
}

function getOmbiRequests($type = "both", $limit = 50)
{
	if ($GLOBALS['homepageOmbiEnabled'] && !empty($GLOBALS['ombiURL']) && !empty($GLOBALS['ombiToken']) && qualifyRequest($GLOBALS['homepageOmbiAuth'])) {
		$url = qualifyURL($GLOBALS['ombiURL']);
		$headers = array(
			"Accept" => "application/json",
			"Apikey" => $GLOBALS['ombiToken'],
		);
		$requests = array();
		try {
			$options = (localURL($url)) ? array('verify' => false) : array();
			switch ($type) {
				case 'movie':
					$movie = Requests::get($url . "/api/v1/Request/movie", $headers, $options);
					break;
				case 'tv':
					$tv = Requests::get($url . "/api/v1/Request/tv", $headers, $options);
					break;
				default:
					$movie = Requests::get($url . "/api/v1/Request/movie", $headers, $options);
					$tv = Requests::get($url . "/api/v1/Request/tv", $headers, $options);
					break;
			}
			if ($movie->success || $tv->success) {
				if (isset($movie)) {
					$movie = json_decode($movie->body, true);
					//$movie = array_reverse($movie);
					foreach ($movie as $key => $value) {
						$proceed = (($GLOBALS['ombiLimitUser']) && strtolower($GLOBALS['organizrUser']['username']) == strtolower($value['requestedUser']['userName'])) || (!$GLOBALS['ombiLimitUser']) || qualifyRequest(1) ? true : false;
						if ($proceed) {
							$requests[] = array(
								'test' => $value,
								'id' => $value['theMovieDbId'],
								'title' => $value['title'],
								'overview' => $value['overview'],
								'poster' => (isset($value['posterPath']) && $value['posterPath'] !== '') ? 'https://image.tmdb.org/t/p/w300/' . $value['posterPath'] : '',
								'background' => (isset($value['background']) && $value['background'] !== '') ? 'https://image.tmdb.org/t/p/w1280/' . $value['background'] : '',
								'approved' => $value['approved'],
								'available' => $value['available'],
								'denied' => $value['denied'],
								'deniedReason' => $value['deniedReason'],
								'user' => $value['requestedUser']['userName'],
								'userAlias' => $value['requestedUser']['userAlias'],
								'request_id' => $value['id'],
								'request_date' => $value['requestedDate'],
								'release_date' => $value['releaseDate'],
								'type' => 'movie',
								'icon' => 'mdi mdi-filmstrip',
								'color' => 'palette-Deep-Purple-900 bg white',
							);
						}
					}
				}
				if (isset($tv) && (is_array($tv) || is_object($tv))) {
					$tv = json_decode($tv->body, true);
					foreach ($tv as $key => $value) {
						if (count($value['childRequests']) > 0) {
							$proceed = (($GLOBALS['ombiLimitUser']) && strtolower($GLOBALS['organizrUser']['username']) == strtolower($value['childRequests'][0]['requestedUser']['userName'])) || (!$GLOBALS['ombiLimitUser']) || qualifyRequest(1) ? true : false;
							if ($proceed) {
								$requests[] = array(
									'test' => $value,
									'id' => $value['tvDbId'],
									'title' => $value['title'],
									'overview' => $value['overview'],
									'poster' => $value['posterPath'],
									'background' => (isset($value['background']) && $value['background'] !== '') ? 'https://image.tmdb.org/t/p/w1280/' . $value['background'] : '',
									'approved' => $value['childRequests'][0]['approved'],
									'available' => $value['childRequests'][0]['available'],
									'denied' => $value['childRequests'][0]['denied'],
									'deniedReason' => $value['childRequests'][0]['deniedReason'],
									'user' => $value['childRequests'][0]['requestedUser']['userName'],
									'userAlias' => $value['childRequests'][0]['requestedUser']['userAlias'],
									'request_id' => $value['id'],
									'request_date' => $value['childRequests'][0]['requestedDate'],
									'release_date' => $value['releaseDate'],
									'type' => 'tv',
									'icon' => 'mdi mdi-television',
									'color' => 'grayish-blue-bg',
								);
							}
						}
					}
				}
				//sort here
				usort($requests, function ($item1, $item2) {
					if ($item1['request_date'] == $item2['request_date']) {
						return 0;
					}
					return $item1['request_date'] > $item2['request_date'] ? -1 : 1;
				});
			}
		} catch (Requests_Exception $e) {
			writeLog('error', 'OMBI Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
		};
	}
	$api['content'] = isset($requests) ? array_slice($requests, 0, $limit) : false;
	return $api;
}

function testAPIConnection($array)
{
	switch ($array['data']['action']) {
		case 'ombi':
			if (!empty($GLOBALS['ombiURL']) && !empty($GLOBALS['ombiToken'])) {
				$url = qualifyURL($GLOBALS['ombiURL']);
				$url = $url . "/api/v1/Status/info";
				$headers = array(
					"Accept" => "application/json",
					"Content-Type" => "application/json",
					"Apikey" => $GLOBALS['ombiToken']
				);
				try {
					$options = (localURL($url)) ? array('verify' => false) : array();
					$response = Requests::get($url, $headers, $options);
					if ($response->success) {
						return true;
					} else {
						return $response->body;
					}
				} catch (Requests_Exception $e) {
					return $e->getMessage();
				};
			} else {
				return 'URL and/or Token not setup';
			}
			break;
		case 'plex':
			if (!empty($GLOBALS['plexURL']) && !empty($GLOBALS['plexToken'])) {
				$url = qualifyURL($GLOBALS['plexURL']);
				$url = $url . "/?X-Plex-Token=" . $GLOBALS['plexToken'];
				try {
					$options = (localURL($url)) ? array('verify' => false) : array();
					$response = Requests::get($url, array(), $options);
					libxml_use_internal_errors(true);
					if ($response->success) {
						return true;
					}
				} catch (Requests_Exception $e) {
					return $e->getMessage();
				};
			} else {
				return 'URL and/or Token not setup';
			}
			break;
		case 'emby':
			break;
		case 'sonarr':
			if (!empty($GLOBALS['sonarrURL']) && !empty($GLOBALS['sonarrToken'])) {
				$sonarrs = array();
				$sonarrURLList = explode(',', $GLOBALS['sonarrURL']);
				$sonarrTokenList = explode(',', $GLOBALS['sonarrToken']);
				if (count($sonarrURLList) == count($sonarrTokenList)) {
					foreach ($sonarrURLList as $key => $value) {
						$sonarrs[$key] = array(
							'url' => $value,
							'token' => $sonarrTokenList[$key]
						);
					}
					foreach ($sonarrs as $key => $value) {
						try {
							$sonarr = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token']);
							$result = json_decode($sonarr->getSystemStatus(), true);
							return (array_key_exists('error', $result)) ? $result['error']['msg'] : true;
						} catch (Exception $e) {
							return strip($e->getMessage());
						}
					}
				}
			} else {
				return 'URL/s and/or Token/s not setup';
			}
			break;
		case 'lidarr':
			if (!empty($GLOBALS['lidarrURL']) && !empty($GLOBALS['lidarrToken'])) {
				$sonarrs = array();
				$sonarrURLList = explode(',', $GLOBALS['lidarrURL']);
				$sonarrTokenList = explode(',', $GLOBALS['lidarrToken']);
				if (count($sonarrURLList) == count($sonarrTokenList)) {
					foreach ($sonarrURLList as $key => $value) {
						$sonarrs[$key] = array(
							'url' => $value,
							'token' => $sonarrTokenList[$key]
						);
					}
					foreach ($sonarrs as $key => $value) {
						try {
							$sonarr = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token'], true);
							$result = json_decode($sonarr->getSystemStatus(), true);
							return (array_key_exists('error', $result)) ? $result['error']['msg'] : true;
						} catch (Exception $e) {
							return $e->getMessage();
						}
					}
				}
			} else {
				return 'URL/s and/or Token/s not setup';
			}
			break;
		case 'radarr':
			if (!empty($GLOBALS['radarrURL']) && !empty($GLOBALS['radarrToken'])) {
				$sonarrs = array();
				$sonarrURLList = explode(',', $GLOBALS['radarrURL']);
				$sonarrTokenList = explode(',', $GLOBALS['radarrToken']);
				if (count($sonarrURLList) == count($sonarrTokenList)) {
					foreach ($sonarrURLList as $key => $value) {
						$sonarrs[$key] = array(
							'url' => $value,
							'token' => $sonarrTokenList[$key]
						);
					}
					foreach ($sonarrs as $key => $value) {
						try {
							$sonarr = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token']);
							$result = json_decode($sonarr->getSystemStatus(), true);
							return (array_key_exists('error', $result)) ? $result['error']['msg'] : true;
						} catch (Exception $e) {
							return $e->getMessage();
						}
					}
				}
			} else {
				return 'URL/s and/or Token/s not setup';
			}
			break;
        case 'jdownloader':
            if (!empty($GLOBALS['jdownloaderURL'])) {
                $url = qualifyURL($GLOBALS['jdownloaderURL']);
                try {
                    $options = (localURL($url)) ? array('verify' => false) : array();
                    $response = Requests::get($url, array(), $options);
                    if ($response->success) {
                        return true;
                    }
                } catch (Requests_Exception $e) {
                    return $e->getMessage();
                };
            } else {
                return 'URL and/or Token not setup';
            }
            break;
		case 'sabnzbd':
			if (!empty($GLOBALS['sabnzbdURL']) && !empty($GLOBALS['sabnzbdToken'])) {
				$url = qualifyURL($GLOBALS['sabnzbdURL']);
				$url = $url . '/api?mode=queue&output=json&apikey=' . $GLOBALS['sabnzbdToken'];
				try {
					$options = (localURL($url)) ? array('verify' => false) : array();
					$response = Requests::get($url, array(), $options);
					if ($response->success) {
						return true;
					}
				} catch (Requests_Exception $e) {
					return $e->getMessage();
				};
			} else {
				return 'URL and/or Token not setup';
			}
			break;
		case 'nzbget':
			if (!empty($GLOBALS['nzbgetURL'])) {
				$url = qualifyURL($GLOBALS['nzbgetURL']);
				if (!empty($GLOBALS['nzbgetUsername']) && !empty($GLOBALS['nzbgetPassword'])) {
					$url = $url . '/' . $GLOBALS['nzbgetUsername'] . ':' . decrypt($GLOBALS['nzbgetPassword']) . '/jsonrpc/listgroups';
				} else {
					$url = $url . '/jsonrpc/listgroups';
				}
				try {
					$options = (localURL($url)) ? array('verify' => false) : array();
					$response = Requests::get($url, array(), $options);
					if ($response->success) {
						return true;
					}
				} catch (Requests_Exception $e) {
					return $e->getMessage();
				};
			} else {
				return 'URL and/or Username/Password not setup';
			}
			break;
		case 'deluge':
			if (!empty($GLOBALS['delugeURL']) && !empty($GLOBALS['delugePassword'])) {
				try {
					$deluge = new deluge($GLOBALS['delugeURL'], decrypt($GLOBALS['delugePassword']));
					$torrents = $deluge->getTorrents(null, 'comment, download_payload_rate, eta, hash, is_finished, is_seed, message, name, paused, progress, queue, state, total_size, upload_payload_rate');
					return true;
				} catch (Exception $e) {
					return $e->getMessage();
				}
			} else {
				return 'URL and/or Password not setup';
			}
			break;
		case 'rtorrent':
			if (!empty($GLOBALS['rTorrentURL'])) {
				try {
					$digest = (empty($GLOBALS['rTorrentURLOverride'])) ? qualifyURL($GLOBALS['rTorrentURL'], true) : qualifyURL(checkOverrideURL($GLOBALS['rTorrentURL'], $GLOBALS['rTorrentURLOverride']), true);
					$passwordInclude = ($GLOBALS['rTorrentUsername'] != '' && $GLOBALS['rTorrentPassword'] != '') ? $GLOBALS['rTorrentUsername'] . ':' . decrypt($GLOBALS['rTorrentPassword']) . "@" : '';
					$extraPath = (strpos($GLOBALS['rTorrentURL'], '.php') !== false) ? '' : '/RPC2';
					$extraPath = (empty($GLOBALS['rTorrentURLOverride'])) ? $extraPath : '';
					$url = $digest['scheme'] . '://' . $passwordInclude . $digest['host'] . $digest['port'] . $digest['path'] . $extraPath;
					$options = (localURL($url)) ? array('verify' => false) : array();
					$data = xmlrpc_encode_request("system.listMethods", null);
					$response = Requests::post($url, array(), $data, $options);
					if ($response->success) {
						$methods = xmlrpc_decode(str_replace('i8>', 'i4>', $response->body));
						if (count($methods) !== 0) {
							return true;
						}
					}
					return false;
				} catch
				(Requests_Exception $e) {
					writeLog('error', 'rTorrent Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
					return $e->getMessage();
				};
			}
			break;
		case 'ldap_login':
			$username = $array['data']['data']['username'];
			$password = $array['data']['data']['password'];
			if (empty($username) || empty($password)) {
				return 'Missing Username or Password';
			}
			if (!empty($GLOBALS['authBaseDN']) && !empty($GLOBALS['authBackendHost'])) {
				$ad = new \Adldap\Adldap();
				// Create a configuration array.
				$ldapServers = explode(',', $GLOBALS['authBackendHost']);
				$i = 0;
				foreach ($ldapServers as $key => $value) {
					// Calculate parts
					$digest = parse_url(trim($value));
					$scheme = strtolower((isset($digest['scheme']) ? $digest['scheme'] : 'ldap'));
					$host = (isset($digest['host']) ? $digest['host'] : (isset($digest['path']) ? $digest['path'] : ''));
					$port = (isset($digest['port']) ? $digest['port'] : (strtolower($scheme) == 'ldap' ? 389 : 636));
					// Reassign
					$ldapHosts[] = $host;
					$ldapServersNew[$key] = $scheme . '://' . $host . ':' . $port; // May use this later
					if ($i == 0) {
						$ldapPort = $port;
					}
					$i++;
				}
				$config = [
					// Mandatory Configuration Options
					'hosts' => $ldapHosts,
					'base_dn' => $GLOBALS['authBaseDN'],
					'username' => (empty($GLOBALS['ldapBindUsername'])) ? null : $GLOBALS['ldapBindUsername'],
					'password' => (empty($GLOBALS['ldapBindPassword'])) ? null : decrypt($GLOBALS['ldapBindPassword']),
					// Optional Configuration Options
					'schema' => (($GLOBALS['ldapType'] == '1') ? Adldap\Schemas\ActiveDirectory::class : (($GLOBALS['ldapType'] == '2') ? Adldap\Schemas\OpenLDAP::class : Adldap\Schemas\FreeIPA::class)),
					'account_prefix' => (empty($GLOBALS['authBackendHostPrefix'])) ? null : $GLOBALS['authBackendHostPrefix'],
					'account_suffix' => (empty($GLOBALS['authBackendHostSuffix'])) ? null : $GLOBALS['authBackendHostSuffix'],
					'port' => $ldapPort,
					'follow_referrals' => false,
					'use_ssl' => false,
					'use_tls' => false,
					'version' => 3,
					'timeout' => 5,
					// Custom LDAP Options
					'custom_options' => [
						// See: http://php.net/ldap_set_option
						//LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_HARD
					]
				];
				// Add a connection provider to Adldap.
				$ad->addProvider($config);
				try {
					// If a successful connection is made to your server, the provider will be returned.
					$provider = $ad->connect();
					//prettyPrint($provider);
					if ($provider->auth()->attempt($username, $password, true)) {
						// Passed.
						$user = $provider->search()->find($username);
						//return $user->getFirstAttribute('cn');
						//return $user->getGroups(['cn']);
						//return $user;
						//return $user->getUserPrincipalName();
						//return $user->getGroups(['cn']);
						return true;
					} else {
						// Failed.
						return 'Username/Password Failed to authenticate';
					}
				} catch (\Adldap\Auth\BindException $e) {
					$detailedError = $e->getDetailedError();
					writeLog('error', 'LDAP Function - Error: ' . $detailedError->getErrorMessage(), $username);
					return $detailedError->getErrorMessage();
					// There was an issue binding / connecting to the server.
				} catch (Adldap\Auth\UsernameRequiredException $e) {
					$detailedError = $e->getDetailedError();
					writeLog('error', 'LDAP Function - Error: ' . $detailedError->getErrorMessage(), $username);
					return $detailedError->getErrorMessage();
					// The user didn't supply a username.
				} catch (Adldap\Auth\PasswordRequiredException $e) {
					$detailedError = $e->getDetailedError();
					writeLog('error', 'LDAP Function - Error: ' . $detailedError->getErrorMessage(), $username);
					return $detailedError->getErrorMessage();
					// The user didn't supply a password.
				}
			}
			break;
		case 'ldap':
			if (!empty($GLOBALS['authBaseDN']) && !empty($GLOBALS['authBackendHost'])) {
				$ad = new \Adldap\Adldap();
				// Create a configuration array.
				$ldapServers = explode(',', $GLOBALS['authBackendHost']);
				$i = 0;
				foreach ($ldapServers as $key => $value) {
					// Calculate parts
					$digest = parse_url(trim($value));
					$scheme = strtolower((isset($digest['scheme']) ? $digest['scheme'] : 'ldap'));
					$host = (isset($digest['host']) ? $digest['host'] : (isset($digest['path']) ? $digest['path'] : ''));
					$port = (isset($digest['port']) ? $digest['port'] : (strtolower($scheme) == 'ldap' ? 389 : 636));
					// Reassign
					$ldapHosts[] = $host;
					if ($i == 0) {
						$ldapPort = $port;
					}
					$i++;
				}
				$config = [
					// Mandatory Configuration Options
					'hosts' => $ldapHosts,
					'base_dn' => $GLOBALS['authBaseDN'],
					'username' => (empty($GLOBALS['ldapBindUsername'])) ? null : $GLOBALS['ldapBindUsername'],
					'password' => (empty($GLOBALS['ldapBindPassword'])) ? null : decrypt($GLOBALS['ldapBindPassword']),
					// Optional Configuration Options
					'schema' => (($GLOBALS['ldapType'] == '1') ? Adldap\Schemas\ActiveDirectory::class : (($GLOBALS['ldapType'] == '2') ? Adldap\Schemas\OpenLDAP::class : Adldap\Schemas\FreeIPA::class)),
					'account_prefix' => '',
					'account_suffix' => '',
					'port' => $ldapPort,
					'follow_referrals' => false,
					'use_ssl' => false,
					'use_tls' => false,
					'version' => 3,
					'timeout' => 5,
					// Custom LDAP Options
					'custom_options' => [
						// See: http://php.net/ldap_set_option
						//LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_HARD
					]
				];
				// Add a connection provider to Adldap.
				$ad->addProvider($config);
				try {
					// If a successful connection is made to your server, the provider will be returned.
					$provider = $ad->connect();
				} catch (\Adldap\Auth\BindException $e) {
					$detailedError = $e->getDetailedError();
					writeLog('error', 'LDAP Function - Error: ' . $detailedError->getErrorMessage(), 'SYSTEM');
					return $detailedError->getErrorMessage();
					// There was an issue binding / connecting to the server.
				}
				return ($provider) ? true : false;
			}
			return false;
			break;
		default :
			return false;
	}
	return false;
}
