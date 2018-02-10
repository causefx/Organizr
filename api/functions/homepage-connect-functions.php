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
        case 'getEmbyStreams':
            return embyConnect('streams');
            break;
        case 'getEmbyRecent':
            return embyConnect('recent');
            break;
        case 'getEmbyMetadata':
            return embyConnect('metadata',$array['data']['key'],true);
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
        case 'getCalendar':
            return getCalendar();
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
    $id = isset($itemDetails['NowPlayingItem']['Id']) ? $itemDetails['NowPlayingItem']['Id'] : $itemDetails['Id'];
    $url = qualifyURL($GLOBALS['embyURL']);
    $url = $url.'/Items?Ids='.$id.'&api_key='.$GLOBALS['embyToken'].'&Fields=Overview,People,Genres,CriticRating,Studios,Taglines';
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
    $actorHeight = 450;
    $actorWidth = 300;
    $widthOverride = 100;
    // Cache Directories
    $cacheDirectory = dirname(__DIR__,2).DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
    $cacheDirectoryWeb = 'plugins/images/cache/';
    // Types
    $embyItem['array-item'] = $item;
    $embyItem['array-itemdetails'] = $itemDetails;

    switch (@$item['Type']) {
        case 'Series':
            $embyItem['type'] = 'tv';
            $embyItem['title'] = $item['Name'];
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
            $embyItem['summary'] = '';
            $embyItem['ratingKey'] = $item['Id'];
            $embyItem['thumb'] = (isset($item['SeriesId'])?$item['SeriesId']:$item['Id']);
            $embyItem['key'] = (isset($item['SeriesId'])?$item['SeriesId']:$item['Id']) . "-list";
            $embyItem['nowPlayingThumb'] = isset($item['ParentThumbItemId']) ? $item['ParentThumbItemId'] : (isset($item['ParentBackdropItemId']) ? $item['ParentBackdropItemId'] : false);
            $embyItem['nowPlayingKey'] = isset($item['ParentThumbItemId']) ? $item['ParentThumbItemId'].'-np' : (isset($item['ParentBackdropItemId']) ? $item['ParentBackdropItemId'].'-np' : false);
            $embyItem['metadataKey'] = $item['Id'];
            $embyItem['nowPlayingImageType'] = isset($item['ImageTags']['Thumb']) ? 'Thumb' : (isset($item['ParentBackdropImageTags'][0]) ? 'Backdrop' : '');
            $embyItem['nowPlayingTitle'] = @$item['SeriesName'].' - '.@$item['Name'];
            $embyItem['nowPlayingBottom'] = 'S'.@$item['ParentIndexNumber'].' · E'.@$item['IndexNumber'];
            break;
        case 'MusicAlbum':
        case 'Audio':
            $embyItem['type'] = 'music';
            $embyItem['title'] = $item['Name'];
            $embyItem['summary'] = '';
            $embyItem['ratingKey'] = $item['Id'];
            $embyItem['thumb'] = $item['Id'];
            $embyItem['key'] = $item['Id'] . "-list";
            $embyItem['nowPlayingThumb'] = (isset($item['AlbumId']) ? $item['AlbumId'] : @$item['ParentBackdropItemId']);
            $embyItem['nowPlayingKey'] = $item['Id'] . "-np";
            $embyItem['metadataKey'] = isset($item['AlbumId']) ? $item['AlbumId'] : $item['Id'];
            $embyItem['nowPlayingImageType'] = (isset($item['ParentBackdropItemId']) ? "Primary" : "Backdrop");
            $embyItem['nowPlayingTitle'] = @$item['AlbumArtist'].' - '.@$item['Name'];
            $embyItem['nowPlayingBottom'] = @$item['Album'];
            break;
        case 'Movie':
            $embyItem['type'] = 'movie';
            $embyItem['title'] = $item['Name'];
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
    $embyItem['duration'] = isset($itemDetails['NowPlayingItem']['RunTimeTicks']) ? (int)$itemDetails['NowPlayingItem']['RunTimeTicks'] : (int)$item['RunTimeTicks'];
    $embyItem['watched'] = ($embyItem['elapsed'] && $embyItem['duration'] ? floor(($embyItem['elapsed'] / $embyItem['duration']) * 100) : 0);
    $embyItem['transcoded'] = isset($itemDetails['TranscodingInfo']['CompletionPercentage']) ? floor((int)$itemDetails['TranscodingInfo']['CompletionPercentage']) : 100;
    $embyItem['stream'] = @$itemDetails['PlayState']['PlayMethod'];
    $embyItem['id'] = $item['ServerId'];
    $embyItem['session'] = @$itemDetails['DeviceId'];
    $embyItem['bandwidth'] = isset($itemDetails['TranscodingInfo']['Bitrate']) ? $itemDetails['TranscodingInfo']['Bitrate'] / 1000 : '';
    $embyItem['bandwidthType'] = 'wan';
    $embyItem['sessionType'] = (@$itemDetails['PlayState']['PlayMethod'] == 'Transcode') ? 'Transcoding' : 'Direct Playing';
    $embyItem['state'] = ((@(string)$itemDetails['PlayState']['IsPaused'] == '1') ? "pause" : "play");
    $embyItem['user'] = ($GLOBALS['homepageShowStreamNames'] && qualifyRequest($GLOBALS['homepageShowStreamNamesAuth']) ) ? @(string)$itemDetails['UserName'] : "";
    $embyItem['userThumb'] = '';
    $embyItem['userAddress'] = "x.x.x.x";
    $embyItem['address'] = $GLOBALS['embyTabURL'] ? '' : '';
    $embyItem['nowPlayingOriginalImage'] = 'api/?v1/image&source=emby&type='.$embyItem['nowPlayingImageType'].'&img='.$embyItem['nowPlayingThumb'].'&height='.$nowPlayingHeight.'&width='.$nowPlayingWidth.'&key='.$embyItem['nowPlayingKey'].'$'.randString();
    $embyItem['originalImage'] = 'api/?v1/image&source=emby&type='.$embyItem['imageType'].'&img='.$embyItem['thumb'].'&height='.$height.'&width='.$width.'&key='.$embyItem['key'].'$'.randString();
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
    if($item['Genres']){
        $genres = array();
        foreach ($item['Genres'] as $genre) {
            $genres[] = $genre;
        }
    }
    // Actor catch all
    if($item['People'] ){
        $actors = array();
        foreach ($item['People'] as $key => $value) {
            if(@$value['PrimaryImageTag'] && @$value['Role']){
                if (file_exists($cacheDirectory.(string)$value['Id'].'-cast.jpg')){ $actorImage = $cacheDirectoryWeb.(string)$value['Id'].'-cast.jpg'; }
                if (file_exists($cacheDirectory.(string)$value['Id'].'-cast.jpg') && (time() - 604800) > filemtime($cacheDirectory.(string)$value['Id'].'-cast.jpg') || !file_exists($cacheDirectory.(string)$value['Id'].'-cast.jpg')) {
                    $actorImage = 'api/?v1/image&source=emby&type=Primary&img='.(string)$value['Id'].'&height='.$actorHeight.'&width='.$actorWidth.'&key='.(string)$value['Id'].'-cast';
                }
                $actors[] = array(
                    'name' =>  (string)$value['Name'],
                    'role' =>  (string)$value['Role'],
                    'thumb' =>  $actorImage
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
        'year' => (string)$item['ProductionYear'],
        //'studio' => (string)$item['studio'],
        'tagline' => @(string)$item['Taglines'][0],
        'genres' => ($item['Genres']) ?  $genres : '',
        'actors' => ($item['People']) ?  $actors : ''
    );

    if (file_exists($cacheDirectory.$embyItem['nowPlayingKey'].'.jpg')){ $embyItem['nowPlayingImageURL'] = $cacheDirectoryWeb.$embyItem['nowPlayingKey'].'.jpg'; }
    if (file_exists($cacheDirectory.$embyItem['key'].'.jpg')){ $embyItem['imageURL']  = $cacheDirectoryWeb.$embyItem['key'].'.jpg'; }
    if (file_exists($cacheDirectory.$embyItem['nowPlayingKey'].'.jpg') && (time() - 604800) > filemtime($cacheDirectory.$embyItem['nowPlayingKey'].'.jpg') || !file_exists($cacheDirectory.$embyItem['nowPlayingKey'].'.jpg')) {
        $embyItem['nowPlayingImageURL'] = 'api/?v1/image&source=emby&type='.$embyItem['nowPlayingImageType'].'&img='.$embyItem['nowPlayingThumb'].'&height='.$nowPlayingHeight.'&width='.$nowPlayingWidth.'&key='.$embyItem['nowPlayingKey'].'';
    }
    if (file_exists($cacheDirectory.$embyItem['key'].'.jpg') && (time() - 604800) > filemtime($cacheDirectory.$embyItem['key'].'.jpg') || !file_exists($cacheDirectory.$embyItem['key'].'.jpg')) {
        $embyItem['imageURL'] = 'api/?v1/image&source=emby&type='.$embyItem['imageType'].'&img='.$embyItem['thumb'].'&height='.$height.'&width='.$width.'&key='.$embyItem['key'].'';
    }
    if(!$embyItem['nowPlayingThumb'] ){ $embyItem['nowPlayingOriginalImage']  = $embyItem['nowPlayingImageURL']  = "plugins/images/cache/no-np.png"; $embyItem['nowPlayingKey'] = "no-np"; }
    if(!$embyItem['thumb'] ){  $embyItem['originalImage'] = $embyItem['imageURL'] = "plugins/images/cache/no-list.png"; $embyItem['key'] = "no-list"; }
    if(isset($useImage)){ $embyItem['useImage'] = $useImage; }
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
    if($GLOBALS['homepagePlexEnabled'] && !empty($GLOBALS['plexURL']) && !empty($GLOBALS['plexToken']) && !empty($GLOBALS['plexID'] && qualifyRequest($GLOBALS['homepagePlexAuth']))){
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
function embyConnect($action,$key=null,$skip=false){
    if($GLOBALS['homepageEmbyEnabled'] && !empty($GLOBALS['embyURL']) && !empty($GLOBALS['embyToken']) && qualifyRequest($GLOBALS['homepageEmbyAuth'])){
        $url = qualifyURL($GLOBALS['embyURL']);
        switch ($action) {
            case 'streams':
                $url = $url.'/Sessions?api_key='.$GLOBALS['embyToken'];
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
                $skip = true;
                break;
            default:
                # code...
                break;
        }
        if($skip && $key){
            $items[] = resolveEmbyItem(array('Id'=>$key));
            $api['content'] = $items;
            return $api;
        }
        try{
            $options = (localURL($url)) ? array('verify' => false ) : array();
            $response = Requests::get($url, array(), $options);
            if($response->success){
                $items = array();
                $emby = json_decode($response->body, true);
                foreach($emby AS $child) {
                    if (isset($child['NowPlayingItem']) || isset($child['Name'])) {
                        $items[] = resolveEmbyItem($child);
                    }
                }
                $api['content'] = array_filter($items);
                return $api;
            }
        }catch( Requests_Exception $e ) {
            writeLog('error', 'Emby Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
        };
    }
    return false;
}
function sabnzbdConnect() {
    if($GLOBALS['homepageSabnzbdEnabled'] && !empty($GLOBALS['sabnzbdURL']) && !empty($GLOBALS['sabnzbdToken']) && qualifyRequest($GLOBALS['homepageSabnzbdAuth'])){
        $url = qualifyURL($GLOBALS['sabnzbdURL']);
        $url = $url.'/api?mode=queue&output=json&apikey='.$GLOBALS['sabnzbdToken'];
        try{
            $options = (localURL($url)) ? array('verify' => false ) : array();
            $response = Requests::get($url, array(), $options);
            if($response->success){
                $api['content']['queueItems'] = json_decode($response->body, true);
            }
        }catch( Requests_Exception $e ) {
            writeLog('error', 'SabNZBd Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
        };
        $url = qualifyURL($GLOBALS['sabnzbdURL']);
        $url = $url.'/api?mode=history&output=json&apikey='.$GLOBALS['sabnzbdToken'];
        try{
            $options = (localURL($url)) ? array('verify' => false ) : array();
            $response = Requests::get($url, array(), $options);
            if($response->success){
                $api['content']['historyItems']= json_decode($response->body, true);
            }
        }catch( Requests_Exception $e ) {
            writeLog('error', 'SabNZBd Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
        };
        $api['content'] = isset($api['content']) ? $api['content'] : false;
        return $api;
    }
}
function nzbgetConnect() {
    if($GLOBALS['homepageNzbgetEnabled'] && !empty($GLOBALS['nzbgetURL']) && qualifyRequest($GLOBALS['homepageNzbgetAuth'])){
        $url = qualifyURL($GLOBALS['nzbgetURL']);
        $url = $url.'/'.$GLOBALS['nzbgetUsername'].':'.decrypt($GLOBALS['nzbgetPassword']).'/jsonrpc/listgroups';
        try{
            $options = (localURL($url)) ? array('verify' => false ) : array();
            $response = Requests::get($url, array(), $options);
            if($response->success){
                $api['content']['queueItems'] = json_decode($response->body, true);
            }
        }catch( Requests_Exception $e ) {
            writeLog('error', 'NZBGet Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
        };
        $url = qualifyURL($GLOBALS['nzbgetURL']);
        $url = $url.'/'.$GLOBALS['nzbgetUsername'].':'.decrypt($GLOBALS['nzbgetPassword']).'/jsonrpc/history';
        try{
            $options = (localURL($url)) ? array('verify' => false ) : array();
            $response = Requests::get($url, array(), $options);
            if($response->success){
                $api['content']['historyItems']= json_decode($response->body, true);
            }
        }catch( Requests_Exception $e ) {
            writeLog('error', 'NZBGet Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
        };
        $api['content'] = isset($api['content']) ? $api['content'] : false;
        return $api;
    }
}
function transmissionConnect() {
    if($GLOBALS['homepageTransmissionEnabled'] && !empty($GLOBALS['transmissionURL']) && qualifyRequest($GLOBALS['homepageTransmissionAuth'])){
        $digest = qualifyURL($GLOBALS['transmissionURL'], true);
        $passwordInclude = ($GLOBALS['transmissionUsername'] != '' && $GLOBALS['transmissionPassword'] != '') ? $GLOBALS['transmissionUsername'].':'.decrypt($GLOBALS['transmissionPassword'])."@" : '';
        $url = $digest['scheme'].'://'.$passwordInclude.$digest['host'].$digest['port'].$digest['path'].'/rpc';
        try{
            $options = (localURL($GLOBALS['transmissionURL'])) ? array('verify' => false ) : array();
            $response = Requests::get($url, array(), $options);
            if($response->headers['x-transmission-session-id']){
                $headers = array(
                    'X-Transmission-Session-Id' => $response->headers['x-transmission-session-id'],
                    'Content-Type' => 'application/json'
                );
                $data = array(
                    'method' => 'torrent-get',
                    'arguments' => array(
                        'fields' => array(
                            "id", "name", "totalSize", "eta", "isFinished", "isStalled", "percentDone", "rateDownload", "status", "downloadDir","errorString"
                        ),
                    ),
                    'tags' => ''
                );
                $response = Requests::post($url, $headers, json_encode($data), $options);
                if($response->success){
                    $torrentList = json_decode($response->body, true)['arguments']['torrents'];
                    if($GLOBALS['transmissionHideSeeding'] || $GLOBALS['transmissionHideCompleted']){
                        $filter = array();
                        $torrents['arguments']['torrents'] = array();
                        if($GLOBALS['transmissionHideSeeding']){ array_push($filter, 6, 5); }
                        if($GLOBALS['transmissionHideCompleted']){ array_push($filter, 0); }
                        foreach ($torrentList as $key => $value) {
                            if(!in_array($value['status'], $filter)){
                                $torrents['arguments']['torrents'][] = $value;
                            }
                        }
                    }else{
                        $torrents = json_decode($response->body, true);
                    }
                    $api['content']['queueItems'] = $torrents;
                    $api['content']['historyItems'] = false;
                }
            }else{
                writeLog('error', 'Transmission Connect Function - Error: Could not get session ID', 'SYSTEM');
            }
        }catch( Requests_Exception $e ) {
            writeLog('error', 'Transmission Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
        };
        $api['content'] = isset($api['content']) ? $api['content'] : false;
        return $api;
    }
}
function qBittorrentConnect() {
    if($GLOBALS['homepageqBittorrentEnabled'] && !empty($GLOBALS['qBittorrentURL']) && qualifyRequest($GLOBALS['homepageqBittorrentAuth'])){
        $digest = qualifyURL($GLOBALS['qBittorrentURL'], true);
        $passwordInclude = ($GLOBALS['qBittorrentUsername'] != '' && $GLOBALS['qBittorrentPassword'] != '') ? 'username='.$GLOBALS['qBittorrentUsername'].'&password='.decrypt($GLOBALS['qBittorrentPassword'])."@" : '';
        $data = array('username'=>$GLOBALS['qBittorrentUsername'], 'password'=> decrypt($GLOBALS['qBittorrentPassword']));
        $url = $digest['scheme'].'://'.$digest['host'].$digest['port'].$digest['path'].'/login';
        try{
            $options = (localURL($GLOBALS['qBittorrentURL'])) ? array('verify' => false ) : array();
            $response = Requests::post($url, array(), $data, $options);
            $reflection = new ReflectionClass($response->cookies);
            $cookie = $reflection->getProperty("cookies");
            $cookie->setAccessible(true);
            $cookie = $cookie->getValue($response->cookies);
            if($cookie){
                $headers = array(
                    'Cookie' => 'SID=' . $cookie['SID']->value
                );
<<<<<<< HEAD
=======
                $sort = isset($GLOBALS['qBittorrentSortOrder']) ? $GLOBALS['qBittorrentSortOrder'] : 'eta';
>>>>>>> 355582442ea88ab493d4eb8c8914d5d18d6c25c1
                $reverse = $GLOBALS['qBittorrentReverseSorting'] ? 'true' : 'false';
                $url = $digest['scheme'].'://'.$digest['host'].$digest['port'].$digest['path'].'/query/torrents?sort=' . $sort . '&reverse=' . $reverse;
                $response = Requests::get($url, $headers, $options);
                if($response){
                    $torrentList = json_decode($response->body, true);
                    if($GLOBALS['qBittorrentHideSeeding'] || $GLOBALS['qBittorrentHideCompleted']){
                        $filter = array();
                        $torrents['arguments']['torrents'] = array();
                        if($GLOBALS['qBittorrentHideSeeding']){ array_push($filter, 'uploading', 'stalledUP', 'queuedUP'); }
                        if($GLOBALS['qBittorrentHideCompleted']){ array_push($filter, 'pausedUP'); }
                        foreach ($torrentList as $key => $value) {
                            if(!in_array($value['state'], $filter)){
                                $torrents['arguments']['torrents'][] = $value;
                            }
                        }
                    }else{
                        $torrents['arguments']['torrents'] = json_decode($response->body, true);
                    }
                    $api['content']['queueItems'] = $torrents;
                    $api['content']['historyItems'] = false;
                }
            }else{
                writeLog('error', 'qBittorrent Connect Function - Error: Could not get session ID', 'SYSTEM');
            }
        }catch( Requests_Exception $e ) {
            writeLog('error', 'qBittorrent Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
        };
        $api['content'] = isset($api['content']) ? $api['content'] : false;
        return $api;
    }
}
function getCalendar(){
    $startDate = date('Y-m-d',strtotime("-".$GLOBALS['calendarStart']." days"));
    $endDate = date('Y-m-d',strtotime("+".$GLOBALS['calendarEnd']." days"));
    $calendarItems = array();
    // SONARR CONNECT
    if($GLOBALS['homepageSonarrEnabled'] && qualifyRequest($GLOBALS['homepageSonarrAuth']) && !empty($GLOBALS['sonarrURL']) && !empty($GLOBALS['sonarrToken'])){
        $sonarrs = array();
        $sonarrURLList = explode(',', $GLOBALS['sonarrURL']);
        $sonarrTokenList = explode(',', $GLOBALS['sonarrToken']);
        if(count($sonarrURLList) == count($sonarrTokenList)){
            foreach ($sonarrURLList as $key => $value) {
                $sonarrs[$key] = array(
                    'url' => $value,
                    'token' => $sonarrTokenList[$key]
                );
            }
            foreach ($sonarrs as $key => $value) {
                try {
                    $sonarr = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token']);
                    $sonarrCalendar = getSonarrCalendar($sonarr->getCalendar($startDate, $endDate),$key);
                } catch (Exception $e) {
                    writeLog('error', 'Sonarr Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
                }
                if(!empty($sonarrCalendar)) { $calendarItems = array_merge($calendarItems, $sonarrCalendar); }
            }
        }
    }
    // RADARR CONNECT
    if($GLOBALS['homepageRadarrEnabled'] && qualifyRequest($GLOBALS['homepageRadarrAuth']) && !empty($GLOBALS['radarrURL']) && !empty($GLOBALS['radarrToken'])){
        $radarrs = array();
        $radarrURLList = explode(',', $GLOBALS['radarrURL']);
        $radarrTokenList = explode(',', $GLOBALS['radarrToken']);
        if(count($radarrURLList) == count($radarrTokenList)){
            foreach ($radarrURLList as $key => $value) {
                $radarrs[$key] = array(
                    'url' => $value,
                    'token' => $radarrTokenList[$key]
                );
            }
            foreach ($radarrs as $key => $value) {
                try {
                    $radarr = new Kryptonit3\Sonarr\Sonarr($value['url'], $value['token']);
                    $radarrCalendar = getRadarrCalendar($radarr->getCalendar($startDate, $endDate),$key);
                } catch (Exception $e) {
                    writeLog('error', 'Radarr Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
                }
                if(!empty($radarrCalendar)) { $calendarItems = array_merge($calendarItems, $radarrCalendar); }
            }
        }
    }
    // SICKRAGE/BEARD/MEDUSA CONNECT
    if($GLOBALS['homepageSickrageEnabled'] && qualifyRequest($GLOBALS['homepageSickrageAuth']) && !empty($GLOBALS['sickrageURL']) && !empty($GLOBALS['sickrageToken'])){
        $sicks = array();
        $sickURLList = explode(',', $GLOBALS['sickrageURL']);
        $sickTokenList = explode(',', $GLOBALS['sickrageToken']);
        if(count($sickURLList) == count($sickTokenList)){
            foreach ($sickURLList as $key => $value) {
                $sicks[$key] = array(
                    'url' => $value,
                    'token' => $sickTokenList[$key]
                );
            }
            foreach ($sicks as $key => $value) {
                try {
                    $sickrage = new Kryptonit3\SickRage\SickRage($value['url'], $value['token']);
                    $sickrageFuture = getSickrageCalendarWanted($sickrage->future(),$key);
                    $sickrageHistory = getSickrageCalendarHistory($sickrage->history("100","downloaded"),$key);
                    if(!empty($sickrageFuture)) { $calendarItems = array_merge($calendarItems, $sickrageFuture); }
                    if(!empty($sickrageHistory)) { $calendarItems = array_merge($calendarItems, $sickrageHistory); }
                } catch (Exception $e) {
                    writeLog('error', 'Sickrage Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
                }
            }
        }
    }
    // COUCHPOTATO CONNECT
    if($GLOBALS['homepageCouchpotatoEnabled'] && qualifyRequest($GLOBALS['homepageCouchpotatoAuth']) && !empty($GLOBALS['couchpotatoURL']) && !empty($GLOBALS['couchpotatoToken'])){
        $couchs = array();
        $couchpotatoURLList = explode(',', $GLOBALS['couchpotatoURL']);
        $couchpotatoTokenList = explode(',', $GLOBALS['couchpotatoToken']);
        if(count($couchpotatoURLList) == count($couchpotatoTokenList)){
            foreach ($couchpotatoURLList as $key => $value) {
                $couchs[$key] = array(
                    'url' => $value,
                    'token' => $couchpotatoTokenList[$key]
                );
            }
            foreach ($couchs as $key => $value) {
                try {
                    $couchpotato = new Kryptonit3\CouchPotato\CouchPotato($value['url'], $value['token']);
                    $couchCalendar = getCouchCalendar($couchpotato->getMediaList(),$key);
                    if(!empty($couchCalendar)) { $calendarItems = array_merge($calendarItems, $couchCalendar); }
                } catch (Exception $e) {
                    writeLog('error', 'Sickrage Connect Function - Error: '.$e->getMessage(), 'SYSTEM');
                }
            }
        }
    }


    return ($calendarItems) ? $calendarItems : false;
}
function getSonarrCalendar($array,$number){
    $array = json_decode($array, true);
    $gotCalendar = array();
    $i = 0;
    foreach($array AS $child) {
        $i++;
        $seriesName = $child['series']['title'];
        $episodeID = $child['series']['tvdbId'];
        if(!isset($episodeID)){ $episodeID = ""; }
        $episodeName = htmlentities($child['title'], ENT_QUOTES);
        if($child['episodeNumber'] == "1"){ $episodePremier = "true"; }else{ $episodePremier = "false"; }
        $episodeAirDate = $child['airDateUtc'];
        $episodeAirDate = strtotime($episodeAirDate);
        $episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
        if (new DateTime() < new DateTime($episodeAirDate)) { $unaired = true; }
        $downloaded = $child['hasFile'];
<<<<<<< HEAD
        if($downloaded == "0" && isset($unaired) && $episodePremier == "true"){ $downloaded = "text-primary"; }elseif($downloaded == "0" && isset($unaired)){ $downloaded = "text-info"; }elseif($downloaded == "1"){ $downloaded = "text-success"; }else{ $downloaded = "text-danger"; }
		array_push($gotCalendar, array(
			"id" => "Sonarr-".$number."-".$i,
			"title" => $seriesName,
			"start" => $child['airDateUtc'],
			"className" => "bg-calendar tvID--".$episodeID,
			"imagetype" => "tv ".$downloaded,
		));
=======
        if($downloaded == "0" && isset($unaired) && $episodePremier == "true"){ $downloaded = "bg-primary"; }elseif($downloaded == "0" && isset($unaired)){ $downloaded = "bg-info"; }elseif($downloaded == "1"){ $downloaded = "bg-success"; }else{ $downloaded = "bg-danger"; }
        array_push($gotCalendar, array(
            "id" => "Sonarr-".$number."-".$i,
            "title" => $seriesName,
            "start" => $child['airDateUtc'],
            "className" => $downloaded." tvID--".$episodeID,
            "imagetype" => "tv",
        ));
>>>>>>> 355582442ea88ab493d4eb8c8914d5d18d6c25c1
    }
    if ($i != 0){ return $gotCalendar; }
}
function getRadarrCalendar($array,$number){
    $array = json_decode($array, true);
    $gotCalendar = array();
    $i = 0;
    foreach($array AS $child) {
        if(isset($child['physicalRelease'])){
            $i++;
            $movieName = $child['title'];
            $movieID = $child['tmdbId'];
            if(!isset($movieID)){ $movieID = ""; }
<<<<<<< HEAD
			$physicalRelease = $child['physicalRelease'];
			$physicalRelease = strtotime($physicalRelease);
			$physicalRelease = date("Y-m-d", $physicalRelease);
			if (new DateTime() < new DateTime($physicalRelease)) { $notReleased = "true"; }else{ $notReleased = "false"; }
			$downloaded = $child['hasFile'];
			if($downloaded == "0" && $notReleased == "true"){ $downloaded = "text-info"; }elseif($downloaded == "1"){ $downloaded = "text-success"; }else{ $downloaded = "text-danger"; }
			array_push($gotCalendar, array(
				"id" => "Radarr-".$number."-".$i,
				"title" => $movieName,
				"start" => $child['physicalRelease'],
				"className" => "bg-calendar movieID--".$movieID,
				"imagetype" => "film ".$downloaded,
			));
=======
            $physicalRelease = $child['physicalRelease'];
            $physicalRelease = strtotime($physicalRelease);
            $physicalRelease = date("Y-m-d", $physicalRelease);
            if (new DateTime() < new DateTime($physicalRelease)) { $notReleased = "true"; }else{ $notReleased = "false"; }
            $downloaded = $child['hasFile'];
            if($downloaded == "0" && $notReleased == "true"){ $downloaded = "bg-info"; }elseif($downloaded == "1"){ $downloaded = "bg-success"; }else{ $downloaded = "bg-danger"; }
            array_push($gotCalendar, array(
                "id" => "Radarr-".$number."-".$i,
                "title" => $movieName,
                "start" => $child['physicalRelease'],
                "className" => $downloaded." movieID--".$movieID,
                "imagetype" => "film",
            ));
>>>>>>> 355582442ea88ab493d4eb8c8914d5d18d6c25c1
        }
    }
    if ($i != 0){ return $gotCalendar; }
}
function getCouchCalendar($array,$number){
    $api = json_decode($array, true);
    $gotCalendar = array();
    $i = 0;
<<<<<<< HEAD
	foreach($api['movies'] AS $child) {
		if($child['status'] == "active" || $child['status'] == "done" ){
			$i++;
			$movieName = $child['info']['original_title'];
			$movieID = $child['info']['tmdb_id'];
			if(!isset($movieID)){ $movieID = ""; }
			$physicalRelease = (isset($child['info']['released']) ? $child['info']['released'] : null);
			$backupRelease = (isset($child['info']['release_date']['theater']) ? $child['info']['release_date']['theater'] : null);
			$physicalRelease = (isset($physicalRelease) ? $physicalRelease : $backupRelease);
			$physicalRelease = strtotime($physicalRelease);
			$physicalRelease = date("Y-m-d", $physicalRelease);
			if (new DateTime() < new DateTime($physicalRelease)) { $notReleased = "true"; }else{ $notReleased = "false"; }
			$downloaded = ($child['status'] == "active") ? "0" : "1";
			if($downloaded == "0" && $notReleased == "true"){ $downloaded = "text-info"; }elseif($downloaded == "1"){ $downloaded = "text-success"; }else{ $downloaded = "text-danger"; }
			array_push($gotCalendar, array(
				"id" => "CouchPotato-".$number."-".$i,
				"title" => $movieName,
				"start" => $physicalRelease,
				"className" => "bg-calendar movieID--".$movieID,
				"imagetype" => "film ".$downloaded,
			));
		}
	}
	if ($i != 0){ return $gotCalendar; }
=======
    foreach($api['movies'] AS $child) {
        if($child['status'] == "active" || $child['status'] == "done" ){
            $i++;
            $movieName = $child['info']['original_title'];
            $movieID = $child['info']['tmdb_id'];
            if(!isset($movieID)){ $movieID = ""; }
            $physicalRelease = (isset($child['info']['released']) ? $child['info']['released'] : null);
            $backupRelease = (isset($child['info']['release_date']['theater']) ? $child['info']['release_date']['theater'] : null);
            $physicalRelease = (isset($physicalRelease) ? $physicalRelease : $backupRelease);
            $physicalRelease = strtotime($physicalRelease);
            $physicalRelease = date("Y-m-d", $physicalRelease);
            if (new DateTime() < new DateTime($physicalRelease)) { $notReleased = "true"; }else{ $notReleased = "false"; }
            $downloaded = ($child['status'] == "active") ? "0" : "1";
            if($downloaded == "0" && $notReleased == "true"){ $downloaded = "bg-info"; }elseif($downloaded == "1"){ $downloaded = "bg-success"; }else{ $downloaded = "bg-danger"; }
            array_push($gotCalendar, array(
                "id" => "CouchPotato-".$number."-".$i,
                "title" => $movieName,
                "start" => $physicalRelease,
                "className" => $downloaded." movieID--".$movieID,
                "imagetype" => "film",
            ));
        }
    }
    if ($i != 0){ return $gotCalendar; }
>>>>>>> 355582442ea88ab493d4eb8c8914d5d18d6c25c1
}
function getSickrageCalendarWanted($array,$number){
    $array = json_decode($array, true);
    $gotCalendar = array();
    $i = 0;
    foreach($array['data']['missed'] AS $child) {
            $i++;
            $seriesName = $child['show_name'];
            $episodeID = $child['tvdbid'];
            $episodeAirDate = $child['airdate'];
            $episodeAirDateTime = explode(" ",$child['airs']);
            $episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1].$episodeAirDateTime[2]));
            $episodeAirDate = strtotime($episodeAirDate.$episodeAirDateTime);
            $episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
            if (new DateTime() < new DateTime($episodeAirDate)) { $unaired = true; }
            $downloaded = "0";
<<<<<<< HEAD
            if($downloaded == "0" && isset($unaired)){ $downloaded = "text-info"; }elseif($downloaded == "1"){ $downloaded = "text-success";}else{ $downloaded = "text-danger"; }
			array_push($gotCalendar, array(
				"id" => "Sick-".$number."-Miss-".$i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => "bg-calendar tvID--".$episodeID,
				"imagetype" => "tv ".$downloaded,
			));
=======
            if($downloaded == "0" && isset($unaired)){ $downloaded = "bg-info"; }elseif($downloaded == "1"){ $downloaded = "bg-success";}else{ $downloaded = "bg-danger"; }
            array_push($gotCalendar, array(
                "id" => "Sick-".$number."-Miss-".$i,
                "title" => $seriesName,
                "start" => $episodeAirDate,
                "className" => $downloaded." tvID--".$episodeID,
                "imagetype" => "tv",
            ));
>>>>>>> 355582442ea88ab493d4eb8c8914d5d18d6c25c1
    }
    foreach($array['data']['today'] AS $child) {
            $i++;
            $seriesName = $child['show_name'];
            $episodeID = $child['tvdbid'];
            $episodeAirDate = $child['airdate'];
            $episodeAirDateTime = explode(" ",$child['airs']);
            $episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1].$episodeAirDateTime[2]));
            $episodeAirDate = strtotime($episodeAirDate.$episodeAirDateTime);
            $episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
            if (new DateTime() < new DateTime($episodeAirDate)) { $unaired = true; }
            $downloaded = "0";
<<<<<<< HEAD
            if($downloaded == "0" && isset($unaired)){ $downloaded = "text-info"; }elseif($downloaded == "1"){ $downloaded = "text-success";}else{ $downloaded = "text-danger"; }
			array_push($gotCalendar, array(
				"id" => "Sick-".$number."-Today-".$i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => "bg-calendar tvID--".$episodeID,
				"imagetype" => "tv ".$downloaded,
			));
=======
            if($downloaded == "0" && isset($unaired)){ $downloaded = "bg-info"; }elseif($downloaded == "1"){ $downloaded = "bg-success";}else{ $downloaded = "bg-danger"; }
            array_push($gotCalendar, array(
                "id" => "Sick-".$number."-Today-".$i,
                "title" => $seriesName,
                "start" => $episodeAirDate,
                "className" => $downloaded." tvID--".$episodeID,
                "imagetype" => "tv",
            ));
>>>>>>> 355582442ea88ab493d4eb8c8914d5d18d6c25c1
    }
    foreach($array['data']['soon'] AS $child) {
            $i++;
            $seriesName = $child['show_name'];
            $episodeID = $child['tvdbid'];
            $episodeAirDate = $child['airdate'];
            $episodeAirDateTime = explode(" ",$child['airs']);
            $episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1].$episodeAirDateTime[2]));
            $episodeAirDate = strtotime($episodeAirDate.$episodeAirDateTime);
            $episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
            if (new DateTime() < new DateTime($episodeAirDate)) { $unaired = true; }
            $downloaded = "0";
<<<<<<< HEAD
            if($downloaded == "0" && isset($unaired)){ $downloaded = "text-info"; }elseif($downloaded == "1"){ $downloaded = "text-success";}else{ $downloaded = "text-danger"; }
			array_push($gotCalendar, array(
				"id" => "Sick-".$number."-Soon-".$i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => "bg-calendar tvID--".$episodeID,
				"imagetype" => "tv ".$downloaded,
			));
=======
            if($downloaded == "0" && isset($unaired)){ $downloaded = "bg-info"; }elseif($downloaded == "1"){ $downloaded = "bg-success";}else{ $downloaded = "bg-danger"; }
            array_push($gotCalendar, array(
                "id" => "Sick-".$number."-Soon-".$i,
                "title" => $seriesName,
                "start" => $episodeAirDate,
                "className" => $downloaded." tvID--".$episodeID,
                "imagetype" => "tv",
            ));
>>>>>>> 355582442ea88ab493d4eb8c8914d5d18d6c25c1
    }
    foreach($array['data']['later'] AS $child) {
            $i++;
            $seriesName = $child['show_name'];
            $episodeID = $child['tvdbid'];
            $episodeAirDate = $child['airdate'];
            $episodeAirDateTime = explode(" ",$child['airs']);
            $episodeAirDateTime = date("H:i:s", strtotime($episodeAirDateTime[1].$episodeAirDateTime[2]));
            $episodeAirDate = strtotime($episodeAirDate.$episodeAirDateTime);
            $episodeAirDate = date("Y-m-d H:i:s", $episodeAirDate);
            if (new DateTime() < new DateTime($episodeAirDate)) { $unaired = true; }
            $downloaded = "0";
<<<<<<< HEAD
            if($downloaded == "0" && isset($unaired)){ $downloaded = "text-info"; }elseif($downloaded == "1"){ $downloaded = "text-success";}else{ $downloaded = "text-danger"; }
			array_push($gotCalendar, array(
				"id" => "Sick-".$number."-Later-".$i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => "bg-calendar tvID--".$episodeID,
				"imagetype" => "tv ".$downloaded,
			));
=======
            if($downloaded == "0" && isset($unaired)){ $downloaded = "bg-info"; }elseif($downloaded == "1"){ $downloaded = "bg-success";}else{ $downloaded = "bg-danger"; }
            array_push($gotCalendar, array(
                "id" => "Sick-".$number."-Later-".$i,
                "title" => $seriesName,
                "start" => $episodeAirDate,
                "className" => $downloaded." tvID--".$episodeID,
                "imagetype" => "tv",
            ));
>>>>>>> 355582442ea88ab493d4eb8c8914d5d18d6c25c1
    }
    if ($i != 0){ return $gotCalendar; }
}
function getSickrageCalendarHistory($array,$number){
    $array = json_decode($array, true);
    $gotCalendar = array();
    $i = 0;
    foreach($array['data'] AS $child) {
            $i++;
            $seriesName = $child['show_name'];
            $episodeID = $child['tvdbid'];
            $episodeAirDate = $child['date'];
<<<<<<< HEAD
            $downloaded = "text-success";
			array_push($gotCalendar, array(
				"id" => "Sick-".$number."-History-".$i,
				"title" => $seriesName,
				"start" => $episodeAirDate,
				"className" => "bg-calendar tvID--".$episodeID,
				"imagetype" => "tv ".$downloaded,
			));
=======
            $downloaded = "bg-success";
            array_push($gotCalendar, array(
                "id" => "Sick-".$number."-History-".$i,
                "title" => $seriesName,
                "start" => $episodeAirDate,
                "className" => $downloaded." tvID--".$episodeID,
                "imagetype" => "tv",
            ));
>>>>>>> 355582442ea88ab493d4eb8c8914d5d18d6c25c1
    }
    if ($i != 0){ return $gotCalendar; }
}
