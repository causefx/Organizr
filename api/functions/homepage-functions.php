<?php
//homepage order
function homepageOrder(){
	$homepageOrder = array(
		"homepageOrdercustomhtml" => $GLOBALS['homepageOrdercustomhtml'],
		"homepageOrdernotice" => $GLOBALS['homepageOrdernotice'],
		"homepageOrderplexsearch" => $GLOBALS['homepageOrderplexsearch'],
		"homepageOrderspeedtest" => $GLOBALS['homepageOrderspeedtest'],
		"homepageOrdernzbget" => $GLOBALS['homepageOrdernzbget'],
		"homepageOrdersabnzbd" => $GLOBALS['homepageOrdersabnzbd'],
		"homepageOrderplexnowplaying" => $GLOBALS['homepageOrderplexnowplaying'],
		"homepageOrderplexrecent" => $GLOBALS['homepageOrderplexrecent'],
		"homepageOrderplexplaylist" => $GLOBALS['homepageOrderplexplaylist'],
		"homepageOrderembynowplaying" => $GLOBALS['homepageOrderembynowplaying'],
		"homepageOrderembyrecent" => $GLOBALS['homepageOrderembyrecent'],
		"homepageOrderombi" => $GLOBALS['homepageOrderombi'],
		"homepageOrdercalendar" => $GLOBALS['homepageOrdercalendar'],
		"homepageOrdernoticeguest" => $GLOBALS['homepageOrdernoticeguest'],
		"homepageOrdertransmisson" => $GLOBALS['homepageOrdertransmisson'],
	);
	asort($homepageOrder);
	return $homepageOrder;
}
function buildHomepage(){
	$homepageOrder = homepageOrder();
	$homepageBuilt = '';
	foreach ($homepageOrder as $key => $value) {
		$homepageBuilt .= buildHomepageItem($key);
	}
	return $homepageBuilt;
}
function buildHomepageItem($homepageItem){
	$item = '<div id="'.$homepageItem.'"></div>';
	switch ($homepageItem) {
		case 'homepageOrderplexsearch':

			break;
		case 'homepageOrdercustomhtml':

			break;
		case 'homepageOrdernotice':

			break;
		case 'homepageOrdernoticeguest':

			break;
		case 'homepageOrderspeedtest':

			break;
		case 'homepageOrdertransmisson':

			break;
		case 'homepageOrdernzbget':

			break;
		case 'homepageOrdersabnzbd':
			if($GLOBALS['homepageSabnzbdEnabled']){
				$item .= '
				<script>
				// SabNZBd
				homepageDownloader("sabnzbd");
				setInterval(function() {
					homepageDownloader("sabnzbd");
				}, '.$GLOBALS['homepageDownloadRefresh'].');
				// End SabNZBd
				</script>
				';
			}
			break;
		case 'homepageOrderplexnowplaying':
			if($GLOBALS['homepagePlexStreams']){
				$item .= '
				<script>
				// Plex Stream
				homepageStream("plex");
				setInterval(function() {
				    homepageStream("plex");
				}, '.$GLOBALS['homepageStreamRefresh'].');
				// End Plex Stream
				</script>
				';
			}
			break;
		case 'homepageOrderplexrecent':
			if($GLOBALS['homepagePlexRecent']){
				$item .= '
				<script>
				// Plex Recent
				homepageRecent("plex");
				setInterval(function() {
					homepageRecent("plex");
				}, '.$GLOBALS['homepageRecentRefresh'].');
				// End Plex Recent
				</script>
				';
			}
			break;
		case 'homepageOrderplexplaylist':

			break;
		case 'homepageOrderembynowplaying':
			if($GLOBALS['homepageEmbyStreams']){
				$item .= '
				<script>
				// Emby Stream
				homepageStream("emby");
				setInterval(function() {
					homepageStream("emby");
				}, '.$GLOBALS['homepageStreamRefresh'].');
				// End Emby Stream
				</script>
				';
			}
			break;
		case 'homepageOrderembyrecent':
			if($GLOBALS['homepageEmbyRecent']){
				$item .= '
				<script>
				// Emby Recent
				homepageRecent("emby");
				setInterval(function() {
					homepageRecent("emby");
				}, '.$GLOBALS['homepageRecentRefresh'].');
				// End Emby Recent
				</script>
				';
			}
			break;
		case 'homepageOrderombi':

			break;
		case 'homepageOrdercalendar':

			break;
		default:
			# code...
			break;
	}
	return $item;
}
function getHomepageList(){
    $groups = groupSelect();
    $time = array(
        array(
            'name' => '5',
            'value' => '5000'
        ),
        array(
            'name' => '10',
            'value' => '10000'
        ),
        array(
            'name' => '15',
            'value' => '15000'
        ),
        array(
            'name' => '30',
            'value' => '30000'
        ),
        array(
            'name' => '60 [1 Minute]',
            'value' => '60000'
        ),
		array(
            'name' => '300 [5 Minutes]',
            'value' => '300000'
        ),
		array(
            'name' => '900 [15 Minutes]',
            'value' => '900000'
        ),
		array(
            'name' => '1800 [30 Minutes]',
            'value' => '1800000'
        ),
		array(
            'name' => '3600 [1 Hour]',
            'value' => '3600000'
        ),
    );
    return array(
        array(
            'name' => 'Plex',
            'enabled' => true,
            'image' => 'plugins/images/tabs/plex.png',
            'category' => 'Media Server',
            'settings' => array(
                'Enable' => array(
                    array(
            			'type' => 'switch',
            			'name' => 'homepagePlexEnabled',
            			'label' => 'Enable',
            			'value' => $GLOBALS['homepagePlexEnabled']
            		),
					array(
            			'type' => 'select',
            			'name' => 'homepagePlexAuth',
            			'label' => 'Minimum Authentication',
            			'value' => $GLOBALS['homepagePlexAuth'],
                        'options' => $groups
            		)
                ),
                'Connection' => array(
                    array(
                        'type' => 'input',
                        'name' => 'plexURL',
                        'label' => 'URL',
                        'value' => $GLOBALS['plexURL'],
						'placeholder' => 'http(s)://hostname:port'
                    ),
                    array(
                        'type' => 'input',
                        'name' => 'plexToken',
                        'label' => 'Token',
                        'value' => $GLOBALS['plexToken']
                    ),
                    array(
                        'type' => 'input',
                        'name' => 'plexID',
                        'label' => 'Plex Machine',
                        'value' => $GLOBALS['plexID']
                    )
                ),
				'Active Streams' => array(
					array(
            			'type' => 'switch',
            			'name' => 'homepagePlexStreams',
            			'label' => 'Enable',
            			'value' => $GLOBALS['homepagePlexStreams']
            		),
					array(
    					'type' => 'select',
    					'name' => 'homepagePlexStreamsAuth',
                        'label' => 'Minimum Authorization',
    					'value' => $GLOBALS['homepagePlexStreamsAuth'],
    					'options' => $groups
    				),
					array(
            			'type' => 'switch',
            			'name' => 'homepageShowStreamNames',
            			'label' => 'User Information',
            			'value' => $GLOBALS['homepageShowStreamNames']
            		),
					array(
    					'type' => 'select',
    					'name' => 'homepageShowStreamNamesAuth',
                        'label' => 'Minimum Authorization',
    					'value' => $GLOBALS['homepageShowStreamNamesAuth'],
    					'options' => $groups
    				),
					array(
    					'type' => 'select',
    					'name' => 'homepageStreamRefresh',
                        'label' => 'Refresh Seconds',
    					'value' => $GLOBALS['homepageStreamRefresh'],
    					'options' => $time
    				),
				),
                'Recent Items' => array(
					array(
            			'type' => 'switch',
            			'name' => 'homepagePlexRecent',
            			'label' => 'Enable',
            			'value' => $GLOBALS['homepagePlexRecent']
            		),
                    array(
    					'type' => 'select',
    					'name' => 'homepagePlexRecentAuth',
                        'label' => 'Minimum Authorization',
    					'value' => $GLOBALS['homepagePlexRecentAuth'],
    					'options' => $groups
    				),
					array(
						'type' => 'select',
						'name' => 'homepageRecentRefresh',
						'label' => 'Refresh Seconds',
						'value' => $GLOBALS['homepageRecentRefresh'],
						'options' => $time
					),
                ),
                'Misc Options' => array(
					array(
                        'type' => 'input',
                        'name' => 'plexTabName',
                        'label' => 'Plex Tab Name',
                        'value' => $GLOBALS['plexTabName'],
						'placeholder' => 'Only use if you have Plex in a reverse proxy'
                    ),
					array(
                        'type' => 'input',
                        'name' => 'plexTabURL',
                        'label' => 'Plex Tab WAN URL',
                        'value' => $GLOBALS['plexTabURL'],
						'placeholder' => 'http(s)://hostname:port'
                    )
                )
            )
        ),
		array(
            'name' => 'Emby',
            'enabled' => true,
            'image' => 'plugins/images/tabs/emby.png',
            'category' => 'Media Server',
            'settings' => array(
                'Enable' => array(
                    array(
            			'type' => 'switch',
            			'name' => 'homepageEmbyEnabled',
            			'label' => 'Enable',
            			'value' => $GLOBALS['homepageEmbyEnabled']
            		),
					array(
            			'type' => 'select',
            			'name' => 'homepageEmbyAuth',
            			'label' => 'Minimum Authentication',
            			'value' => $GLOBALS['homepageEmbyAuth'],
                        'options' => $groups
            		)
                ),
                'Connection' => array(
                    array(
                        'type' => 'input',
                        'name' => 'embyURL',
                        'label' => 'URL',
                        'value' => $GLOBALS['embyURL'],
						'placeholder' => 'http(s)://hostname:port'
                    ),
                    array(
                        'type' => 'input',
                        'name' => 'embyToken',
                        'label' => 'Token',
                        'value' => $GLOBALS['embyToken']
                    )
                ),
				'Active Streams' => array(
					array(
            			'type' => 'switch',
            			'name' => 'homepageEmbyStreams',
            			'label' => 'Enable',
            			'value' => $GLOBALS['homepageEmbyStreams']
            		),
					array(
    					'type' => 'select',
    					'name' => 'homepageEmbyStreamsAuth',
                        'label' => 'Minimum Authorization',
    					'value' => $GLOBALS['homepageEmbyStreamsAuth'],
    					'options' => $groups
    				),
					array(
            			'type' => 'switch',
            			'name' => 'homepageShowStreamNames',
            			'label' => 'User Information',
            			'value' => $GLOBALS['homepageShowStreamNames']
            		),
					array(
    					'type' => 'select',
    					'name' => 'homepageShowStreamNamesAuth',
                        'label' => 'Minimum Authorization',
    					'value' => $GLOBALS['homepageShowStreamNamesAuth'],
    					'options' => $groups
    				),
					array(
    					'type' => 'select',
    					'name' => 'homepageStreamRefresh',
                        'label' => 'Refresh Seconds',
    					'value' => $GLOBALS['homepageStreamRefresh'],
    					'options' => $time
    				),
				),
                'Recent Items' => array(
					array(
            			'type' => 'switch',
            			'name' => 'homepageEmbyRecent',
            			'label' => 'Enable',
            			'value' => $GLOBALS['homepageEmbyRecent']
            		),
                    array(
    					'type' => 'select',
    					'name' => 'homepageEmbyRecentAuth',
                        'label' => 'Minimum Authorization',
    					'value' => $GLOBALS['homepageEmbyRecentAuth'],
    					'options' => $groups
    				),
					array(
						'type' => 'select',
						'name' => 'homepageRecentRefresh',
						'label' => 'Refresh Seconds',
						'value' => $GLOBALS['homepageRecentRefresh'],
						'options' => $time
					),
                ),
                'Misc Options' => array(
					array(
                        'type' => 'input',
                        'name' => 'embyTabName',
                        'label' => 'Emby Tab Name',
                        'value' => $GLOBALS['embyTabName'],
						'placeholder' => 'Only use if you have Plex in a reverse proxy'
                    ),
					array(
                        'type' => 'input',
                        'name' => 'embyTabURL',
                        'label' => 'Emby Tab WAN URL',
                        'value' => $GLOBALS['embyTabURL'],
						'placeholder' => 'http(s)://hostname:port'
                    )
                )
            )
        ),
        array(
            'name' => 'SabNZBD',
            'enabled' => false,
            'image' => 'plugins/images/tabs/sabnzbd.png',
            'category' => 'Downloader',
            'settings' => array(
                'Enable' => array(
                    array(
            			'type' => 'switch',
            			'name' => 'homepageSabnzbdEnabled',
            			'label' => 'Enable',
            			'value' => $GLOBALS['homepageSabnzbdEnabled']
            		),
					array(
            			'type' => 'select',
            			'name' => 'homepageSabnzbdAuth',
            			'label' => 'Minimum Authentication',
            			'value' => $GLOBALS['homepageSabnzbdAuth'],
                        'options' => $groups
            		)
                ),
				'Connection' => array(
                    array(
                        'type' => 'input',
                        'name' => 'sabnzbdURL',
                        'label' => 'URL',
                        'value' => $GLOBALS['sabnzbdURL'],
						'placeholder' => 'http(s)://hostname:port'
                    ),
                    array(
                        'type' => 'input',
                        'name' => 'sabnzbdToken',
                        'label' => 'Token',
                        'value' => $GLOBALS['sabnzbdToken']
                    )
                ),
				'Misc Options' => array(
					array(
						'type' => 'select',
						'name' => 'homepageDownloadRefresh',
						'label' => 'Refresh Seconds',
						'value' => $GLOBALS['homepageDownloadRefresh'],
						'options' => $time
					)
				)
            )
        )
    );
}
