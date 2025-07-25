<?php

/**
 * User Watch Statistics Homepage Plugin
 * Provides comprehensive user watching statistics from Plex/Emby/Jellyfin
 */

trait HomepageUserWatchStats
{
    public function userWatchStatsSettingsArray($infoOnly = false)
    {
        $homepageInformation = [
            'name' => 'UserWatchStats',
            'enabled' => true,
            'image' => 'plugins/images/homepage/userWatchStats.png',
            'category' => 'Media Server',
            'settingsArray' => __FUNCTION__
        ];
        if ($infoOnly) {
            return $homepageInformation;
        }
        $homepageSettings = [
            'debug' => true,
            'settings' => [
                'Enable' => [
                    $this->settingsOption('enable', 'homepageUserWatchStatsEnabled'),
                    $this->settingsOption('auth', 'homepageUserWatchStatsAuth'),
                ],
                'Connection' => [
                    $this->settingsOption('select', 'homepageUserWatchStatsService', ['label' => 'Media Server', 'options' => [
                        ['name' => 'Plex (via Tautulli)', 'value' => 'plex'],
                        ['name' => 'Emby', 'value' => 'emby'],
                        ['name' => 'Jellyfin', 'value' => 'jellyfin']
                    ]]),
                    $this->settingsOption('url', 'userWatchStatsURL'),
                    $this->settingsOption('token', 'userWatchStatsApikey'),
                    $this->settingsOption('disable-cert-check', 'userWatchStatsDisableCertCheck'),
                    $this->settingsOption('use-custom-certificate', 'userWatchStatsUseCustomCertificate'),
                ],
                'Display Options' => [
                    $this->settingsOption('number', 'homepageUserWatchStatsRefresh', ['label' => 'Auto-refresh Interval (minutes)', 'min' => 1, 'max' => 60]),
                    $this->settingsOption('number', 'homepageUserWatchStatsDays', ['label' => 'Statistics Period (days)', 'min' => 1, 'max' => 365]),
                    $this->settingsOption('switch', 'homepageUserWatchStatsCompactView', ['label' => 'Use Compact View']),
                    $this->settingsOption('switch', 'homepageUserWatchStatsShowTopUsers', ['label' => 'Show Top Users']),
                    $this->settingsOption('switch', 'homepageUserWatchStatsShowMostWatched', ['label' => 'Show Most Watched']),
                    $this->settingsOption('switch', 'homepageUserWatchStatsShowRecentActivity', ['label' => 'Show Recent Activity']),
                    $this->settingsOption('number', 'homepageUserWatchStatsMaxItems', ['label' => 'Maximum Items to Display', 'min' => 5, 'max' => 50]),
                ],
                'Test Connection' => [
                    $this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
                    $this->settingsOption('test', 'userWatchStats'),
                ]
            ]
        ];
        return array_merge($homepageInformation, $homepageSettings);
    }


    public function testConnectionUserWatchStats()
    {
        if (!$this->homepageItemPermissions($this->userWatchStatsHomepagePermissions('test'), true)) {
            return false;
        }
        
        $mediaServer = $this->config['homepageUserWatchStatsService'] ?? 'plex';
        
        // Get URL and token from plugin-specific config  
        $url = $this->config['userWatchStatsURL'] ?? '';
        $token = $this->config['userWatchStatsApikey'] ?? '';
        $disableCert = $this->config['userWatchStatsDisableCertCheck'] ?? false;
        $customCert = $this->config['userWatchStatsUseCustomCertificate'] ?? false;
        
        if (empty($url) || empty($token)) {
            $serverName = ucfirst($mediaServer) . ($mediaServer === 'plex' ? ' (Tautulli)' : '');
            $this->setAPIResponse('error', $serverName . ' URL or API key not configured', 500);
            return false;
        }
        
        // Test the connection based on media server type
        try {
            $options = $this->requestOptions($url, null, $disableCert, $customCert);
            
            switch (strtolower($mediaServer)) {
                case 'plex':
                    // Test Tautulli connection
                    $testUrl = $this->qualifyURL($url) . '/api/v2?apikey=' . $token . '&cmd=get_server_info';
                    $response = Requests::get($testUrl, [], $options);
                    if ($response->success) {
                        $data = json_decode($response->body, true);
                        if (isset($data['response']['result']) && $data['response']['result'] === 'success') {
                            $this->setAPIResponse('success', 'Successfully connected to Tautulli', 200);
                            return true;
                        }
                    }
                    break;
                case 'emby':
                    // Test Emby connection
                    $testUrl = $this->qualifyURL($url) . '/emby/System/Info?api_key=' . $token;
                    $response = Requests::get($testUrl, [], $options);
                    if ($response->success) {
                        $data = json_decode($response->body, true);
                        if (isset($data['ServerName'])) {
                            $this->setAPIResponse('success', 'Successfully connected to Emby server: ' . $data['ServerName'], 200);
                            return true;
                        }
                    }
                    break;
                case 'jellyfin':
                    // Test Jellyfin connection
                    $testUrl = $this->qualifyURL($url) . '/System/Info?api_key=' . $token;
                    $response = Requests::get($testUrl, [], $options);
                    if ($response->success) {
                        $data = json_decode($response->body, true);
                        if (isset($data['ServerName'])) {
                            $this->setAPIResponse('success', 'Successfully connected to Jellyfin server: ' . $data['ServerName'], 200);
                            return true;
                        }
                    }
                    break;
            }
            
            $this->setAPIResponse('error', 'Connection test failed - invalid response from server', 500);
            return false;
            
        } catch (Exception $e) {
            $this->setAPIResponse('error', 'Connection test failed: ' . $e->getMessage(), 500);
            return false;
        }
    }
    public function userWatchStatsHomepagePermissions($key = null)
    {
        $permissions = [
            'test' => [
                'enabled' => [
                    'homepageUserWatchStatsEnabled',
                ],
                'auth' => [
                    'homepageUserWatchStatsAuth',
                ],
                'not_empty' => [
                    'userWatchStatsURL',
                    'userWatchStatsApikey'
                ]
            ],
            'main' => [
                'enabled' => [
                    'homepageUserWatchStatsEnabled'
                ],
                'auth' => [
                    'homepageUserWatchStatsAuth'
                ],
                'not_empty' => [
                    'userWatchStatsURL',
                    'userWatchStatsApikey'
                ]
            ]
        ];
        return $this->homepageCheckKeyPermissions($key, $permissions);
    }

    public function homepageOrderUserWatchStats()
    {
        if ($this->homepageItemPermissions($this->userWatchStatsHomepagePermissions('main'))) {
            $refreshInterval = ($this->config['homepageUserWatchStatsRefresh'] ?? 5) * 60000; // Convert minutes to milliseconds
            $compactView = ($this->config['homepageUserWatchStatsCompactView'] ?? false) ? 'true' : 'false';
            $days = $this->config['homepageUserWatchStatsDays'] ?? 30;
            $maxItems = $this->config['homepageUserWatchStatsMaxItems'] ?? 10;
            $showTopUsers = ($this->config['homepageUserWatchStatsShowTopUsers'] ?? true) ? 'true' : 'false';
            $showMostWatched = ($this->config['homepageUserWatchStatsShowMostWatched'] ?? true) ? 'true' : 'false';
            $showRecentActivity = ($this->config['homepageUserWatchStatsShowRecentActivity'] ?? true) ? 'true' : 'false';

            return '
            <div id="' . __FUNCTION__ . '">
                <div class="white-box">
                    <div class="white-box-header">
                        <i class="fa fa-bar-chart"></i> User Watch Statistics
                        <span class="pull-right">
                            <small id="watchstats-last-update" class="text-muted"></small>
                            <button class="btn btn-xs btn-primary" onclick="refreshUserWatchStats()" title="Refresh Data">
                                <i class="fa fa-refresh" id="watchstats-refresh-icon"></i>
                            </button>
                        </span>
                    </div>
                    <div class="white-box-content">
                        <div class="row" id="watchstats-content">
                            <div class="col-lg-12 text-center">
                                <i class="fa fa-spinner fa-spin"></i> Loading statistics...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            var watchStatsRefreshTimer;
            var watchStatsLastRefresh = 0;

            function refreshUserWatchStats() {
                var refreshIcon = $("#watchstats-refresh-icon");
                refreshIcon.addClass("fa-spin");

                // Show loading state
                $("#watchstats-content").html(\'<div class="col-lg-12 text-center"><i class="fa fa-spinner fa-spin"></i> Loading statistics...</div>\');

                // Load watch statistics
                getUserWatchStatsData()
                .always(function() {
                    refreshIcon.removeClass("fa-spin");
                    watchStatsLastRefresh = Date.now();
                    updateWatchStatsLastRefreshTime();
                });
            }

            function updateWatchStatsLastRefreshTime() {
                if (watchStatsLastRefresh > 0) {
                    var ago = Math.floor((Date.now() - watchStatsLastRefresh) / 1000);
                    var timeText = ago < 60 ? ago + "s ago" : Math.floor(ago / 60) + "m ago";
                    $("#watchstats-last-update").text("Updated " + timeText);
                }
            }

            var watchStatsData = null;
            var currentSort = { field: 'total_plays', direction: 'desc' };
            var currentFilter = 'all';
            var currentView = 'table';
            
            function getUserWatchStatsData() {
                return organizrAPI2("GET", "api/v2/homepage/userWatchStats")
                .done(function(data) {
                    if (data && data.response && data.response.result === "success" && data.response.data) {
                        watchStatsData = data.response.data;
                        renderWatchStatsUI();
                    } else {
                        $("#watchstats-content").html('\<div class="col-lg-12 text-center text-danger">Failed to load statistics\</div>\');
                    }
                })
                .fail(function(xhr, status, error) {
                    $("#watchstats-content").html('\<div class="col-lg-12 text-center text-danger">Error loading statistics\</div>\');
                });
            }
            
            function renderWatchStatsUI() {
                if (!watchStatsData) return;
                
                var stats = watchStatsData;
                var html = "";
                
                // Header with controls
                html += '\<div class="col-lg-12">\'';
                html += '\<div class="row">\'';
                html += '\<div class="col-md-6">\<h4>Watch Statistics for \' + (stats.period || "30 days") + '\</h4>\</div>\'';
                html += '\<div class="col-md-6 text-right">\'';
                html += '\<div class="btn-group btn-group-sm" role="group">\'';
                html += '\<button type="button" class="btn btn-sm \' + (currentView === "table" ? "btn-primary" : "btn-default") + '\" onclick="switchView(\'table\')">\<i class="fa fa-table">\</i> Table\</button>\'';
                html += '\<button type="button" class="btn btn-sm \' + (currentView === "cards" ? "btn-primary" : "btn-default") + '\" onclick="switchView(\'cards\')">\<i class="fa fa-th">\</i> Cards\</button>\'';
                html += '\</div>\'';
                html += '\</div>\'';
                html += '\</div>\'';
                html += '\</div>\'';
                
                // Filter and search controls
                html += '\<div class="col-lg-12">\'';
                html += '\<div class="row" style="margin-bottom: 15px;">\'';
                html += '\<div class="col-md-4">\'';
                html += '\<select class="form-control input-sm" id="media-filter" onchange="filterMedia(this.value)">\'';
                html += '\<option value="all" \' + (currentFilter === "all" ? "selected" : "") + '>All Media\</option>\'';
                html += '\<option value="Movie" \' + (currentFilter === "Movie" ? "selected" : "") + '>Movies\</option>\'';
                html += '\<option value="Episode" \' + (currentFilter === "Episode" ? "selected" : "") + '>TV Episodes\</option>\'';
                html += '\<option value="Series" \' + (currentFilter === "Series" ? "selected" : "") + '>TV Series\</option>\'';
                html += '\</select>\'';
                html += '\</div>\'';
                html += '\<div class="col-md-4">\'';
                html += '\<input type="text" class="form-control input-sm" placeholder="Search titles..." id="search-input" oninput="searchMedia(this.value)">\'';
                html += '\</div>\'';
                html += '\<div class="col-md-4">\'';
                html += '\<small class="text-muted" id="results-count">\</small>\'';
                html += '\</div>\'';
                html += '\</div>\'';
                html += '\</div>\'';
                
                // Most Watched Content
                if (stats.most_watched && stats.most_watched.length > 0) {
                    html += renderMostWatchedContent(stats.most_watched);
                }
                
                // User Statistics (collapsible)
                if (stats.user_stats && stats.user_stats.length > 0) {
                    html += '\<div class="col-lg-12" style="margin-top: 20px;">\'';
                    html += '\<div class="panel panel-default">\'';
                    html += '\<div class="panel-heading">\'';
                    html += '\<h5 class="panel-title">\'';
                    html += '\<a data-toggle="collapse" href="#user-stats-panel">\'';
                    html += '\<i class="fa fa-users">\</i> Server Users (\' + stats.user_stats.length + '\' total) \<i class="fa fa-chevron-down pull-right">\</i>\'';
                    html += '\</a>\'';
                    html += '\</h5>\'';
                    html += '\</div>\'';
                    html += '\<div id="user-stats-panel" class="panel-collapse collapse">\'';
                    html += '\<div class="panel-body">\'';
                    html += '\<div class="row">\'';
                    stats.user_stats.slice(0, 12).forEach(function(user, index) {
                        var lastActivity = "Never";
                        if (user.LastActivityDate && user.LastActivityDate !== "0001-01-01T00:00:00.0000000Z") {
                            var activityDate = new Date(user.LastActivityDate);
                            lastActivity = activityDate.toLocaleDateString();
                        }
                        var isAdmin = user.Policy && user.Policy.IsAdministrator;
                        var isDisabled = user.Policy && user.Policy.IsDisabled;
                        var badgeClass = isAdmin ? "label-success" : (isDisabled ? "label-danger" : "label-info");
                        var badgeText = isAdmin ? "Admin" : (isDisabled ? "Disabled" : "User");
                        
                        html += '\<div class="col-md-4 col-sm-6" style="margin-bottom: 10px;">\'';
                        html += '\<div class="media">\'';
                        html += '\<div class="media-left">\'';
                        html += '\<i class="fa fa-user fa-2x text-muted">\</i>\'';
                        html += '\</div>\'';
                        html += '\<div class="media-body">\'';
                        html += '\<h6 class="media-heading">\' + (user.Name || "Unknown User") + '\' \<span class="label \' + badgeClass + '">\' + badgeText + '\</span>\</h6>\'';
                        html += '\<small class="text-muted">Last Activity: \' + lastActivity + '\</small>\'';
                        html += '\</div>\'';
                        html += '\</div>\'';
                        html += '\</div>\'';
                    });
                    html += '\</div>\'';
                    html += '\</div>\'';
                    html += '\</div>\'';
                    html += '\</div>\'';
                    html += '\</div>\'';
                }
                
                // Recent Activity (collapsible)
                if (' . $showRecentActivity . ' && stats.recent_activity && stats.recent_activity.length > 0) {
                    html += '\<div class="col-lg-12">\'';
                    html += '\<div class="panel panel-default">\'';
                    html += '\<div class="panel-heading">\'';
                    html += '\<h5 class="panel-title">\'';
                    html += '\<a data-toggle="collapse" href="#recent-activity-panel">\'';
                    html += '\<i class="fa fa-clock-o">\</i> Recent Activity \<i class="fa fa-chevron-down pull-right">\</i>\'';
                    html += '\</a>\'';
                    html += '\</h5>\'';
                    html += '\</div>\'';
                    html += '\<div id="recent-activity-panel" class="panel-collapse collapse">\'';
                    html += '\<div class="panel-body">\'';
                    html += '\<div class="row">\'';
                    stats.recent_activity.slice(0, 10).forEach(function(activity) {
                        html += '\<div class="col-md-6" style="margin-bottom: 5px;">\'';
                        html += '\<i class="fa fa-play-circle text-success">\</i> \' + (activity.title || "Unknown Title");
                        if (activity.year) html += '\' (\' + activity.year + '\')\'';
                        html += '\<br>\<small class="text-muted">\' + (activity.added_at || "Unknown Date") + '\</small>\'';
                        html += '\</div>\'';
                    });
                    html += '\</div>\'';
                    html += '\</div>\'';
                    html += '\</div>\'';
                    html += '\</div>\'';
                    html += '\</div>\'';
                }
                
                // Check if we have any data to display
                var hasData = (stats.most_watched && stats.most_watched.length > 0) ||
                             (stats.user_stats && stats.user_stats.length > 0) ||
                             (stats.recent_activity && stats.recent_activity.length > 0);
                             
                if (!hasData) {
                    html += '\<div class="col-lg-12 text-center text-muted">\'';
                    html += '\<i class="fa fa-exclamation-circle fa-3x" style="margin-bottom: 10px;">\</i>\'';
                    html += '\<h4>No statistics available\</h4>\'';
                    html += '\<p>Start watching some content to see statistics here!\</p>\'';
                    html += '\</div>\'';
                }
                
                $("#watchstats-content").html(html);
            }

            // Auto-refresh setup
            var refreshInterval = ' . $refreshInterval . ';
            if (refreshInterval > 0) {
                watchStatsRefreshTimer = setInterval(function() {
                    refreshUserWatchStats();
                }, refreshInterval);
            }

            // Update time display every 30 seconds
            setInterval(updateWatchStatsLastRefreshTime, 30000);

            // Initial load
            $(document).ready(function() {
                refreshUserWatchStats();
            });

            // Cleanup timer when page unloads
            $(window).on("beforeunload", function() {
                if (watchStatsRefreshTimer) {
                    clearInterval(watchStatsRefreshTimer);
                }
            });
            
            function renderMostWatchedContent(items) {
                var html = '\<div class="col-lg-12">\'';
                html += '\<h5>\<i class="fa fa-star text-warning">\</i> Most Watched Content\</h5>\'';
                
                if (currentView === "table") {
                    html += renderTableView(items);
                } else {
                    html += renderCardView(items);
                }
                
                html += '\</div>\'';
                return html;
            }
            
            function renderTableView(items) {
                var filteredItems = filterAndSortItems(items);
                var html = '\<div class="table-responsive">\'';
                html += '\<table class="table table-striped table-hover table-condensed">\'';
                html += '\<thead>\'';
                html += '\<tr>\'';
                html += '\<th>\<a href="#" onclick="sortBy(\'title\')" class="text-decoration-none">Title \' + getSortIcon(\'title\') + '\</a>\</th>\'';
                html += '\<th>\<a href="#" onclick="sortBy(\'type\')" class="text-decoration-none">Type \' + getSortIcon(\'type\') + '\</a>\</th>\'';
                html += '\<th>\<a href="#" onclick="sortBy(\'total_plays\')" class="text-decoration-none">Users \' + getSortIcon(\'total_plays\') + '\</a>\</th>\'';
                html += '\<th>\<a href="#" onclick="sortBy(\'runtime\')" class="text-decoration-none">Runtime \' + getSortIcon(\'runtime\') + '\</a>\</th>\'';
                html += '\<th>\<a href="#" onclick="sortBy(\'year\')" class="text-decoration-none">Year \' + getSortIcon(\'year\') + '\</a>\</th>\'';
                html += '\</tr>\'';
                html += '\</thead>\'';
                html += '\<tbody>\'';
                
                filteredItems.slice(0, 20).forEach(function(item) {
                    var typeIcon = getTypeIcon(item.type);
                    var typeBadge = getTypeBadge(item.type);
                    
                    html += '\<tr>\'';
                    html += '\<td>\<strong>\' + (item.title || "Unknown Title") + '\</strong>\</td>\'';
                    html += '\<td>\' + typeIcon + '\' \' + typeBadge + '\</td>\'';
                    html += '\<td>\<span class="badge badge-primary">\' + (item.total_plays || 0) + '\</span>\</td>\'';
                    html += '\<td>\' + (item.runtime || "Unknown") + '\</td>\'';
                    html += '\<td>\' + (item.year || "N/A") + '\</td>\'';
                    html += '\</tr>\'';
                });
                
                html += '\</tbody>\'';
                html += '\</table>\'';
                html += '\</div>\'';
                
                updateResultsCount(filteredItems.length);
                return html;
            }
            
            function renderCardView(items) {
                var filteredItems = filterAndSortItems(items);
                var html = '\<div class="row">\'';
                
                filteredItems.slice(0, 20).forEach(function(item) {
                    var typeIcon = getTypeIcon(item.type);
                    var typeBadge = getTypeBadge(item.type);
                    
                    html += '\<div class="col-lg-4 col-md-6 col-sm-12" style="margin-bottom: 15px;">\'';
                    html += '\<div class="panel panel-default">\'';
                    html += '\<div class="panel-body">\'';
                    html += '\<div class="media">\'';
                    html += '\<div class="media-left">\'';
                    html += '\<div class="text-center" style="width: 60px;">\'';
                    html += typeIcon + '\<br>\'';
                    html += '\<span class="badge badge-primary">\' + (item.total_plays || 0) + '\</span>\'';
                    html += '\</div>\'';
                    html += '\</div>\'';
                    html += '\<div class="media-body">\'';
                    html += '\<h6 class="media-heading">\<strong>\' + (item.title || "Unknown Title") + '\</strong>\</h6>\'';
                    html += '\<p class="text-muted" style="margin-bottom: 5px;">\'';
                    html += typeBadge;
                    if (item.year) html += '\' • \' + item.year;
                    if (item.runtime && item.runtime !== "Unknown") html += '\' • \' + item.runtime;
                    html += '\</p>\'';
                    html += '\</div>\'';
                    html += '\</div>\'';
                    html += '\</div>\'';
                    html += '\</div>\'';
                    html += '\</div>\'';
                });
                
                html += '\</div>\'';
                updateResultsCount(filteredItems.length);
                return html;
            }
            
            function filterAndSortItems(items) {
                var filtered = items.filter(function(item) {
                    // Apply media type filter
                    if (currentFilter !== "all" && item.type !== currentFilter) {
                        return false;
                    }
                    
                    // Apply search filter
                    var searchTerm = document.getElementById(\'search-input\') ? document.getElementById(\'search-input\').value.toLowerCase() : "";
                    if (searchTerm && item.title && item.title.toLowerCase().indexOf(searchTerm) === -1) {
                        return false;
                    }
                    
                    return true;
                });
                
                // Sort items
                filtered.sort(function(a, b) {
                    var aVal = a[currentSort.field];
                    var bVal = b[currentSort.field];
                    
                    // Handle different data types
                    if (currentSort.field === \'year\' || currentSort.field === \'total_plays\') {
                        aVal = parseInt(aVal) || 0;
                        bVal = parseInt(bVal) || 0;
                    } else {
                        aVal = (aVal || "").toString().toLowerCase();
                        bVal = (bVal || "").toString().toLowerCase();
                    }
                    
                    var result = aVal < bVal ? -1 : (aVal > bVal ? 1 : 0);
                    return currentSort.direction === \'desc\' ? -result : result;
                });
                
                return filtered;
            }
            
            function getTypeIcon(type) {
                switch(type) {
                    case \'Movie\': return '\<i class="fa fa-film fa-2x text-primary">\</i>\';
                    case \'Episode\': return '\<i class="fa fa-tv fa-2x text-success">\</i>\';
                    case \'Series\': return '\<i class="fa fa-television fa-2x text-info">\</i>\';
                    case \'Audio\': return '\<i class="fa fa-music fa-2x text-warning">\</i>\';
                    default: return '\<i class="fa fa-play-circle fa-2x text-muted">\</i>\';
                }
            }
            
            function getTypeBadge(type) {
                switch(type) {
                    case \'Movie\': return '\<span class="label label-primary">Movie\</span>\';
                    case \'Episode\': return '\<span class="label label-success">TV Episode\</span>\';
                    case \'Series\': return '\<span class="label label-info">TV Series\</span>\';
                    case \'Audio\': return '\<span class="label label-warning">Music\</span>\';
                    default: return '\<span class="label label-default">\' + (type || "Unknown") + '\</span>\';
                }
            }
            
            function getSortIcon(field) {
                if (currentSort.field !== field) {
                    return '\<i class="fa fa-sort text-muted">\</i>\';
                }
                return currentSort.direction === \'asc\' ? 
                    '\<i class="fa fa-sort-up text-primary">\</i>\' : 
                    '\<i class="fa fa-sort-down text-primary">\</i>\';
            }
            
            function sortBy(field) {
                if (currentSort.field === field) {
                    currentSort.direction = currentSort.direction === \'asc\' ? \'desc\' : \'asc\';
                } else {
                    currentSort.field = field;
                    currentSort.direction = \'desc\';
                }
                renderWatchStatsUI();
            }
            
            function filterMedia(type) {
                currentFilter = type;
                renderWatchStatsUI();
            }
            
            function searchMedia(term) {
                renderWatchStatsUI();
            }
            
            function switchView(view) {
                currentView = view;
                renderWatchStatsUI();
            }
            
            function updateResultsCount(count) {
                setTimeout(function() {
                    var countElement = document.getElementById(\'results-count\');
                    if (countElement) {
                        countElement.innerHTML = \'Showing \' + count + \' item\' + (count !== 1 ? \'s\' : \'\') + \'\'';
                    }
                }, 100);
            }
            
            </script>
            ';
        }
    }

    /**
     * Main function to get watch statistics
     */
    public function getUserWatchStats($options = null)
    {
        if (!$this->homepageItemPermissions($this->userWatchStatsHomepagePermissions('main'), true)) {
            $this->setAPIResponse('error', 'User not approved to view this homepage item - check plugin configuration', 401);
            return false;
        }

        try {
            $mediaServer = $this->config['homepageUserWatchStatsService'] ?? 'plex';
            $days = intval($this->config['homepageUserWatchStatsDays'] ?? 30);
            
            switch (strtolower($mediaServer)) {
                case 'plex':
                    $stats = $this->getPlexWatchStats($days);
                    break;
                case 'emby':
                    $stats = $this->getEmbyWatchStats($days);
                    break;
                case 'jellyfin':
                    $stats = $this->getJellyfinWatchStats($days);
                    break;
                default:
                    $stats = $this->getPlexWatchStats($days);
                    break;
            }
            
            if (isset($stats['error']) && $stats['error']) {
                $this->setAPIResponse('error', $stats['message'], 500);
                return false;
            }
            
            $this->setAPIResponse('success', 'Watch statistics retrieved successfully', 200, $stats);
            return true;
            
        } catch (Exception $e) {
            // User Watch Stats Error: " . $e->getMessage();
            $this->setAPIResponse('error', 'Failed to retrieve watch statistics: ' . $e->getMessage(), 500);
            return false;
        }
    }

    /**
     * Get Plex watch statistics via Tautulli API
     */
    private function getPlexWatchStats($days = 30)
    {
        $tautulliUrl = $this->config['userWatchStatsURL'] ?? '';
        $tautulliToken = $this->config['userWatchStatsApikey'] ?? '';
        
        if (empty($tautulliUrl) || empty($tautulliToken)) {
            return ['error' => true, 'message' => 'Tautulli URL or API key not configured'];
        }

        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $stats = [
            'period' => "{$days} days",
            'start_date' => $startDate,
            'end_date' => $endDate,
            'most_watched' => $this->getTautulliMostWatched($tautulliUrl, $tautulliToken, $days),
            'least_watched' => $this->getTautulliLeastWatched($tautulliUrl, $tautulliToken, $days),
            'user_stats' => $this->getTautulliUserStats($tautulliUrl, $tautulliToken, $days),
            'recent_activity' => $this->getTautulliRecentActivity($tautulliUrl, $tautulliToken),
            'top_users' => $this->getTautulliTopUsers($tautulliUrl, $tautulliToken, $days)
        ];

        return $stats;
    }

    /**
     * Get most watched content from Tautulli
     */
    private function getTautulliMostWatched($url, $token, $days)
    {
        $apiURL = rtrim($url, '/') . '/api/v2?apikey=' . $token . '&cmd=get_home_stats&time_range=' . $days . '&stats_type=plays&stats_count=10';
        
        try {
            $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);
            
            if ($response->success) {
                $data = json_decode($response->body, true);
                return $data['response']['data'] ?? [];
            }
        } catch (Requests_Exception $e) {
            // Tautulli Most Watched Error: " . $e->getMessage();
        }

        return [];
    }

    /**
     * Get user statistics from Tautulli
     */
    private function getTautulliUserStats($url, $token, $days)
    {
        $apiURL = rtrim($url, '/') . '/api/v2?apikey=' . $token . '&cmd=get_user_watch_time_stats&time_range=' . $days;
        
        try {
            $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);
            
            if ($response->success) {
                $data = json_decode($response->body, true);
                return $data['response']['data'] ?? [];
            }
        } catch (Requests_Exception $e) {
            // Tautulli User Stats Error: " . $e->getMessage();
        }

        return [];
    }

    /**
     * Get top users from Tautulli
     */
    private function getTautulliTopUsers($url, $token, $days)
    {
        $apiURL = rtrim($url, '/') . '/api/v2?apikey=' . $token . '&cmd=get_users&length=25';
        
        try {
            $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);
            
            if ($response->success) {
                $data = json_decode($response->body, true);
                $users = $data['response']['data']['data'] ?? [];
                
                // Sort by play count
                usort($users, function($a, $b) {
                    return ($b['play_count'] ?? 0) - ($a['play_count'] ?? 0);
                });
                
                return array_slice($users, 0, 10);
            }
        } catch (Requests_Exception $e) {
            // Tautulli Top Users Error: " . $e->getMessage();
        }

        return [];
    }

    /**
     * Get recent activity from Tautulli
     */
    private function getTautulliRecentActivity($url, $token)
    {
        $apiURL = rtrim($url, '/') . '/api/v2?apikey=' . $token . '&cmd=get_recently_added&count=10';
        
        try {
            $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);
            
            if ($response->success) {
                $data = json_decode($response->body, true);
                return $data['response']['data']['recently_added'] ?? [];
            }
        } catch (Requests_Exception $e) {
            // Tautulli Recent Activity Error: " . $e->getMessage();
        }

        return [];
    }

    /**
     * Get least watched content (inverse of most watched)
     */
    private function getTautulliLeastWatched($url, $token, $days)
    {
        $apiURL = rtrim($url, '/') . '/api/v2?apikey=' . $token . '&cmd=get_libraries';
        
        try {
            $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);
            
            if ($response->success) {
                $data = json_decode($response->body, true);
                $libraries = $data['response']['data'] ?? [];
                
                $leastWatched = [];
                foreach ($libraries as $library) {
                    $libraryStats = $this->getTautulliLibraryStats($url, $token, $library['section_id'], $days);
                    if (!empty($libraryStats)) {
                        $leastWatched = array_merge($leastWatched, array_slice($libraryStats, -10));
                    }
                }
                
                return $leastWatched;
            }
        } catch (Requests_Exception $e) {
            // Tautulli Least Watched Error: " . $e->getMessage();
        }

        return [];
    }

    /**
     * Get library statistics for least watched calculation
     */
    private function getTautulliLibraryStats($url, $token, $sectionId, $days)
    {
        $apiURL = rtrim($url, '/') . '/api/v2?apikey=' . $token . '&cmd=get_library_media_info&section_id=' . $sectionId . '&length=50&order_column=play_count&order_dir=asc';
        
        try {
            $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);
            
            if ($response->success) {
                $data = json_decode($response->body, true);
                return $data['response']['data']['data'] ?? [];
            }
        } catch (Requests_Exception $e) {
            // Tautulli Library Stats Error: " . $e->getMessage();
        }

        return [];
    }

    /**
     * Get Emby watch statistics
     */
    private function getEmbyWatchStats($days = 30)
    {
        $embyUrl = $this->config['userWatchStatsURL'] ?? '';
        $embyToken = $this->config['userWatchStatsApikey'] ?? '';
        
        if (empty($embyUrl) || empty($embyToken)) {
            return ['error' => true, 'message' => 'Emby URL or API key not configured'];
        }

        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $stats = [
            'period' => "{$days} days",
            'start_date' => $startDate,
            'end_date' => $endDate,
            'most_watched' => $this->getEmbyMostWatched($embyUrl, $embyToken, $days),
            'least_watched' => [],  // Emby doesn't have a direct least watched API
            'user_stats' => $this->getEmbyUserStats($embyUrl, $embyToken, $days),
            'recent_activity' => $this->getEmbyRecentActivity($embyUrl, $embyToken),
            'watch_history' => $this->getEmbyWatchHistory($embyUrl, $embyToken, $days),
            'top_users' => $this->getEmbyTopUsers($embyUrl, $embyToken, $days)
        ];

        return $stats;
    }

    /**
     * Get Jellyfin watch statistics
     */
    private function getJellyfinWatchStats($days = 30)
    {
        $jellyfinUrl = $this->config['jellyfinURL'] ?? '';
        $jellyfinToken = $this->config['jellyfinToken'] ?? '';
        
        if (empty($jellyfinUrl) || empty($jellyfinToken)) {
            return ['error' => true, 'message' => 'Jellyfin URL or API key not configured'];
        }

        // Implement Jellyfin-specific statistics gathering
        return $this->getGenericMediaServerStats('jellyfin', $jellyfinUrl, $jellyfinToken, $days);
    }

    /**
     * Generic media server stats for Emby/Jellyfin
     */
    private function getGenericMediaServerStats($type, $url, $token, $days)
    {
        // Basic structure for now - can be expanded based on Emby/Jellyfin APIs
        return [
            'period' => "{$days} days",
            'start_date' => date('Y-m-d', strtotime("-{$days} days")),
            'end_date' => date('Y-m-d'),
            'message' => ucfirst($type) . ' statistics coming soon',
            'most_watched' => [],
            'least_watched' => [],
            'user_stats' => [],
            'recent_activity' => [],
            'top_users' => []
        ];
    }

    /**
     * Format duration for display
     */
    private function formatDuration($seconds)
    {
        if ($seconds < 3600) {
            return gmdate('i:s', $seconds);
        } else {
            return gmdate('H:i:s', $seconds);
        }
    }

    /**
     * Get user avatar URL
     */
    private function getUserAvatar($userId, $mediaServer = 'plex')
    {
        switch ($mediaServer) {
            case 'plex':
                return $this->getPlexUserAvatar($userId);
            case 'emby':
                return $this->getEmbyUserAvatar($userId);
            case 'jellyfin':
                return $this->getJellyfinUserAvatar($userId);
            default:
                return '/plugins/images/organizr/user-bg.png';
        }
    }

    /**
     * Get Plex user avatar
     */
    private function getPlexUserAvatar($userId)
    {
        $tautulliUrl = $this->config['plexURL'] ?? '';
        $tautulliToken = $this->config['plexToken'] ?? '';
        
        if (empty($tautulliUrl) || empty($tautulliToken)) {
            return '/plugins/images/organizr/user-bg.png';
        }

        $apiURL = rtrim($tautulliUrl, '/') . '/api/v2?apikey=' . $tautulliToken . '&cmd=get_user_thumb&user_id=' . $userId;

        try {
            $options = $this->requestOptions($tautulliUrl, null, $this->config['plexDisableCertCheck'] ?? false, $this->config['plexUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);

            if ($response->success) {
                $data = json_decode($response->body, true);
                return $data['response']['data']['thumb'] ?? '/plugins/images/organizr/user-bg.png';
            }
        } catch (Requests_Exception $e) {
            // Tautulli User Avatar Error: " . $e->getMessage();
        }

        return '/plugins/images/organizr/user-bg.png';
    }

    /**
     * Get most watched content from Emby (server-wide statistics)
     */
    private function getEmbyMostWatched($url, $token, $days)
    {
        // Skip activity log approach and go directly to simple media approach
        return $this->getEmbySimpleMostWatched($url, $token);
    }
    
    /**
     * Get most watched content by aggregating play counts across all users
     */
    private function getEmbySimpleMostWatched($url, $token)
    {
        // Since user-specific endpoints are not accessible with API key,
        // fall back to using the global Items API sorted by DatePlayed
        $apiURL = rtrim($url, '/') . '/emby/Items?api_key=' . $token . 
                  '&Recursive=true&IncludeItemTypes=Movie,Episode&Fields=Name,RunTimeTicks,ProductionYear,DatePlayed' .
                  '&SortBy=DatePlayed&SortOrder=Descending&Limit=20';
        
        try {
            $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);
            
            if ($response->success) {
                $responseBody = $response->body;
                
                // Check if response contains SQLite exception or other error indicators
                if (is_string($responseBody) && (
                    strpos($responseBody, 'SQLiteException') !== false ||
                    strpos($responseBody, 'error') !== false ||
                    strpos($responseBody, 'Error') !== false ||
                    !trim($responseBody) || 
                    $responseBody === 'null'
                )) {
                    // Fall back to recently created items if DatePlayed sorting fails
                    return $this->getEmbyFallbackMostWatched($url, $token);
                }
                
                $data = json_decode($responseBody, true);
                
                // Check if JSON decode failed or returned invalid data
                if ($data === null || !is_array($data) || !isset($data['Items'])) {
                    return $this->getEmbyFallbackMostWatched($url, $token);
                }
                
                $items = $data['Items'] ?? [];
                
                $mostWatched = [];
                foreach ($items as $item) {
                    // Only include items that have been played (DatePlayed exists)
                    if (!empty($item['DatePlayed'])) {
                        $mostWatched[] = [
                            'title' => $item['Name'] ?? 'Unknown Title',
                            'total_plays' => 1, // We can't get actual play count from this API
                            'runtime' => isset($item['RunTimeTicks']) ? $this->formatDuration($item['RunTimeTicks'] / 10000000) : 'Unknown',
                            'type' => $item['Type'] ?? 'Unknown',
                            'year' => $item['ProductionYear'] ?? null
                        ];
                    }
                }
                
                // If no items with DatePlayed, fall back to recently created
                if (empty($mostWatched)) {
                    return $this->getEmbyFallbackMostWatched($url, $token);
                }
                
                return $mostWatched;
            } else {
                // HTTP request failed, use fallback
                return $this->getEmbyFallbackMostWatched($url, $token);
            }
        } catch (Exception $e) {
            // Any exception triggers fallback
            return $this->getEmbyFallbackMostWatched($url, $token);
        }
        
        // Fallback if we somehow get here
        return $this->getEmbyFallbackMostWatched($url, $token);
    }
    
    /**
     * Fallback method - calculate most watched by aggregating play counts across all users
     */
    private function getEmbyFallbackMostWatched($url, $token)
    {
        // Get all users first
        $users = $this->getEmbyUserStats($url, $token, 30);
        $playCountsByItem = [];
        $itemDetails = [];
        
        // For each user, get their watched items
        foreach ($users as $user) {
            if (isset($user['Policy']['IsDisabled']) && $user['Policy']['IsDisabled']) {
                continue; // Skip disabled users
            }
            
            $userId = $user['Id'];
            $userWatchedItems = $this->getEmbyUserWatchedContent($url, $token, $userId, 30);
            
            // Aggregate play counts - count each user who has played the item
            foreach ($userWatchedItems as $item) {
                $itemId = $item['Id'] ?? $item['title']; // Use ID if available, title as fallback
                $isPlayed = $item['is_played'] ?? false;
                
                if ($isPlayed) {
                    // Increment count for each user who has played this item
                    $playCountsByItem[$itemId] = ($playCountsByItem[$itemId] ?? 0) + 1;
                    
                    // Store item details (only need to do this once per item)
                    if (!isset($itemDetails[$itemId])) {
                        $itemDetails[$itemId] = [
                            'title' => $item['title'] ?? 'Unknown Title',
                            'runtime' => $item['runtime'] ?? 'Unknown',
                            'type' => $item['type'] ?? 'Unknown',
                            'year' => $item['year'] ?? null
                        ];
                    }
                }
            }
        }
        
        // Sort by total play count (descending)
        arsort($playCountsByItem);
        
        // Build most watched array
        $mostWatched = [];
        $count = 0;
        foreach ($playCountsByItem as $itemId => $totalPlays) {
            if ($count >= 20) break; // Limit to top 20
            
            $details = $itemDetails[$itemId] ?? [
                'title' => 'Unknown Title',
                'runtime' => 'Unknown',
                'type' => 'Unknown',
                'year' => null
            ];
            
            $mostWatched[] = [
                'title' => $details['title'],
                'total_plays' => $totalPlays,
                'runtime' => $details['runtime'],
                'type' => $details['type'],
                'year' => $details['year']
            ];
            
            $count++;
        }
        
        // If no watched content found, fall back to recent items as last resort
        if (empty($mostWatched)) {
            return $this->getEmbyRecentItemsFallback($url, $token);
        }
        
        return $mostWatched;
    }
    
    /**
     * Final fallback - recent items when no watch data is available
     */
    private function getEmbyRecentItemsFallback($url, $token)
    {
        $apiURL = rtrim($url, '/') . '/emby/Items?api_key=' . $token . 
                  '&Recursive=true&IncludeItemTypes=Movie,Episode&Fields=Name,RunTimeTicks,ProductionYear,DateCreated' .
                  '&SortBy=DateCreated&SortOrder=Descending&Limit=10';
        
        try {
            $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);
            
            if ($response->success) {
                $data = json_decode($response->body, true);
                $items = $data['Items'] ?? [];
                
                $recentItems = [];
                foreach ($items as $item) {
                    $recentItems[] = [
                        'title' => $item['Name'] ?? 'Unknown Title',
                        'total_plays' => 0, // No play data available
                        'runtime' => isset($item['RunTimeTicks']) ? $this->formatDuration($item['RunTimeTicks'] / 10000000) : 'Unknown',
                        'type' => $item['Type'] ?? 'Unknown',
                        'year' => $item['ProductionYear'] ?? null
                    ];
                }
                
                return $recentItems;
            }
        } catch (Requests_Exception $e) {
            // Nothing we can do at this point
        }
        
        return [];
    }
    
    /**
     * Get total play count for a specific item across all users
     */
    private function getEmbyItemTotalPlays($url, $token, $itemId)
    {
        $totalPlays = 0;
        $users = $this->getEmbyUserStats($url, $token, 30);
        
        foreach ($users as $user) {
            if (isset($user['Policy']['IsDisabled']) && $user['Policy']['IsDisabled']) {
                continue;
            }
            
            $userId = $user['Id'];
            $userItemURL = rtrim($url, '/') . '/emby/Users/' . $userId . '/Items/' . $itemId . '?api_key=' . $token . '&Fields=UserData';
            
            try {
                $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
                $response = Requests::get($userItemURL, [], $options);
                
                if ($response->success) {
                    $itemData = json_decode($response->body, true);
                    $totalPlays += $itemData['UserData']['PlayCount'] ?? 0;
                }
            } catch (Requests_Exception $e) {
                // Continue with other users if one fails
                continue;
            }
        }
        
        return $totalPlays;
    }
    

    /**
     * Get watched content for a specific user
     */
    private function getEmbyUserWatchedContent($url, $token, $userId, $days)
    {
        $apiURL = rtrim($url, '/') . '/emby/Users/' . $userId . '/Items?api_key=' . $token . 
                  '&Recursive=true&IncludeItemTypes=Movie,Episode&IsPlayed=true&Limit=50' .
                  '&Fields=Name,PlayCount,UserData,RunTimeTicks,ProductionYear';
        
        try {
            $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);
            
            if ($response->success) {
                $data = json_decode($response->body, true);
                $items = $data['Items'] ?? [];

                $watchedContent = [];
                foreach ($items as $item) {
                    // Use Played flag instead of PlayCount since Emby may not increment PlayCount properly
                    if (($item['UserData']['Played'] ?? false)) {
                        $watchedContent[] = [
                            'Id' => $item['Id'] ?? null,
                            'title' => $item['Name'] ?? 'Unknown Title',
                            'is_played' => true, // Mark as played for our aggregation
                            'runtime' => $item['RunTimeTicks'] ? $this->formatDuration($item['RunTimeTicks'] / 10000000) : 'Unknown',
                            'type' => $item['Type'] ?? 'Unknown',
                            'year' => $item['ProductionYear'] ?? null
                        ];
                    }
                }
                return $watchedContent;
            }
        } catch (Requests_Exception $e) {
            // Emby User Watched Content Error: " . $e->getMessage();
        }

        return [];
    }

    /**
     * Get user statistics from Emby
     */
    private function getEmbyUserStats($url, $token, $days)
    {
        $apiURL = rtrim($url, '/') . '/emby/Users?api_key=' . $token;
        
        try {
            $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);
            
            if ($response->success) {
                $data = json_decode($response->body, true);
                return $data ?? [];
            }
        } catch (Requests_Exception $e) {
            // Emby User Stats Error: " . $e->getMessage();
        }

        return [];
    }

    /**
     * Get top users from Emby
     */
    private function getEmbyTopUsers($url, $token, $days)
    {
        $apiURL = rtrim($url, '/') . '/emby/Users?api_key=' . $token;
        
        try {
            $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);
            
            if ($response->success) {
                $data = json_decode($response->body, true);
                $users = $data ?? [];
                
                $topUsers = [];
                foreach ($users as $user) {
                    if (!isset($user['Policy']['IsHidden']) || !$user['Policy']['IsHidden']) {
                        $topUsers[] = [
                            'username' => $user['Name'] ?? 'Unknown User',
                            'friendly_name' => $user['Name'] ?? 'Unknown User',
                            'play_count' => 0,  // Emby doesn't provide direct play count per user
                            'last_seen' => $user['LastActivityDate'] ?? null
                        ];
                    }
                }
                
                return array_slice($topUsers, 0, 10);
            }
        } catch (Requests_Exception $e) {
            // Emby Top Users Error: " . $e->getMessage();
        }

        return [];
    }

    /**
     * Get recent activity from Emby
     */
    private function getEmbyRecentActivity($url, $token)
    {
        $apiURL = rtrim($url, '/') . '/emby/Items/Latest?api_key=' . $token . '&Limit=10&Recursive=true&IncludeItemTypes=Movie,Episode';
        
        try {
            $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);
            
            if ($response->success) {
                $data = json_decode($response->body, true);
                
                $recentActivity = [];
                foreach ($data as $item) {
                    $recentActivity[] = [
                        'title' => $item['Name'] ?? 'Unknown Title',
                        'type' => $item['Type'] ?? 'Unknown',
                        'added_at' => $item['DateCreated'] ?? 'Unknown Date',
                        'year' => $item['ProductionYear'] ?? null
                    ];
                }
                
                return $recentActivity;
            }
        } catch (Requests_Exception $e) {
            // Emby Recent Activity Error: " . $e->getMessage();
        }

        return [];
    }

    /**
     * Get watch history from Emby
     */
    private function getEmbyWatchHistory($url, $token, $days)
    {
        // Try without date filter first to see if we get any played content
        $apiURL = rtrim($url, '/') . '/emby/UserLibrary/Items?api_key=' . $token . 
                  '&Recursive=true&IncludeItemTypes=Movie,Episode&IsPlayed=true&Limit=20' .
                  '&Fields=Name,PlayCount,UserData,RunTimeTicks,DateCreated,UserDataLastPlayedDate';
        
        try {
            $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);
            
            if ($response->success) {
                $data = json_decode($response->body, true);
                $items = $data['Items'] ?? [];

                $watchHistory = [];
                foreach ($items as $item) {
                    $watchHistory[] = [
                        'title' => $item['Name'] ?? 'Unknown Title',
                        'play_count' => $item['UserData']['PlayCount'] ?? 0,
                        'runtime' => $item['RunTimeTicks'] ? $this->formatDuration($item['RunTimeTicks'] / 10000000) : 'Unknown',
                        'type' => $item['Type'] ?? 'Unknown',
                        'year' => $item['ProductionYear'] ?? null,
                        'last_played' => $item['UserData']['LastPlayedDate'] ?? 'Never'
                    ];
                }

                return $watchHistory;
            }
        } catch (Requests_Exception $e) {
            // Emby Watch History Error: " . $e->getMessage();
        }

        return [];
    }

    /**
     * Get Emby user avatar
     */
    private function getEmbyUserAvatar($userId)
    {
        // Implement Emby avatar logic
        return '/plugins/images/organizr/user-bg.png';
    }

    /**
     * Get Jellyfin user avatar
     */
    private function getJellyfinUserAvatar($userId)
    {
        // Implement Jellyfin avatar logic
        return '/plugins/images/organizr/user-bg.png';
    }
}
