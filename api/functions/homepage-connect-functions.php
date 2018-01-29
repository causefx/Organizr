<?php

function homepageConnect($array){
	switch ($array['data']['action']) {
        case 'getPlexStreams':
			return getPlexStreams();
            break;
		case 'getPlexRecent':
        return getPlexRecent();
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
function resolvePlexItem($item) {
    // Static Height
    $height = 300;
    $width = 200;
    $nowPlayingHeight = 338;
    $nowPlayingWidth = 600;
	$widthOverride = 100;
    $cacheDirectory = dirname(__DIR__,2).DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
    $cacheDirectoryWeb = 'plugins/images/cache/';
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
            break;

        case 'clip':
            $useImage = (isset($item['live']) ? "images/livetv.png" : null);
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
		}
        $plexItem['elapsed'] = ($item['viewOffset']) ? (string)$item['viewOffset'] : null;
        $plexItem['duration'] = ($item['duration']) ? (string)$item['duration'] : (string)$item->Media['duration'];
        $plexItem['watched'] = (!empty($plexItem['elapsed']) ? floor(($plexItem['elapsed'] / $plexItem['duration']) * 100) : 0);
        $plexItem['transcoded'] = floor($item->TranscodeSession['progress']- $plexItem['watched']);
        $plexItem['stream'] = isset($item->Media->Part->Stream['decision']) ? (string)$item->Media->Part->Stream['decision']: '';
        $plexItem['id'] = str_replace('"', '', (string)$item->Player['machineIdentifier']);
        $plexItem['state'] = (((string)$item->Player['state'] == "paused") ? "pause" : "play");
        $plexItem['user'] = ($GLOBALS['homepageShowStreamNames'] && qualifyRequest(1) ) ? (string)$item->User['title'] : "";
        $plexItem['address'] = $GLOBALS['plexTabURL'] ? $GLOBALS['plexTabURL']."/web/index.html#!/server/".$GLOBALS['plexID']."/details?key=/library/metadata/".$item['ratingKey'] : "https://app.plex.tv/web/app#!/server/".$GLOBALS['plexID']."/details?key=/library/metadata/".$item['ratingKey'];
        $plexItem['nowPlayingOriginalImage'] = 'api/?v1/image&source=plex&img='.$plexItem['nowPlayingThumb'].'&height='.$nowPlayingHeight.'&width='.$nowPlayingWidth.'&key='.$plexItem['nowPlayingKey'].'$'.randString();
        $plexItem['originalImage'] = 'api/?v1/image&source=plex&img='.$plexItem['thumb'].'&height='.$height.'&width='.$width.'&key='.$plexItem['key'].'$'.randString();
        if (file_exists($cacheDirectory.$plexItem['nowPlayingKey'].'.jpg')){ $plexItem['nowPlayingImageURL'] = $cacheDirectoryWeb.$plexItem['nowPlayingKey'].'.jpg'; }
        if (file_exists($cacheDirectory.$plexItem['key'].'.jpg')){ $plexItem['imageURL']  = $cacheDirectoryWeb.$plexItem['key'].'.jpg'; }
        if (file_exists($cacheDirectory.$plexItem['nowPlayingKey'].'.jpg') && (time() - 604800) > filemtime($cacheDirectory.$plexItem['nowPlayingKey'].'.jpg') || !file_exists($cacheDirectory.$plexItem['nowPlayingKey'].'.jpg')) {
            $plexItem['nowPlayingImageURL'] = 'api/?v1/image&source=plex&img='.$plexItem['nowPlayingThumb'].'&height='.$nowPlayingHeight.'&width='.$nowPlayingWidth.'&key='.$plexItem['nowPlayingKey'].'';
        }
        if (file_exists($cacheDirectory.$plexItem['key'].'.jpg') && (time() - 604800) > filemtime($cacheDirectory.$plexItem['key'].'.jpg') || !file_exists($cacheDirectory.$plexItem['key'].'.jpg')) {
            $plexItem['imageURL'] = 'api/?v1/image&source=plex&img='.$plexItem['thumb'].'&height='.$height.'&width='.$width.'&key='.$plexItem['key'].'';
        }
        if(!$plexItem['nowPlayingThumb'] ){ $plexItem['nowPlayingOriginalImage']  = $plexItem['nowPlayingImageURL']  = "images/no-np.png"; $plexItem['nowPlayingKey'] = "no-np"; }
        if(!$plexItem['thumb'] ){  $plexItem['originalImage'] = $plexItem['imageURL'] = "images/no-list.png"; $plexItem['key'] = "no-list"; }

	if(isset($useImage)){ $plexItem['useImage'] = $useImage; }
    return $plexItem;

}
function getPlexStreams(){
	if(!empty($GLOBALS['plexURL']) && !empty($GLOBALS['plexToken']) && !empty($GLOBALS['plexID'] && qualifyRequest($GLOBALS['homepagePlexStreamsAuth']))){
		try{
			$url = qualifyURL($GLOBALS['plexURL']);
			$url = $url."/status/sessions?X-Plex-Token=".$GLOBALS['plexToken'];
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
function getPlexRecent(){
	if(!empty($GLOBALS['plexURL']) && !empty($GLOBALS['plexToken']) && !empty($GLOBALS['plexID'] && qualifyRequest($GLOBALS['homepagePlexRecentAuth']))){
		try{
			$url = qualifyURL($GLOBALS['plexURL']);
			$url = $url."/library/recentlyAdded?X-Plex-Token=".$GLOBALS['plexToken'];
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
