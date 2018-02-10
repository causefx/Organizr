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
        "homepageOrdertransmission" => $GLOBALS['homepageOrdertransmission'],
        "homepageOrderqBittorrent" => $GLOBALS['homepageOrderqBittorrent'],
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
	$item = '<div id="'.$homepageItem.'">';
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
        case 'homepageOrderqBittorrent':
            if($GLOBALS['homepageqBittorrentEnabled']){
                $item .= '
                <script>
                // homepageOrderqBittorrent
                homepageDownloader("qBittorrent");
                setInterval(function() {
                    homepageDownloader("qBittorrent");
                }, '.$GLOBALS['homepageDownloadRefresh'].');
                // End homepageOrderqBittorrent
                </script>
                ';
            }
            break;
		case 'homepageOrdertransmission':
			if($GLOBALS['homepageTransmissionEnabled']){
				$item .= '
				<script>
				// Transmission
				homepageDownloader("transmission");
				setInterval(function() {
					homepageDownloader("transmission");
				}, '.$GLOBALS['homepageDownloadRefresh'].');
				// End Transmission
				</script>
				';
			}
			break;
		case 'homepageOrdernzbget':
			if($GLOBALS['homepageNzbgetEnabled']){
				$item .= '
				<script>
				// NZBGet
				homepageDownloader("nzbget");
				setInterval(function() {
					homepageDownloader("nzbget");
				}, '.$GLOBALS['homepageDownloadRefresh'].');
				// End NZBGet
				</script>
				';
			}
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
			$item .= '
			<div id="calendar" class="fc fc-ltr"></div>
			<script>
			// Calendar
			homepageCalendar();
			setInterval(function() {
				homepageCalendar();
			}, '.$GLOBALS['calendarRefresh'].');
			// End Calendar
			</script>
			';
			break;
		default:
			# code...
			break;
	}
	return $item.'</div>';
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
	$limit = array(
		array(
			'name' => '1 Item',
			'value' => '1'
		),
		array(
			'name' => '2 Items',
			'value' => '2'
		),
		array(
			'name' => '3 Items',
			'value' => '3'
		),
		array(
			'name' => '4 Items',
			'value' => '4'
		),
		array(
			'name' => '5 Items',
			'value' => '5'
		),
		array(
			'name' => '6 Items',
			'value' => '6'
		),
		array(
			'name' => '7 Items',
			'value' => '7'
		),
		array(
			'name' => '8 Items',
			'value' => '8'
		),
		array(
			'name' => 'Unlimited',
			'value' => '1000'
		),
	);
	$day = array(
		array(
			'name' => 'Sunday',
			'value' => '0'
		),
		array(
			'name' => 'Monday',
			'value' => '1'
		),
		array(
			'name' => 'Tueday',
			'value' => '2'
		),
		array(
			'name' => 'Wednesday',
			'value' => '3'
		),
		array(
			'name' => 'Thursday',
			'value' => '4'
		),
		array(
			'name' => 'Friday',
			'value' => '5'
		),
		array(
			'name' => 'Saturday',
			'value' => '6'
		)
	);
	$calendarDefault = array(
		array(
			'name' => 'Month',
			'value' => 'month'
		),
		array(
			'name' => 'Day',
			'value' => 'basicDay'
		),
		array(
			'name' => 'Week',
			'value' => 'basicWeek'
		)
	);
	$timeFormat = array(
		array(
			'name' => '6p',
			'value' => 'h(:mm)t'
		),
		array(
			'name' => '6:00p',
			'value' => 'h:mmt'
		),
		array(
			'name' => '6:00',
			'value' => 'h:mm'
		),
		array(
			'name' => '18',
			'value' => 'H(:mm)'
		),
		array(
			'name' => '18:00',
			'value' => 'H:mm'
		)
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
        ),
		array(
            'name' => 'NZBGet',
            'enabled' => false,
            'image' => 'plugins/images/tabs/nzbget.png',
            'category' => 'Downloader',
            'settings' => array(
                'Enable' => array(
                    array(
            			'type' => 'switch',
            			'name' => 'homepageNzbgetEnabled',
            			'label' => 'Enable',
            			'value' => $GLOBALS['homepageNzbgetEnabled']
            		),
					array(
            			'type' => 'select',
            			'name' => 'homepageNzbgetAuth',
            			'label' => 'Minimum Authentication',
            			'value' => $GLOBALS['homepageNzbgetAuth'],
                        'options' => $groups
            		)
                ),
				'Connection' => array(
                    array(
                        'type' => 'input',
                        'name' => 'nzbgetURL',
                        'label' => 'URL',
                        'value' => $GLOBALS['nzbgetURL'],
						'placeholder' => 'http(s)://hostname:port'
                    ),
                    array(
                        'type' => 'input',
                        'name' => 'nzbgetUsername',
                        'label' => 'Username',
                        'value' => $GLOBALS['nzbgetUsername']
                    ),
					array(
                        'type' => 'password',
                        'name' => 'nzbgetPassword',
                        'label' => 'Password',
                        'value' => $GLOBALS['nzbgetPassword']
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
        ),
        array(
            'name' => 'Transmission',
            'enabled' => false,
            'image' => 'plugins/images/tabs/transmission.png',
            'category' => 'Downloader',
            'settings' => array(
                'Enable' => array(
                    array(
                        'type' => 'switch',
                        'name' => 'homepageTransmissionEnabled',
                        'label' => 'Enable',
                        'value' => $GLOBALS['homepageTransmissionEnabled']
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'homepageTransmissionAuth',
                        'label' => 'Minimum Authentication',
                        'value' => $GLOBALS['homepageTransmissionAuth'],
                        'options' => $groups
                    )
                ),
                'Connection' => array(
                    array(
                        'type' => 'input',
                        'name' => 'transmissionURL',
                        'label' => 'URL',
                        'value' => $GLOBALS['transmissionURL'],
                        'placeholder' => 'http(s)://hostname:port'
                    ),
                    array(
                        'type' => 'input',
                        'name' => 'transmissionUsername',
                        'label' => 'Username',
                        'value' => $GLOBALS['transmissionUsername']
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'transmissionPassword',
                        'label' => 'Password',
                        'value' => $GLOBALS['transmissionPassword']
                    )
                ),
                'Misc Options' => array(
                    array(
                        'type' => 'switch',
                        'name' => 'transmissionHideSeeding',
                        'label' => 'Hide Seeding',
                        'value' => $GLOBALS['transmissionHideSeeding']
                    ),array(
                        'type' => 'switch',
                        'name' => 'transmissionHideCompleted',
                        'label' => 'Hide Completed',
                        'value' => $GLOBALS['transmissionHideCompleted']
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'homepageDownloadRefresh',
                        'label' => 'Refresh Seconds',
                        'value' => $GLOBALS['homepageDownloadRefresh'],
                        'options' => $time
                    )
                )
            )
        ),
        array(
            'name' => 'qBittorrent',
            'enabled' => false,
            'image' => 'plugins/images/tabs/qBittorrent.png',
            'category' => 'Downloader',
            'settings' => array(
                'Enable' => array(
                    array(
                        'type' => 'switch',
                        'name' => 'homepageqBittorrentEnabled',
                        'label' => 'Enable',
                        'value' => $GLOBALS['homepageqBittorrentEnabled']
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'homepageqBittorrentAuth',
                        'label' => 'Minimum Authentication',
                        'value' => $GLOBALS['homepageqBittorrentAuth'],
                        'options' => $groups
                    )
                ),
                'Connection' => array(
                    array(
                        'type' => 'input',
                        'name' => 'qBittorrentURL',
                        'label' => 'URL',
                        'value' => $GLOBALS['qBittorrentURL'],
                        'placeholder' => 'http(s)://hostname:port'
                    ),
                    array(
                        'type' => 'input',
                        'name' => 'qBittorrentUsername',
                        'label' => 'Username',
                        'value' => $GLOBALS['qBittorrentUsername']
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'qBittorrentPassword',
                        'label' => 'Password',
                        'value' => $GLOBALS['qBittorrentPassword']
                    )
                ),
                'Misc Options' => array(
                    array(
                        'type' => 'switch',
                        'name' => 'qBittorrentHideSeeding',
                        'label' => 'Hide Seeding',
                        'value' => $GLOBALS['qBittorrentHideSeeding']
                    ),array(
                        'type' => 'switch',
                        'name' => 'qBittorrentnHideCompleted',
                        'label' => 'Hide Completed',
                        'value' => $GLOBALS['qBittorrentHideCompleted']
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'homepageDownloadRefresh',
                        'label' => 'Refresh Seconds',
                        'value' => $GLOBALS['homepageDownloadRefresh'],
                        'options' => $time
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
                        'name' => 'homepageSonarrEnabled',
                        'label' => 'Enable',
                        'value' => $GLOBALS['homepageSonarrEnabled']
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'homepageSonarrAuth',
                        'label' => 'Minimum Authentication',
                        'value' => $GLOBALS['homepageSonarrAuth'],
                        'options' => $groups
                    )
                ),
                'Connection' => array(
                    array(
                        'type' => 'input',
                        'name' => 'sonarrURL',
                        'label' => 'URL',
                        'value' => $GLOBALS['sonarrURL'],
                        'placeholder' => 'http(s)://hostname:port'
                    ),
                    array(
                        'type' => 'input',
                        'name' => 'sonarrToken',
                        'label' => 'Token',
                        'value' => $GLOBALS['sonarrToken']
                    )
                ),
                'Misc Options' => array(
					array(
						'type' => 'input',
						'name' => 'calendarStart',
						'label' => '# of Days Before',
						'value' => $GLOBALS['calendarStart'],
						'placeholder' => ''
					),
					array(
						'type' => 'input',
						'name' => 'calendarEnd',
						'label' => '# of Days After',
						'value' => $GLOBALS['calendarEnd'],
						'placeholder' => ''
					),
					array(
                        'type' => 'select',
                        'name' => 'calendarFirstDay',
                        'label' => 'Start Day',
                        'value' => $GLOBALS['calendarFirstDay'],
                        'options' => $day
                    ),
					array(
                        'type' => 'select',
                        'name' => 'calendarDefault',
                        'label' => 'Default View',
                        'value' => $GLOBALS['calendarDefault'],
                        'options' => $calendarDefault
                    ),
					array(
                        'type' => 'select',
                        'name' => 'calendarTimeFormat',
                        'label' => 'Time Format',
                        'value' => $GLOBALS['calendarTimeFormat'],
                        'options' => $timeFormat
                    ),
					array(
                        'type' => 'select',
                        'name' => 'calendarLimit',
                        'label' => 'Items Per Day',
                        'value' => $GLOBALS['calendarLimit'],
                        'options' => $limit
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'calendarRefresh',
                        'label' => 'Refresh Seconds',
                        'value' => $GLOBALS['calendarRefresh'],
                        'options' => $time
                    )
                )
            )
        ),
		array(
            'name' => 'Radarr',
            'enabled' => false,
            'image' => 'plugins/images/tabs/radarr.png',
            'category' => 'PVR',
            'settings' => array(
                'Enable' => array(
                    array(
                        'type' => 'switch',
                        'name' => 'homepageRadarrEnabled',
                        'label' => 'Enable',
                        'value' => $GLOBALS['homepageRadarrEnabled']
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'homepageRadarrAuth',
                        'label' => 'Minimum Authentication',
                        'value' => $GLOBALS['homepageRadarrAuth'],
                        'options' => $groups
                    )
                ),
                'Connection' => array(
                    array(
                        'type' => 'input',
                        'name' => 'radarrURL',
                        'label' => 'URL',
                        'value' => $GLOBALS['radarrURL'],
                        'placeholder' => 'http(s)://hostname:port'
                    ),
                    array(
                        'type' => 'input',
                        'name' => 'radarrToken',
                        'label' => 'Token',
                        'value' => $GLOBALS['radarrToken']
                    )
                ),
                'Misc Options' => array(
					array(
						'type' => 'input',
						'name' => 'calendarStart',
						'label' => '# of Days Before',
						'value' => $GLOBALS['calendarStart'],
						'placeholder' => ''
					),
					array(
						'type' => 'input',
						'name' => 'calendarEnd',
						'label' => '# of Days After',
						'value' => $GLOBALS['calendarEnd'],
						'placeholder' => ''
					),
					array(
                        'type' => 'select',
                        'name' => 'calendarFirstDay',
                        'label' => 'Start Day',
                        'value' => $GLOBALS['calendarFirstDay'],
                        'options' => $day
                    ),
					array(
                        'type' => 'select',
                        'name' => 'calendarDefault',
                        'label' => 'Default View',
                        'value' => $GLOBALS['calendarDefault'],
                        'options' => $calendarDefault
                    ),
					array(
                        'type' => 'select',
                        'name' => 'calendarTimeFormat',
                        'label' => 'Time Format',
                        'value' => $GLOBALS['calendarTimeFormat'],
                        'options' => $timeFormat
                    ),
					array(
                        'type' => 'select',
                        'name' => 'calendarLimit',
                        'label' => 'Items Per Day',
                        'value' => $GLOBALS['calendarLimit'],
                        'options' => $limit
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'calendarRefresh',
                        'label' => 'Refresh Seconds',
                        'value' => $GLOBALS['calendarRefresh'],
                        'options' => $time
                    )
                )
            )
        ),
		array(
            'name' => 'CouchPotato',
            'enabled' => false,
            'image' => 'plugins/images/tabs/couchpotato.png',
            'category' => 'PVR',
            'settings' => array(
                'Enable' => array(
                    array(
                        'type' => 'switch',
                        'name' => 'homepageCouchpotatoEnabled',
                        'label' => 'Enable',
                        'value' => $GLOBALS['homepageCouchpotatoEnabled']
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'homepageCouchpotatoAuth',
                        'label' => 'Minimum Authentication',
                        'value' => $GLOBALS['homepageCouchpotatoAuth'],
                        'options' => $groups
                    )
                ),
                'Connection' => array(
                    array(
                        'type' => 'input',
                        'name' => 'couchpotatoURL',
                        'label' => 'URL',
                        'value' => $GLOBALS['couchpotatoURL'],
                        'placeholder' => 'http(s)://hostname:port'
                    ),
                    array(
                        'type' => 'input',
                        'name' => 'couchpotatoToken',
                        'label' => 'Token',
                        'value' => $GLOBALS['couchpotatoToken']
                    )
                ),
                'Misc Options' => array(
					array(
                        'type' => 'select',
                        'name' => 'calendarFirstDay',
                        'label' => 'Start Day',
                        'value' => $GLOBALS['calendarFirstDay'],
                        'options' => $day
                    ),
					array(
                        'type' => 'select',
                        'name' => 'calendarDefault',
                        'label' => 'Default View',
                        'value' => $GLOBALS['calendarDefault'],
                        'options' => $calendarDefault
                    ),
					array(
                        'type' => 'select',
                        'name' => 'calendarTimeFormat',
                        'label' => 'Time Format',
                        'value' => $GLOBALS['calendarTimeFormat'],
                        'options' => $timeFormat
                    ),
					array(
                        'type' => 'select',
                        'name' => 'calendarLimit',
                        'label' => 'Items Per Day',
                        'value' => $GLOBALS['calendarLimit'],
                        'options' => $limit
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'calendarRefresh',
                        'label' => 'Refresh Seconds',
                        'value' => $GLOBALS['calendarRefresh'],
                        'options' => $time
                    )
                )
            )
        ),
		array(
            'name' => 'SickRage',
            'enabled' => false,
            'image' => 'plugins/images/tabs/sickrage.png',
            'category' => 'PVR',
            'settings' => array(
                'Enable' => array(
                    array(
                        'type' => 'switch',
                        'name' => 'homepageSickrageEnabled',
                        'label' => 'Enable',
                        'value' => $GLOBALS['homepageSickrageEnabled']
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'homepageSickrageAuth',
                        'label' => 'Minimum Authentication',
                        'value' => $GLOBALS['homepageSickrageAuth'],
                        'options' => $groups
                    )
                ),
                'Connection' => array(
                    array(
                        'type' => 'input',
                        'name' => 'sickrageURL',
                        'label' => 'URL',
                        'value' => $GLOBALS['sickrageURL'],
                        'placeholder' => 'http(s)://hostname:port'
                    ),
                    array(
                        'type' => 'input',
                        'name' => 'sickrageToken',
                        'label' => 'Token',
                        'value' => $GLOBALS['sickrageToken']
                    )
                ),
                'Misc Options' => array(
					array(
                        'type' => 'select',
                        'name' => 'calendarFirstDay',
                        'label' => 'Start Day',
                        'value' => $GLOBALS['calendarFirstDay'],
                        'options' => $day
                    ),
					array(
                        'type' => 'select',
                        'name' => 'calendarDefault',
                        'label' => 'Default View',
                        'value' => $GLOBALS['calendarDefault'],
                        'options' => $calendarDefault
                    ),
					array(
                        'type' => 'select',
                        'name' => 'calendarTimeFormat',
                        'label' => 'Time Format',
                        'value' => $GLOBALS['calendarTimeFormat'],
                        'options' => $timeFormat
                    ),
					array(
                        'type' => 'select',
                        'name' => 'calendarLimit',
                        'label' => 'Items Per Day',
                        'value' => $GLOBALS['calendarLimit'],
                        'options' => $limit
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'calendarRefresh',
                        'label' => 'Refresh Seconds',
                        'value' => $GLOBALS['calendarRefresh'],
                        'options' => $time
                    )
                )
            )
        )
    );
}
