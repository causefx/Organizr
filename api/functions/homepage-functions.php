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

			break;
		case 'homepageOrderplexnowplaying':
			if($GLOBALS['homepagePlexStreams']){
				$item .= '
				<script>
				// Plex Stream
				plexStream();
				setInterval(function() {
				    plexStream();
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
				plexRecent();
				setInterval(function() {
					plexRecent();
				}, '.$GLOBALS['homepageRecentRefresh'].');
				// End Plex Recent
				</script>
				';
			}
			break;
		case 'homepageOrderplexplaylist':

			break;
		case 'homepageOrderembynowplaying':

			break;
		case 'homepageOrderembyrecent':

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
            		)
                ),
                'Connection' => array(
                    array(
                        'type' => 'input',
                        'name' => 'plexURL',
                        'label' => 'URL',
                        'value' => $GLOBALS['plexURL']
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
                'Authentication' => array(
                    array(
            			'type' => 'select',
            			'name' => 'homepagePlexAuth',
            			'label' => 'Minimum Authentication',
            			'value' => $GLOBALS['homepagePlexAuth'],
                        'options' => $groups
            		)
                ),
                'Modules' => array(
                    array(
            			'type' => 'switch',
            			'name' => 'homepagePlexStreams',
            			'label' => 'Show Streams',
            			'value' => $GLOBALS['homepagePlexStreams']
            		),
                    array(
    					'type' => 'select',
    					'name' => 'homepagePlexStreamsAuth',
                        'label' => 'Stream Authorization',
    					'value' => $GLOBALS['homepagePlexStreamsAuth'],
    					'options' => $groups
    				),
					array(
            			'type' => 'switch',
            			'name' => 'homepagePlexRecent',
            			'label' => 'Show Recent Items',
            			'value' => $GLOBALS['homepagePlexRecent']
            		),
                    array(
    					'type' => 'select',
    					'name' => 'homepagePlexRecentAuth',
                        'label' => 'Recent Authorization',
    					'value' => $GLOBALS['homepagePlexRecentAuth'],
    					'options' => $groups
    				)
                ),
                'Options' => array(
                    array(
            			'type' => 'switch',
            			'name' => 'homepageShowStreamNames',
            			'label' => 'Show Usernames',
            			'value' => $GLOBALS['homepageShowStreamNames']
            		),
                    array(
    					'type' => 'select',
    					'name' => 'homepageStreamRefresh',
                        'label' => 'Stream Refresh Seconds',
    					'value' => $GLOBALS['homepageStreamRefresh'],
    					'options' => $time
    				),
					array(
    					'type' => 'select',
    					'name' => 'homepageRecentRefresh',
                        'label' => 'Recent Items Refresh Seconds',
    					'value' => $GLOBALS['homepageRecentRefresh'],
    					'options' => $time
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
            			'name' => 'homepagePlexEnabled',
            			'label' => 'Enable',
            			'value' => $GLOBALS['homepagePlexEnabled']
            		)
                ),
                'Connection' => array(
                    array(
                        'type' => 'input',
                        'name' => 'plexURL',
                        'label' => 'URL',
                        'value' => $GLOBALS['plexURL']
                    ),
                    array(
                        'type' => 'input',
                        'name' => 'plexToken',
                        'label' => 'Token',
                        'value' => $GLOBALS['plexToken']
                    )
                ),
                'Authentication' => array(
                    array(
            			'type' => 'select',
            			'name' => 'homepagePlexAuth',
            			'label' => 'Minimum Authentication',
            			'value' => $GLOBALS['homepagePlexAuth'],
                        'options' => $groups
            		)
                ),
                'Options' => array(
                    array(
    					'type' => 'select',
    					'name' => 'style',
    					'label' => 'Style',
    					'class' => 'styleChanger',
    					'value' => $GLOBALS['style'],
    					'options' => $groups
    				)
                )
            )
        ),
        array(
            'name' => 'Sonarr',
            'enabled' => false,
            'image' => 'plugins/images/tabs/sonarr.png',
            'category' => 'PVR',
            'settings' => array(
                'Enable' => array(
                    array(
            			'type' => 'switch',
            			'name' => 'homepagePlexEnabled',
            			'label' => 'Enable',
            			'value' => $GLOBALS['homepagePlexEnabled']
            		)
                ),
                'Connection' => array(
                    array(
                        'type' => 'input',
                        'name' => 'plexURL',
                        'label' => 'URL',
                        'value' => $GLOBALS['plexURL']
                    ),
                    array(
                        'type' => 'input',
                        'name' => 'plexToken',
                        'label' => 'Token',
                        'value' => $GLOBALS['plexToken']
                    )
                ),
                'Authentication' => array(
                    array(
            			'type' => 'select',
            			'name' => 'homepagePlexAuth',
            			'label' => 'Minimum Authentication',
            			'value' => $GLOBALS['homepagePlexAuth'],
                        'options' => $groups
            		)
                ),
                'Options' => array(
                    array(
    					'type' => 'select',
    					'name' => 'style',
    					'label' => 'Style',
    					'class' => 'styleChanger',
    					'value' => $GLOBALS['style'],
    					'options' => $groups
    				)
                )
            )
        )
    );
}
