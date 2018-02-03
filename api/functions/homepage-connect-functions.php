<?php

function homepageConnect($array){
	switch ($array['data']['action']) {
        case 'getPlexStreams':
			return plexConnect('streams');
            break;
		case 'getPlexRecent':
            return plexConnect('recent');
			break;
        case 'getPlexMetadata':
            return plexConnect('metadata',$array['data']['key']);
			break;
        default:
            # code...
            break;
    }
}
function streamType($value){
    if($value == "transcode" || $value == "Transcode"){
        return "Transcode";
    }elseif($value == "copy" || $value == "DirectStream"){
        return "Direct Stream";
    }elseif($value == "directplay" || $value == "DirectPlay"){
        return "Direct Play";
    }else{
        return "Direct Play";
    }
}
function resolveEmbyItem($itemDetails) {
    // Grab Each item info from Emby (extra call)
    $url = qualifyURL($GLOBALS['embyURL']);
    $url = $url.'/Items?Ids='.$itemDetails['Id'].'&api_key='.$GLOBALS['embyToken'];
    try{
        $options = (localURL($url)) ? array('verify' => false ) : array();
        $response = Requests::get($url, array(), $options);
        if($response->success){
            $item = json_decode($response->body,true)['Items'][0];
        }
    }catch( Requests_Exception $e ) {
        return false;
    };
    // Static Height & Width
    $height = 300;
    $width = 200;
    $nowPlayingHeight = 675;
    $nowPlayingWidth = 1200;
	$widthOverride = 100;
    // Cache Directories
    $cacheDirectory = dirname(__DIR__,2).DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
    $cacheDirectoryWeb = 'plugins/images/cache/';
    // Types
    switch ($itemDetails['Type']) {
    	case 'Series':
        case 'Episode':
            $embyItem['type'] = 'tv';
            $embyItem['title'] = $item['Name'];
            $embyItem['summary'] = '';
            $embyItem['ratingKey'] = $item['Id'];
            $embyItem['thumb'] = $item['ImageTags']['Primary'];
            $embyItem['key'] = $item['Id'] . "-list";
            $embyItem['nowPlayingThumb'] = $item['ImageTags']['Thumb'];
            $embyItem['nowPlayingKey'] = $item['Id'] . "-np";
            $embyItem['metadataKey'] = $item['Id'];
            break;
        case 'TvChannel':
            //$useImage = (isset($item['live']) ? "plugins/images/cache/livetv.png" : null);
            $embyItem['type'] = 'clip';
            /*$embyItem['title'] = (string)$item['title'];
            $embyItem['summary'] = (string)$item['summary'];
            $embyItem['ratingKey'] = (string)$item['parentRatingKey'];
            $embyItem['thumb'] = (string)$item['thumb'];
            $embyItem['key'] = (string)$item['ratingKey'] . "-list";
            $embyItem['nowPlayingThumb'] = (string)$item['art'];
            $embyItem['nowPlayingKey'] = isset($item['ratingKey']) ? (string)$item['ratingKey'] . "-np" : (isset($item['live']) ? "livetv.png" : ":)");
            $embyItem['nowPlayingTitle'] = $embyItem['title'];
            $embyItem['nowPlayingBottom'] = isset($item['extraType']) ? "Trailer" : (isset($item['live']) ? "Live TV" : ":)");*/
            break;
        case 'MusicAlbum':
        case 'Audio':
            $embyItem['type'] = 'music';
            /*$embyItem['title'] = (string)$item['parentTitle'];
            $embyItem['summary'] = (string)$item['title'];
			$embyItem['ratingKey'] = (string)$item['parentRatingKey'];
            $embyItem['thumb'] = (string)$item['thumb'];
            $embyItem['key'] = (string)$item['ratingKey'] . "-list";
			$embyItem['nowPlayingThumb'] = ($item['parentThumb']) ? (string)$item['parentThumb'] :  (string)$item['art'];
            $embyItem['nowPlayingKey'] = (string)$item['ratingKey'] . "-np";
            $embyItem['nowPlayingTitle'] = (string)$item['grandparentTitle'].' - '.(string)$item['title'];
            $embyItem['nowPlayingBottom'] = (string)$item['parentTitle'];
            $embyItem['metadataKey'] = isset($item['grandparentRatingKey']) ? (string)$item['grandparentRatingKey'] : (string)$item['parentRatingKey'];*/
            break;
        default:
            $embyItem['type'] = 'movie';
            $embyItem['title'] = $item['Name'];
            $embyItem['summary'] = '';
            $embyItem['ratingKey'] = $item['Id'];
            $embyItem['thumb'] = $item['ImageTags']['Primary'];
            $embyItem['key'] = $item['Id'] . "-list";
            $embyItem['nowPlayingThumb'] = $item['ImageTags']['Thumb'];
            $embyItem['nowPlayingKey'] = $item['Id'] . "-np";
            $embyItem['metadataKey'] = $item['Id'];
            //$embyItem['nowPlayingTitle'] = (string)$item['title'];
            //$embyItem['nowPlayingBottom'] = (string)$item['year'];
	}
    /*$embyItem['uid'] = (string)$item['ratingKey'];
    $embyItem['elapsed'] = isset($item['viewOffset']) && $item['viewOffset'] !== '0' ? (int)$item['viewOffset'] : null;
    $embyItem['duration'] = isset($item['duration']) ? (int)$item['duration'] : (int)$item->Media['duration'];
    $embyItem['watched'] = ($embyItem['elapsed'] && $embyItem['duration'] ? floor(($embyItem['elapsed'] / $embyItem['duration']) * 100) : 0);
    $embyItem['transcoded'] = isset($item->TranscodeSession['progress']) ? floor((int)$item->TranscodeSession['progress']- $embyItem['watched']) : '';
    $embyItem['stream'] = isset($item->Media->Part->Stream['decision']) ? (string)$item->Media->Part->Stream['decision']: '';
    $embyItem['id'] = str_replace('"', '', (string)$item->Player['machineIdentifier']);
    $embyItem['session'] = (string)$item->Session['id'];
    $embyItem['bandwidth'] = (string)$item->Session['bandwidth'];
    $embyItem['bandwidthType'] = (string)$item->Session['location'];
    $embyItem['sessionType'] = isset($item->TranscodeSession['progress']) ? 'Transcoding' : 'Direct Playing';
    $embyItem['state'] = (((string)$item->Player['state'] == "paused") ? "pause" : "play");
    $embyItem['user'] = ($GLOBALS['homepageShowStreamNames'] && qualifyRequest($GLOBALS['homepageShowStreamNamesAuth']) ) ? (string)$item->User['title'] : "";
    $embyItem['userThumb'] = ($GLOBALS['homepageShowStreamNames'] && qualifyRequest($GLOBALS['homepageShowStreamNamesAuth']) ) ? (string)$item->User['thumb'] : "";
    $embyItem['userAddress'] = ($GLOBALS['homepageShowStreamNames'] && qualifyRequest($GLOBALS['homepageShowStreamNamesAuth']) ) ? (string)$item->Player['address'] : "x.x.x.x";
    $embyItem['address'] = $GLOBALS['plexTabURL'] ? $GLOBALS['plexTabURL']."/web/index.html#!/server/".$GLOBALS['plexID']."/details?key=/library/metadata/".$item['ratingKey'] : "https://app.plex.tv/web/app#!/server/".$GLOBALS['plexID']."/details?key=/library/metadata/".$item['ratingKey'];
    $embyItem['nowPlayingOriginalImage'] = 'api/?v1/image&source=plex&img='.$embyItem['nowPlayingThumb'].'&height='.$nowPlayingHeight.'&width='.$nowPlayingWidth.'&key='.$embyItem['nowPlayingKey'].'$'.randString();
    $embyItem['originalImage'] = 'api/?v1/image&source=plex&img='.$embyItem['thumb'].'&height='.$height.'&width='.$width.'&key='.$embyItem['key'].'$'.randString();
    $embyItem['openTab'] = $GLOBALS['plexTabURL'] && $GLOBALS['plexTabName'] ? true : false;
    $embyItem['tabName'] = $GLOBALS['plexTabName'] ? $GLOBALS['plexTabName'] : '';
    // Stream info
    $embyItem['userStream'] = array(
        'platform' => (string)$item->Player['platform'],
        'product' => (string)$item->Player['product'],
        'device' => (string)$item->Player['device'],
        'stream' => (string)$item->Media->Part['decision'].($item->TranscodeSession['throttled'] == '1' ? ' (Throttled)': ''),
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
    if($item->Genre){
        $genres = array();
        foreach ($item->Genre as $key => $value) {
            $genres[] = (string)$value['tag'];
        }
    }
    // Actor catch all
    if($item->Role ){
        $actors = array();
        foreach ($item->Role  as $key => $value) {
            if($value['thumb']){
                $actors[] = array(
                    'name' =>  (string)$value['tag'],
                    'role' =>  (string)$value['role'],
                    'thumb' =>  (string)$value['thumb']
                );
            }
        }
    }
    // Metadata information
    $embyItem['metadata'] = array(
        'guid' => (string)$item['guid'],
        'summary' => (string)$item['summary'],
        'rating' => (string)$item['rating'],
        'duration' => (string)$item['duration'],
        'originallyAvailableAt' => (string)$item['originallyAvailableAt'],
        'year' => (string)$item['year'],
        'studio' => (string)$item['studio'],
        'tagline' => (string)$item['tagline'],
        'genres' => ($item->Genre) ?  $genres : '',
        'actors' => ($item->Role) ?  $actors : ''
    );
    if (file_exists($cacheDirectory.$embyItem['nowPlayingKey'].'.jpg')){ $embyItem['nowPlayingImageURL'] = $cacheDirectoryWeb.$embyItem['nowPlayingKey'].'.jpg'; }
    if (file_exists($cacheDirectory.$embyItem['key'].'.jpg')){ $embyItem['imageURL']  = $cacheDirectoryWeb.$embyItem['key'].'.jpg'; }
    if (file_exists($cacheDirectory.$embyItem['nowPlayingKey'].'.jpg') && (time() - 604800) > filemtime($cacheDirectory.$embyItem['nowPlayingKey'].'.jpg') || !file_exists($cacheDirectory.$embyItem['nowPlayingKey'].'.jpg')) {
        $embyItem['nowPlayingImageURL'] = 'api/?v1/image&source=plex&img='.$embyItem['nowPlayingThumb'].'&height='.$nowPlayingHeight.'&width='.$nowPlayingWidth.'&key='.$embyItem['nowPlayingKey'].'';
    }
    if (file_exists($cacheDirectory.$embyItem['key'].'.jpg') && (time() - 604800) > filemtime($cacheDirectory.$embyItem['key'].'.jpg') || !file_exists($cacheDirectory.$embyItem['key'].'.jpg')) {
        $embyItem['imageURL'] = 'api/?v1/image&source=plex&img='.$embyItem['thumb'].'&height='.$height.'&width='.$width.'&key='.$embyItem['key'].'';
    }
    if(!$embyItem['nowPlayingThumb'] ){ $embyItem['nowPlayingOriginalImage']  = $embyItem['nowPlayingImageURL']  = "plugins/images/cache/no-np.png"; $embyItem['nowPlayingKey'] = "no-np"; }
    if(!$embyItem['thumb'] ){  $embyItem['originalImage'] = $embyItem['imageURL'] = "plugins/images/cache/no-list.png"; $embyItem['key'] = "no-list"; }
	if(isset($useImage)){ $embyItem['useImage'] = $useImage; }*/

    return $embyItem;
}
function resolvePlexItem($item) {
    // Static Height & Width
    $height = 300;
    $width = 200;
    $nowPlayingHeight = 675;
    $nowPlayingWidth = 1200;
	$widthOverride = 100;
    // Cache Directories
    $cacheDirectory = dirname(__DIR__,2).DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
    $cacheDirectoryWeb = 'plugins/images/cache/';
    // Types
    switch ($item['type']) {
    	case 'season':
            $plexItem['type'] = 'tv';
            $plexItem['title'] = (string)$item['parentTitle'];
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
            $plexItem['summary'] = (string)$item['title'];
            $plexItem['ratingKey'] = (string)$item['parentRatingKey'];
            $plexItem['thumb'] = ($item['parentThumb'] ? (string)$item['parentThumb'] : (string)$item['grandparentThumb']);
            $plexItem['key'] = (string)$item['ratingKey'] . "-list";
            $plexItem['nowPlayingThumb'] = (string)$item['art'];
            $plexItem['nowPlayingKey'] = (string)$item['ratingKey'] . "-np";
            $plexItem['nowPlayingTitle'] = (string)$item['grandparentTitle'].' - '.(string)$item['title'];
            $plexItem['nowPlayingBottom'] = 'S'.(string)$item['parentIndex'].' · E'.(string)$item['index'];
            $plexItem['metadataKey'] = (string)$item['grandparentRatingKey'];
            break;
        case 'clip':
            $useImage = (isset($item['live']) ? "plugins/images/cache/livetv.png" : null);
            $plexItem['type'] = 'clip';
            $plexItem['title'] = (string)$item['title'];
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
            $plexItem['summary'] = (string)$item['title'];
			$plexItem['ratingKey'] = (string)$item['parentRatingKey'];
            $plexItem['thumb'] = (string)$item['thumb'];
            $plexItem['key'] = (string)$item['ratingKey'] . "-list";
			$plexItem['nowPlayingThumb'] = ($item['parentThumb']) ? (string)$item['parentThumb'] :  (string)$item['art'];
            $plexItem['nowPlayingKey'] = (string)$item['ratingKey'] . "-np";
            $plexItem['nowPlayingTitle'] = (string)$item['grandparentTitle'].' - '.(string)$item['title'];
            $plexItem['nowPlayingBottom'] = (string)$item['parentTitle'];
            $plexItem['metadataKey'] = isset($item['grandparentRatingKey']) ? (string)$item['grandparentRatingKey'] : (string)$item['parentRatingKey'];
            break;
        default:
            $plexItem['type'] = 'movie';
            $plexItem['title'] = (string)$item['title'];
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
    $plexItem['watched'] = ($plexItem['elapsed'] && $plexItem['duration'] ? floor(($plexItem['elapsed'] / $plexItem['duration']) * 100) : 0);
    $plexItem['transcoded'] = isset($item->TranscodeSession['progress']) ? floor((int)$item->TranscodeSession['progress']- $plexItem['watched']) : '';
    $plexItem['stream'] = isset($item->Media->Part->Stream['decision']) ? (string)$item->Media->Part->Stream['decision']: '';
    $plexItem['id'] = str_replace('"', '', (string)$item->Player['machineIdentifier']);
    $plexItem['session'] = (string)$item->Session['id'];
    $plexItem['bandwidth'] = (string)$item->Session['bandwidth'];
    $plexItem['bandwidthType'] = (string)$item->Session['location'];
    $plexItem['sessionType'] = isset($item->TranscodeSession['progress']) ? 'Transcoding' : 'Direct Playing';
    $plexItem['state'] = (((string)$item->Player['state'] == "paused") ? "pause" : "play");
    $plexItem['user'] = ($GLOBALS['homepageShowStreamNames'] && qualifyRequest($GLOBALS['homepageShowStreamNamesAuth']) ) ? (string)$item->User['title'] : "";
    $plexItem['userThumb'] = ($GLOBALS['homepageShowStreamNames'] && qualifyRequest($GLOBALS['homepageShowStreamNamesAuth']) ) ? (string)$item->User['thumb'] : "";
    $plexItem['userAddress'] = ($GLOBALS['homepageShowStreamNames'] && qualifyRequest($GLOBALS['homepageShowStreamNamesAuth']) ) ? (string)$item->Player['address'] : "x.x.x.x";
    $plexItem['address'] = $GLOBALS['plexTabURL'] ? $GLOBALS['plexTabURL']."/web/index.html#!/server/".$GLOBALS['plexID']."/details?key=/library/metadata/".$item['ratingKey'] : "https://app.plex.tv/web/app#!/server/".$GLOBALS['plexID']."/details?key=/library/metadata/".$item['ratingKey'];
    $plexItem['nowPlayingOriginalImage'] = 'api/?v1/image&source=plex&img='.$plexItem['nowPlayingThumb'].'&height='.$nowPlayingHeight.'&width='.$nowPlayingWidth.'&key='.$plexItem['nowPlayingKey'].'$'.randString();
    $plexItem['originalImage'] = 'api/?v1/image&source=plex&img='.$plexItem['thumb'].'&height='.$height.'&width='.$width.'&key='.$plexItem['key'].'$'.randString();
    $plexItem['openTab'] = $GLOBALS['plexTabURL'] && $GLOBALS['plexTabName'] ? true : false;
    $plexItem['tabName'] = $GLOBALS['plexTabName'] ? $GLOBALS['plexTabName'] : '';
    // Stream info
    $plexItem['userStream'] = array(
        'platform' => (string)$item->Player['platform'],
        'product' => (string)$item->Player['product'],
        'device' => (string)$item->Player['device'],
        'stream' => (string)$item->Media->Part['decision'].($item->TranscodeSession['throttled'] == '1' ? ' (Throttled)': ''),
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
    if($item->Genre){
        $genres = array();
        foreach ($item->Genre as $key => $value) {
            $genres[] = (string)$value['tag'];
        }
    }
    // Actor catch all
    if($item->Role ){
        $actors = array();
        foreach ($item->Role  as $key => $value) {
            if($value['thumb']){
                $actors[] = array(
                    'name' =>  (string)$value['tag'],
                    'role' =>  (string)$value['role'],
                    'thumb' =>  (string)$value['thumb']
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
        'genres' => ($item->Genre) ?  $genres : '',
        'actors' => ($item->Role) ?  $actors : ''
    );
    if (file_exists($cacheDirectory.$plexItem['nowPlayingKey'].'.jpg')){ $plexItem['nowPlayingImageURL'] = $cacheDirectoryWeb.$plexItem['nowPlayingKey'].'.jpg'; }
    if (file_exists($cacheDirectory.$plexItem['key'].'.jpg')){ $plexItem['imageURL']  = $cacheDirectoryWeb.$plexItem['key'].'.jpg'; }
    if (file_exists($cacheDirectory.$plexItem['nowPlayingKey'].'.jpg') && (time() - 604800) > filemtime($cacheDirectory.$plexItem['nowPlayingKey'].'.jpg') || !file_exists($cacheDirectory.$plexItem['nowPlayingKey'].'.jpg')) {
        $plexItem['nowPlayingImageURL'] = 'api/?v1/image&source=plex&img='.$plexItem['nowPlayingThumb'].'&height='.$nowPlayingHeight.'&width='.$nowPlayingWidth.'&key='.$plexItem['nowPlayingKey'].'';
    }
    if (file_exists($cacheDirectory.$plexItem['key'].'.jpg') && (time() - 604800) > filemtime($cacheDirectory.$plexItem['key'].'.jpg') || !file_exists($cacheDirectory.$plexItem['key'].'.jpg')) {
        $plexItem['imageURL'] = 'api/?v1/image&source=plex&img='.$plexItem['thumb'].'&height='.$height.'&width='.$width.'&key='.$plexItem['key'].'';
    }
    if(!$plexItem['nowPlayingThumb'] ){ $plexItem['nowPlayingOriginalImage']  = $plexItem['nowPlayingImageURL']  = "plugins/images/cache/no-np.png"; $plexItem['nowPlayingKey'] = "no-np"; }
    if(!$plexItem['thumb'] ){  $plexItem['originalImage'] = $plexItem['imageURL'] = "plugins/images/cache/no-list.png"; $plexItem['key'] = "no-list"; }
	if(isset($useImage)){ $plexItem['useImage'] = $useImage; }
    return $plexItem;
}
function plexConnect($action,$key=null){
	if(!empty($GLOBALS['plexURL']) && !empty($GLOBALS['plexToken']) && !empty($GLOBALS['plexID'] && qualifyRequest($GLOBALS['homepagePlexAuth']))){
        $url = qualifyURL($GLOBALS['plexURL']);
        switch ($action) {
            case 'streams':
                $url = $url."/status/sessions?X-Plex-Token=".$GLOBALS['plexToken'];
                break;
            case 'recent':
                $url = $url."/library/recentlyAdded?X-Plex-Token=".$GLOBALS['plexToken'];
                break;
            case 'metadata':
                $url = $url."/library/metadata/".$key."?X-Plex-Token=".$GLOBALS['plexToken'];
                break;
            default:
                # code...
                break;
        }
		try{
			$options = (localURL($url)) ? array('verify' => false ) : array();
			$response = Requests::get($url, array(), $options);
			libxml_use_internal_errors(true);
			if($response->success){
				$items = array();
				$plex = simplexml_load_string($response->body);
				foreach($plex AS $child) {
					$items[] = resolvePlexItem($child);
				}
				$api['content'] = $items;
				$api['plexID'] = $GLOBALS['plexID'];
				$api['showNames'] = true;
				$api['group'] = '1';
				return $api;
			}
		}catch( Requests_Exception $e ) {
			writeLog('error', 'Plex Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
		};
	}
	return false;
}
function embyConnect($action,$key=null){
	if(!empty($GLOBALS['embyURL']) && !empty($GLOBALS['embyToken']) && qualifyRequest($GLOBALS['homepageEmbyAuth'])){
        $url = qualifyURL($GLOBALS['embyURL']);
        switch ($action) {
            case 'streams':
                $url = $url."/status/sessions?X-Plex-Token=".$GLOBALS['plexToken'];
                break;
            case 'recent':
                $username = false;
                if (isset($GLOBALS['organizrUser']['username'])) {
                    $username = strtolower($GLOBALS['organizrUser']['username']);
                }
                // Get A User
                $userIds = $url."/Users?api_key=".$GLOBALS['embyToken'];
                $showPlayed = true;
                try{
        			$options = (localURL($userIds)) ? array('verify' => false ) : array();
        			$response = Requests::get($userIds, array(), $options);
        			if($response->success){
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
        			}
        		}catch( Requests_Exception $e ) {
        			writeLog('error', 'Emby Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
        		};
                $url = $url.'/Users/'.$userId.'/Items/Latest?EnableImages=false&Limit=100&api_key='.$GLOBALS['embyToken'].($showPlayed?'':'&IsPlayed=false');
                break;
            case 'metadata':
                $url = $url."/library/metadata/".$key."?X-Plex-Token=".$GLOBALS['plexToken'];
                break;
            default:
                # code...
                break;
        }
		try{
			$options = (localURL($url)) ? array('verify' => false ) : array();
			$response = Requests::get($url, array(), $options);
			if($response->success){
				$items = array();
				$emby = json_decode($response->body, true);
				foreach($emby AS $child) {
					$items[] = resolveEmbyItem($child);
				}
				$api['content'] = $items;
				return $api;
			}
		}catch( Requests_Exception $e ) {
			writeLog('error', 'Plex Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
		};
	}
	return false;
}
