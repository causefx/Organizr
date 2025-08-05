<?php

/**
 * JellyStat Homepage Plugin for Organizr
 * Supports both Emby and Jellyfin servers via JellyStat API or embedded interface
 */

trait JellyStatHomepageItem
{
    public function jellystatSettingsArray($infoOnly = false)
    {
        $homepageInformation = [
            'name' => 'JellyStat',
            'enabled' => true,
            'image' => 'plugins/images/homepage/jellystat.png',
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
                    $this->settingsOption('enable', 'homepageJellyStatEnabled'),
                    $this->settingsOption('auth', 'homepageJellyStatAuth'),
                ],
                'Display Mode' => [
                    $this->settingsOption('select', 'homepageJellyStatDisplayMode', ['label' => 'Display Mode', 'options' => [
                        ['name' => 'Native Statistics View', 'value' => 'native'],
                        ['name' => 'Embedded JellyStat Interface', 'value' => 'iframe']
                    ]]),
                ],
                'Connection' => [
                    $this->settingsOption('url', 'jellyStatURL', ['label' => 'JellyStat URL', 'help' => 'URL to your JellyStat instance']),
                    $this->settingsOption('url', 'jellyStatInternalURL', ['label' => 'Internal JellyStat URL (optional)', 'help' => 'Internal URL for server-side API calls (e.g., http://192.168.80.77:3000). If not set, uses main URL.']),
                    $this->settingsOption('token', 'jellyStatApikey', ['label' => 'JellyStat API Key', 'help' => 'API key for JellyStat (required for native mode)']),
                    $this->settingsOption('disable-cert-check', 'jellyStatDisableCertCheck'),
                    $this->settingsOption('use-custom-certificate', 'jellyStatUseCustomCertificate'),
                ],
                'Native Mode Options' => [
                    $this->settingsOption('number', 'homepageJellyStatRefresh', ['label' => 'Auto-refresh Interval (minutes)', 'min' => 1, 'max' => 60]),
                    $this->settingsOption('number', 'homepageJellyStatDays', ['label' => 'Statistics Period (days)', 'min' => 1, 'max' => 365]),
                    $this->settingsOption('switch', 'homepageJellyStatShowLibraries', ['label' => 'Show Library Statistics']),
                    $this->settingsOption('switch', 'homepageJellyStatShowUsers', ['label' => 'Show User Statistics']),
                    $this->settingsOption('switch', 'homepageJellyStatShowMostWatched', ['label' => 'Show Most Watched Content']),
                    $this->settingsOption('switch', 'homepageJellyStatShowRecentActivity', ['label' => 'Show Recent Activity']),
                    $this->settingsOption('number', 'homepageJellyStatMaxItems', ['label' => 'Maximum Items to Display', 'min' => 5, 'max' => 50]),
                ],
                'Most Watched Content' => [
                    $this->settingsOption('switch', 'homepageJellyStatShowMostWatchedMovies', ['label' => 'Show Most Watched Movies with Posters']),
                    $this->settingsOption('switch', 'homepageJellyStatShowMostWatchedShows', ['label' => 'Show Most Watched TV Shows with Posters']),
                    $this->settingsOption('switch', 'homepageJellyStatShowMostListenedMusic', ['label' => 'Show Most Listened Music with Cover Art']),
                    $this->settingsOption('number', 'homepageJellyStatMostWatchedCount', ['label' => 'Number of Most Watched Items to Display', 'min' => 1, 'max' => 50]),
                ],
                'Iframe Mode Options' => [
                    $this->settingsOption('number', 'homepageJellyStatIframeHeight', ['label' => 'Iframe Height (pixels)', 'min' => 300, 'max' => 2000]),
                    $this->settingsOption('switch', 'homepageJellyStatIframeScrolling', ['label' => 'Allow Scrolling in Iframe']),
                ],
                'Test Connection' => [
                    $this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
                    $this->settingsOption('test', 'jellystat'),
                ]
            ]
        ];
        return array_merge($homepageInformation, $homepageSettings);
    }

    public function testConnectionJellyStat()
    {
        if (!$this->homepageItemPermissions($this->jellystatHomepagePermissions('test'), true)) {
            return false;
        }
        
        $url = $this->config['jellyStatURL'] ?? '';
        $token = $this->config['jellyStatApikey'] ?? '';
        $disableCert = $this->config['jellyStatDisableCertCheck'] ?? false;
        $customCert = $this->config['jellyStatUseCustomCertificate'] ?? false;
        
        if (empty($url)) {
            $this->setAPIResponse('error', 'JellyStat URL not configured', 500);
            return false;
        }
        
        $displayMode = $this->config['homepageJellyStatDisplayMode'] ?? 'native';
        
        if ($displayMode === 'iframe') {
            // For iframe mode, just test if the URL is reachable (use main URL for frontend)
            try {
                $options = $this->requestOptions($url, null, $disableCert, $customCert);
                $response = Requests::get($this->qualifyURL($url), [], $options);
                if ($response->success) {
                    $this->setAPIResponse('success', 'Successfully connected to JellyStat', 200);
                    return true;
                } else {
                    $this->setAPIResponse('error', 'Failed to connect to JellyStat URL', 500);
                    return false;
                }
            } catch (Exception $e) {
                $this->setAPIResponse('error', 'Connection test failed: ' . $e->getMessage(), 500);
                return false;
            }
        } else {
            // For native mode, test API connection
            if (empty($token)) {
                $this->setAPIResponse('error', 'JellyStat API key not configured for native mode', 500);
                return false;
            }
            
            try {
                // Use internal URL for server-side API calls if configured, otherwise use main URL
                $internalUrl = $this->config['jellyStatInternalURL'] ?? '';
                $apiUrl = !empty($internalUrl) ? $internalUrl : $url;
                
                $options = $this->requestOptions($apiUrl, null, $disableCert, $customCert);
                
                // Test JellyStat API - use query parameter authentication
                $testUrl = $this->qualifyURL($apiUrl) . '/api/getLibraries?apiKey=' . urlencode($token);
                
                $response = Requests::get($testUrl, [], $options);
                if ($response->success) {
                    $data = json_decode($response->body, true);
                    if (isset($data) && is_array($data) && !isset($data['error'])) {
                        $this->setAPIResponse('success', 'Successfully connected to JellyStat API', 200);
                        return true;
                    }
                    // Check if there's an error message in the response
                    if (isset($data['error'])) {
                        $this->writeLog('error', 'JellyStat API test error: ' . $data['error']);
                        $this->setAPIResponse('error', 'JellyStat API error: ' . $data['error'], 500);
                        return false;
                    }
                    // Log the actual response for debugging
                    $this->writeLog('error', 'JellyStat API test: Valid HTTP response but invalid data format. Response: ' . substr($response->body, 0, 500));
                }
                
                // Log first endpoint failure details
                $firstError = "HTTP {$response->status_code}: " . substr($response->body, 0, 200);
                $this->writeLog('error', "JellyStat API test failed on /api/getLibraries: {$firstError}");
                
                // If libraries test failed, the API key is likely invalid
                $this->writeLog('error', 'JellyStat API key appears to be invalid or JellyStat API is not responding');
                
                // Try basic connection test to see if JellyStat is even running
                $response = Requests::get($this->qualifyURL($apiUrl), [], $options);
                if ($response->success) {
                    $this->setAPIResponse('error', 'JellyStat is reachable but API key is invalid or API endpoints are not responding correctly.', 500);
                } else {
                    $this->setAPIResponse('error', 'Cannot connect to JellyStat URL. Check URL and network connectivity.', 500);
                }
                return false;
                
            } catch (Exception $e) {
                $this->writeLog('error', 'JellyStat API test exception: ' . $e->getMessage());
                $this->setAPIResponse('error', 'Connection test failed: ' . $e->getMessage(), 500);
                return false;
            }
        }
    }
    
    public function jellystatHomepagePermissions($key = null)
    {
        $displayMode = $this->config['homepageJellyStatDisplayMode'] ?? 'native';
        
        // For iframe mode, only URL is required; for native mode, both URL and API key are required
        $requiredFields = ['jellyStatURL'];
        if ($displayMode === 'native') {
            $requiredFields[] = 'jellyStatApikey';
        }
        
        $permissions = [
            'test' => [
                'enabled' => [
                    'homepageJellyStatEnabled',
                ],
                'auth' => [
                    'homepageJellyStatAuth',
                ],
                'not_empty' => $requiredFields
            ],
            'main' => [
                'enabled' => [
                    'homepageJellyStatEnabled'
                ],
                'auth' => [
                    'homepageJellyStatAuth'
                ],
                'not_empty' => $requiredFields
            ]
        ];
        return $this->homepageCheckKeyPermissions($key, $permissions);
    }

    public function homepageOrderJellyStat()
    {
        if ($this->homepageItemPermissions($this->jellystatHomepagePermissions('main'))) {
            $displayMode = $this->config['homepageJellyStatDisplayMode'] ?? 'native';
            
            if ($displayMode === 'iframe') {
                return $this->renderJellyStatIframe();
            } else {
                return $this->renderJellyStatNative();
            }
        }
    }

    private function renderJellyStatIframe()
    {
        $url = $this->config['jellyStatURL'] ?? '';
        $height = $this->config['homepageJellyStatIframeHeight'] ?? 800;
        $scrolling = ($this->config['homepageJellyStatIframeScrolling'] ?? true) ? 'auto' : 'no';
        
        return '
        <div id="' . __FUNCTION__ . '">
            <div class="white-box">
                <div class="white-box-header">
                    <i class="fa fa-bar-chart"></i> JellyStat Dashboard
                </div>
                <div class="white-box-content" style="padding: 0;">
                    <iframe 
                        src="' . htmlspecialchars($this->qualifyURL($url)) . '" 
                        width="100%" 
                        height="' . intval($height) . 'px"
                        style="border: none; border-radius: 0 0 4px 4px;"
                        scrolling="' . $scrolling . '"
                        frameborder="0">
                        <p>Your browser does not support iframes. Please visit <a href="' . htmlspecialchars($url) . '" target="_blank">JellyStat</a> directly.</p>
                    </iframe>
                </div>
            </div>
        </div>';
    }

    private function renderJellyStatNative()
    {
        $refreshInterval = ($this->config['homepageJellyStatRefresh'] ?? 5) * 60000; // Convert minutes to milliseconds
        $days = $this->config['homepageJellyStatDays'] ?? 30;
        $maxItems = $this->config['homepageJellyStatMaxItems'] ?? 10;
        $showLibraries = ($this->config['homepageJellyStatShowLibraries'] ?? true) ? 'true' : 'false';
        $showUsers = ($this->config['homepageJellyStatShowUsers'] ?? true) ? 'true' : 'false';
        $showMostWatched = ($this->config['homepageJellyStatShowMostWatched'] ?? true) ? 'true' : 'false';
        $showRecentActivity = ($this->config['homepageJellyStatShowRecentActivity'] ?? true) ? 'true' : 'false';
        $showMostWatchedMovies = ($this->config['homepageJellyStatShowMostWatchedMovies'] ?? true) ? 'true' : 'false';
        $showMostWatchedShows = ($this->config['homepageJellyStatShowMostWatchedShows'] ?? true) ? 'true' : 'false';
        $showMostListenedMusic = ($this->config['homepageJellyStatShowMostListenedMusic'] ?? true) ? 'true' : 'false';
        $mostWatchedCount = $this->config['homepageJellyStatMostWatchedCount'] ?? 10;
        $jellyStatUrl = htmlspecialchars($this->qualifyURL($this->config['jellyStatURL'] ?? ''), ENT_QUOTES, 'UTF-8');

        return '
        <div id="' . __FUNCTION__ . '">
            <div class="white-box">
                <div class="white-box-header">
                    <i class="fa fa-bar-chart"></i> JellyStat Analytics
                    <span class="pull-right">
                        <small id="jellystat-last-update" class="text-muted"></small>
                        <button class="btn btn-xs btn-primary" onclick="refreshJellyStatData()" title="Refresh Data">
                            <i class="fa fa-refresh" id="jellystat-refresh-icon"></i>
                        </button>
                    </span>
                </div>
                <div class="white-box-content">
                    <div class="row" id="jellystat-content">
                        <div class="col-lg-12 text-center">
                            <i class="fa fa-spinner fa-spin"></i> Loading JellyStat data...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        var jellyStatRefreshTimer;
        var jellyStatLastRefresh = 0;

        function refreshJellyStatData() {
            var refreshIcon = $("#jellystat-refresh-icon");
            refreshIcon.addClass("fa-spin");

            // Show loading state
            $("#jellystat-content").html("<div class=\"col-lg-12 text-center\"><i class=\"fa fa-spinner fa-spin\"></i> Loading JellyStat data...</div>");

            // Load JellyStat data
            getJellyStatData()
            .always(function() {
                refreshIcon.removeClass("fa-spin");
                jellyStatLastRefresh = Date.now();
                updateJellyStatLastRefreshTime();
            });
        }

        function updateJellyStatLastRefreshTime() {
            if (jellyStatLastRefresh > 0) {
                var ago = Math.floor((Date.now() - jellyStatLastRefresh) / 1000);
                var timeText = ago < 60 ? ago + "s ago" : Math.floor(ago / 60) + "m ago";
                $("#jellystat-last-update").text("Updated " + timeText);
            }
        }

        // Helper function to get icon for content type
        function getTypeIcon(collectionType) {
            switch(collectionType) {
                case "movies": return "fa-film";
                case "tvshows": return "fa-television";
                case "music": return "fa-music";
                case "mixed": return "fa-folder-open";
                default: return "fa-folder";
            }
        }
        
        // Helper function to format duration from ticks
        function formatJellyStatDuration(ticks) {
            if (!ticks || ticks === 0) return "0 min";
            
            // Convert ticks to seconds (ticks are in 100-nanosecond intervals)
            var seconds = ticks / 10000000;
            
            if (seconds < 60) {
                return Math.round(seconds) + " sec";
            } else if (seconds < 3600) {
                return Math.round(seconds / 60) + " min";
            } else if (seconds < 86400) {
                var hours = Math.floor(seconds / 3600);
                var minutes = Math.floor((seconds % 3600) / 60);
                return hours + "h " + minutes + "m";
            } else {
                var days = Math.floor(seconds / 86400);
                var hours = Math.floor((seconds % 86400) / 3600);
                return days + "d " + hours + "h";
            }
        }
        
        // Helper function to generate poster URLs from JellyStat/Jellyfin
        function getPosterUrl(posterPath, itemId, serverId) {
            console.log("getPosterUrl called with:", {posterPath, itemId, serverId});
            // Use external URL for frontend poster display to avoid mixed content issues
            var jellyStatUrl = ' . json_encode($jellyStatUrl) . ';
            console.log("JellyStat URL from config:", jellyStatUrl);
            
            if (!posterPath && !itemId) {
                console.log("No poster path or item ID provided");
                return null;
            }
            
            // If we have a poster path, process it
            if (posterPath) {
                console.log("Processing poster path:", posterPath);
                // If its already an absolute URL, use it directly
                if (posterPath.indexOf("http://") === 0 || posterPath.indexOf("https://") === 0) {
                    console.log("Poster path is absolute URL:", posterPath);
                    return posterPath;
                }
                // If its a relative path starting with /, prepend the JellyStat URL
                if (jellyStatUrl && posterPath.indexOf("/") === 0) {
                    var fullUrl = jellyStatUrl + posterPath;
                    console.log("Generated full URL from relative path:", fullUrl);
                    return fullUrl;
                }
            }
            
            // If we have itemId, try to generate JellyStat image proxy URL
            if (itemId && jellyStatUrl) {
                // JellyStat uses /proxy/Items/Images/Primary endpoint
                // Format: /proxy/Items/Images/Primary?id={itemId}&fillWidth=200&quality=90
                var baseUrl = jellyStatUrl.replace(/\/+$/, ""); // Remove trailing slashes
                
                var apiUrl = baseUrl + "/proxy/Items/Images/Primary?id=" + itemId + "&fillWidth=200&quality=90";
                console.log("Generated JellyStat proxy image URL:", apiUrl);
                return apiUrl;
            }
            
            console.log("No valid poster URL could be generated");
            return null;
        }

        function getJellyStatData() {
            return organizrAPI2("GET", "api/v2/homepage/jellystat")
            .done(function(data) {
                console.log("JellyStat API Response:", data);
                if (data && data.response && data.response.result === "success" && data.response.data) {
                    console.log("JellyStat Data:", data.response.data);
                    renderJellyStatData(data.response.data);
                } else {
                    console.error("JellyStat API Error:", data);
                    var errorMsg = "Failed to load JellyStat data";
                    if (data && data.response && data.response.message) {
                        errorMsg += ": " + data.response.message;
                    }
                    $("#jellystat-content").html("<div class=\"col-lg-12 text-center text-danger\">" + errorMsg + "</div>");
                }
            })
            .fail(function(xhr, status, error) {
                console.error("JellyStat API Request Failed:", xhr, status, error);
                $("#jellystat-content").html("<div class=\"col-lg-12 text-center text-danger\">Error loading JellyStat data: " + error + "</div>");
            });
        }
        
        function renderJellyStatData(stats) {
            console.log("Rendering JellyStat data:", stats);
            var html = "";
            
            // Server Overview - Summary Stats
            if (stats.library_totals) {
                console.log("Library totals found:", stats.library_totals);
                html += "<div class=\"col-lg-12\" style=\"margin-bottom: 20px;\">";
                html += "<div class=\"row\">";
                
                // Total Libraries
                html += "<div class=\"col-sm-3\">";
                html += "<div class=\"small-box bg-blue\">";
                html += "<div class=\"inner\">";
                html += "<h3>" + (stats.library_totals.total_libraries || 0) + "</h3>";
                html += "<p>Libraries</p>";
                html += "</div>";
                html += "<div class=\"icon\"><i class=\"fa fa-folder\"></i></div>";
                html += "</div></div>";
                
                // Total Items
                html += "<div class=\"col-sm-3\">";
                html += "<div class=\"small-box bg-green\">";
                html += "<div class=\"inner\">";
                html += "<h3>" + (stats.library_totals.total_items || 0).toLocaleString() + "</h3>";
                html += "<p>Total Items</p>";
                html += "</div>";
                html += "<div class=\"icon\"><i class=\"fa fa-film\"></i></div>";
                html += "</div></div>";
                
                // Total Episodes (if any)
                if (stats.library_totals.total_episodes > 0) {
                    html += "<div class=\"col-sm-3\">";
                    html += "<div class=\"small-box bg-yellow\">";
                    html += "<div class=\"inner\">";
                    html += "<h3>" + (stats.library_totals.total_episodes || 0).toLocaleString() + "</h3>";
                    html += "<p>Episodes</p>";
                    html += "</div>";
                    html += "<div class=\"icon\"><i class=\"fa fa-television\"></i></div>";
                    html += "</div></div>";
                }
                
                // Total Play Time
                html += "<div class=\"col-sm-3\">";
                html += "<div class=\"small-box bg-red\">";
                html += "<div class=\"inner\">";
                html += "<h3 style=\"font-size: 18px;\">" + (stats.library_totals.total_play_time || "0 min") + "</h3>";
                html += "<p>Total Watched</p>";
                html += "</div>";
                html += "<div class=\"icon\"><i class=\"fa fa-clock-o\"></i></div>";
                html += "</div></div>";
                
                html += "</div></div>";
            }
            
            // Content Type Breakdown
            if (stats.library_totals && stats.library_totals.type_breakdown) {
                html += "<div class=\"col-lg-6\">";
                html += "<h5><i class=\"fa fa-pie-chart text-primary\"></i> Content Breakdown</h5>";
                html += "<div class=\"table-responsive\">";
                html += "<table class=\"table table-striped table-condensed\">";
                html += "<thead><tr><th>Type</th><th>Libraries</th><th>Items</th><th>Watch Time</th></tr></thead>";
                html += "<tbody>";
                
                Object.keys(stats.library_totals.type_breakdown).forEach(function(type) {
                    var breakdown = stats.library_totals.type_breakdown[type];
                    var playTimeFormatted = breakdown.play_time > 0 ? formatJellyStatDuration(breakdown.play_time) : "0 min";
                    
                    html += "<tr>";
                    html += "<td><strong>" + breakdown.label + "</strong></td>";
                    html += "<td><span class=\"label label-info\">" + breakdown.count + "</span></td>";
                    html += "<td><span class=\"label label-success\">" + breakdown.items.toLocaleString() + "</span></td>";
                    html += "<td><small>" + playTimeFormatted + "</small></td>";
                    html += "</tr>";
                });
                
                html += "</tbody></table></div></div>";
            }
            
            // Detailed Library Statistics
            if (' . $showLibraries . ' && stats.libraries && stats.libraries.length > 0) {
                html += "<div class=\"col-lg-6\">";
                html += "<h5><i class=\"fa fa-folder text-info\"></i> Library Details</h5>";
                html += "<div class=\"table-responsive\">";
                html += "<table class=\"table table-striped table-condensed\">";
                html += "<thead><tr><th>Library</th><th>Type</th><th>Items</th><th>Watch Time</th></tr></thead>";
                html += "<tbody>";
                
                stats.libraries.slice(0, ' . $maxItems . ').forEach(function(lib) {
                    var typeIcon = getTypeIcon(lib.collection_type);
                    html += "<tr>";
                    html += "<td><i class=\"fa " + typeIcon + " text-muted\"></i> <strong>" + (lib.name || "Unknown Library") + "</strong></td>";
                    html += "<td><small>" + (lib.type || "Unknown") + "</small></td>";
                    html += "<td><span class=\"label label-primary\">" + (lib.item_count || 0).toLocaleString() + "</span>";
                    
                    // Show additional counts for TV libraries
                    if (lib.episode_count > 0) {
                        html += "<br><small class=\"text-muted\">Episodes: " + lib.episode_count.toLocaleString() + "</small>";
                    }
                    if (lib.season_count > 0) {
                        html += "<br><small class=\"text-muted\">Seasons: " + lib.season_count.toLocaleString() + "</small>";
                    }
                    
                    html += "</td>";
                    html += "<td><small>" + (lib.total_play_time || "0 min") + "</small></td>";
                    html += "</tr>";
                });
                
                html += "</tbody></table></div></div>";
            }
            
            // User Statistics  
            if (' . $showUsers . ' && stats.users && stats.users.length > 0) {
                html += "<div class=\"col-lg-6\">";
                html += "<h5><i class=\"fa fa-users\"></i> Active Users (" + stats.users.length + " total)</h5>";
                html += "<div class=\"row\">";
                
                stats.users.slice(0, 8).forEach(function(user) {
                    var lastActivity = "Never";
                    if (user.last_activity && user.last_activity !== "0001-01-01T00:00:00.0000000Z") {
                        var activityDate = new Date(user.last_activity);
                        lastActivity = activityDate.toLocaleDateString();
                    }
                    var playCount = user.play_count || 0;
                    
                    html += "<div class=\"col-md-6 col-sm-6\" style=\"margin-bottom: 10px;\">";
                    html += "<div class=\"media\">";
                    html += "<div class=\"media-left\"><i class=\"fa fa-user fa-2x text-muted\"></i></div>";
                    html += "<div class=\"media-body\">";
                    html += "<h6 class=\"media-heading\">" + (user.name || "Unknown User") + " <span class=\"label label-info\">" + playCount + " plays</span></h6>";
                    html += "<small class=\"text-muted\">Last Activity: " + lastActivity + "</small>";
                    html += "</div></div></div>";
                });
                
                html += "</div></div>";
            }
            
            // Most Watched Content
            if (' . $showMostWatched . ' && stats.most_watched && stats.most_watched.length > 0) {
                html += "<div class=\"col-lg-12\" style=\"margin-top: 20px;\">";
                html += "<h5><i class=\"fa fa-star text-warning\"></i> Most Watched Content</h5>";
                html += "<div class=\"table-responsive\">";
                html += "<table class=\"table table-striped table-condensed\">";
                html += "<thead><tr><th>Title</th><th>Type</th><th>Plays</th><th>Runtime</th><th>Year</th></tr></thead>";
                html += "<tbody>";
                
                stats.most_watched.slice(0, ' . $maxItems . ').forEach(function(item) {
                    html += "<tr>";
                    html += "<td><strong>" + (item.title || "Unknown Title") + "</strong></td>";
                    html += "<td>" + (item.type || "Unknown") + "</td>";
                    html += "<td><span class=\"label label-primary\">" + (item.play_count || 0) + "</span></td>";
                    html += "<td>" + (item.runtime || "Unknown") + "</td>";
                    html += "<td>" + (item.year || "N/A") + "</td>";
                    html += "</tr>";
                });
                
                html += "</tbody></table></div></div>";
            }
            
            // Recent Activity
            if (' . $showRecentActivity . ' && stats.recent_activity && stats.recent_activity.length > 0) {
                html += "<div class=\"col-lg-12\" style=\"margin-top: 20px;\">";
                html += "<h5><i class=\"fa fa-clock-o text-success\"></i> Recent Activity</h5>";
                html += "<div class=\"table-responsive\">";
                html += "<table class=\"table table-striped table-condensed\">";
                html += "<thead><tr><th>Date</th><th>User</th><th>Title</th><th>Type</th></tr></thead>";
                html += "<tbody>";
                
                stats.recent_activity.slice(0, ' . $maxItems . ').forEach(function(activity) {
                    var date = new Date(activity.date);
                    var formattedDate = date.toLocaleDateString() + " " + date.toLocaleTimeString([], {hour: "2-digit", minute:"2-digit"});
                    
                    html += "<tr>";
                    html += "<td><small>" + formattedDate + "</small></td>";
                    html += "<td>" + (activity.user || "Unknown User") + "</td>";
                    html += "<td><strong>" + (activity.title || "Unknown Title") + "</strong></td>";
                    html += "<td>" + (activity.type || "Unknown") + "</td>";
                    html += "</tr>";
                });
                
                html += "</tbody></table></div></div>";
            }
            
            // Debug data availability
            console.log("Full stats object:", stats);
            console.log("Movie settings enabled:", ' . $showMostWatchedMovies . ');
            console.log("Movies data:", stats.most_watched_movies);
            console.log("Shows data:", stats.most_watched_shows);
            console.log("Music data:", stats.most_listened_music);
            
            // Most Watched Movies with Posters
            if (' . $showMostWatchedMovies . ' && stats.most_watched_movies && stats.most_watched_movies.length > 0) {
                console.log("Rendering most watched movies:", stats.most_watched_movies);
                html += "<div class=\"col-lg-12\" style=\"margin-top: 30px;\">";
                html += "<h5><i class=\"fa fa-film text-primary\"></i> Most Watched Movies</h5>";
                html += "<div class=\"row\" style=\"margin-top: 15px;\">";
                
                stats.most_watched_movies.forEach(function(movie) {
                    console.log("Processing movie:", movie);
                    console.log("Movie poster_path:", movie.poster_path);
                    console.log("Movie id:", movie.id);
                    console.log("Movie server_id:", movie.server_id);
                    var posterUrl = getPosterUrl(movie.poster_path, movie.id, movie.server_id);
                    console.log("Generated posterUrl:", posterUrl);
                    var playCount = movie.play_count || 0;
                    var year = movie.year || "N/A";
                    var title = movie.title || "Unknown Movie";
                    
                    html += "<div style=\"display: inline-block; margin: 10px; width: 150px;\">";
                    html += "<div class=\"poster-card\" style=\"position: relative; background: #f8f9fa; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 150px; height: 280px;\">";
                    
                    // Poster image container
                    html += "<div class=\"poster-image\" style=\"position: relative; width: 150px; height: 225px; background: #e9ecef;\">";
                    if (posterUrl) {
                        html += "<img src=\"" + posterUrl + "\" alt=\"" + title + "\" style=\"width: 150px; height: 225px; object-fit: cover;\" onerror=\"this.style.display=\"none\"; this.nextElementSibling.style.display=\"flex\";\">";
                    }
                    html += "<div class=\"poster-placeholder\" style=\"position: absolute; top: 0; left: 0; width: 150px; height: 225px; display: " + (posterUrl ? "none" : "flex") + "; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;\">";
                    html += "<i class=\"fa fa-film fa-3x\"></i>";
                    html += "</div>";
                    
                    // Play count badge
                    html += "<div class=\"play-count-badge\" style=\"position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.8); color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px;\">";
                    html += "<i class=\"fa fa-play\"></i> " + playCount;
                    html += "</div>";
                    html += "</div>";
                    
                    // Movie info
                    html += "<div class=\"poster-info\" style=\"padding: 8px; text-align: center; height: 45px;\">";
                    html += "<div style=\"font-size: 12px; font-weight: bold; line-height: 1.2; height: 24px; overflow: hidden;\" title=\"" + title + "\">" + title + "</div>";
                    html += "<small class=\"text-muted\">" + year + "</small>";
                    html += "</div>";
                    
                    html += "</div></div>";
                });
                
                html += "</div></div>";
            } else {
                console.log("Movies not showing because:");
                console.log("- Setting enabled:", ' . $showMostWatchedMovies . ');
                console.log("- Has data:", stats.most_watched_movies && stats.most_watched_movies.length > 0);
                console.log("- Data:", stats.most_watched_movies);
            }
            
            // Most Watched TV Shows with Posters
            if (' . $showMostWatchedShows . ' && stats.most_watched_shows && stats.most_watched_shows.length > 0) {
                html += "<div class=\"col-lg-12\" style=\"margin-top: 30px;\">";
                html += "<h5><i class=\"fa fa-television text-info\"></i> Most Watched TV Shows</h5>";
                html += "<div class=\"row\" style=\"margin-top: 15px;\">";
                
                stats.most_watched_shows.forEach(function(show) {
                    var posterUrl = getPosterUrl(show.poster_path, show.id, show.server_id);
                    var playCount = show.play_count || 0;
                    var year = show.year || "N/A";
                    var title = show.title || "Unknown Show";
                    
                    html += "<div style=\"display: inline-block; margin: 10px; width: 150px;\">";
                    html += "<div class=\"poster-card\" style=\"position: relative; background: #f8f9fa; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 150px; height: 280px;\">";
                    
                    // Poster image container
                    html += "<div class=\"poster-image\" style=\"position: relative; width: 150px; height: 225px; background: #e9ecef;\">";
                    if (posterUrl) {
                        html += "<img src=\"" + posterUrl + "\" alt=\"" + title + "\" style=\"width: 150px; height: 225px; object-fit: cover;\" onerror=\"this.style.display=\"none\"; this.nextElementSibling.style.display=\"flex\";\">";
                    }
                    html += "<div class=\"poster-placeholder\" style=\"position: absolute; top: 0; left: 0; width: 150px; height: 225px; display: " + (posterUrl ? "none" : "flex") + "; align-items: center; justify-content: center; background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); color: white;\">";
                    html += "<i class=\"fa fa-television fa-3x\"></i>";
                    html += "</div>";
                    
                    // Play count badge
                    html += "<div class=\"play-count-badge\" style=\"position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.8); color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px;\">";
                    html += "<i class=\"fa fa-play\"></i> " + playCount;
                    html += "</div>";
                    html += "</div>";
                    
                    // Show info
                    html += "<div class=\"poster-info\" style=\"padding: 8px; text-align: center; height: 45px;\">";
                    html += "<div style=\"font-size: 12px; font-weight: bold; line-height: 1.2; height: 24px; overflow: hidden;\" title=\"" + title + "\">" + title + "</div>";
                    html += "<small class=\"text-muted\">" + year + "</small>";
                    html += "</div>";
                    
                    html += "</div></div>";
                });
                
                html += "</div></div>";
            }
            
            // Most Listened Music with Cover Art
            if (' . $showMostListenedMusic . ' && stats.most_listened_music && stats.most_listened_music.length > 0) {
                html += "<div class=\"col-lg-12\" style=\"margin-top: 30px;\">";
                html += "<h5><i class=\"fa fa-music text-success\"></i> Most Listened Music</h5>";
                html += "<div class=\"row\" style=\"margin-top: 15px;\">";
                
                stats.most_listened_music.forEach(function(music) {
                    var posterUrl = getPosterUrl(music.poster_path || music.cover_art, music.id, music.server_id);
                    var playCount = music.play_count || 0;
                    var artist = music.artist || "Unknown Artist";
                    var title = music.title || music.album || "Unknown";
                    
                    html += "<div class=\"col-lg-2 col-md-3 col-sm-4 col-xs-6\" style=\"margin-bottom: 20px;\">";
                    html += "<div class=\"poster-card\" style=\"position: relative; background: #f8f9fa; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.2s;\">";
                    
                    // Cover art
                    html += "<div class=\"poster-image\" style=\"position: relative; padding-top: 100%; background: #e9ecef;\">";
                    if (posterUrl) {
                        html += "<img src=\"" + posterUrl + "\" alt=\"" + title + "\" style=\"position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;\" onerror=\"this.style.display=\"none\"; this.nextElementSibling.style.display=\"flex\";\">";
                    }
                    html += "<div class=\"poster-placeholder\" style=\"position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: " + (posterUrl ? "none" : "flex") + "; align-items: center; justify-content: center; background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%); color: white;\">";
                    html += "<i class=\"fa fa-music fa-3x\"></i>";
                    html += "</div>";
                    
                    // Play count badge
                    html += "<div class=\"play-count-badge\" style=\"position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.8); color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px;\">";
                    html += "<i class=\"fa fa-play\"></i> " + playCount;
                    html += "</div>";
                    html += "</div>";
                    
                    // Music info
                    html += "<div class=\"poster-info\" style=\"padding: 12px; text-align: center;\">";
                    html += "<h6 style=\"margin: 0 0 4px 0; font-size: 13px; font-weight: bold; line-height: 1.2; height: 32px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;\" title=\"" + title + "\">" + title + "</h6>";
                    html += "<small class=\"text-muted\">" + artist + "</small>";
                    html += "</div>";
                    
                    html += "</div></div>";
                });
                
                html += "</div></div>";
            }
            
            if (!html) {
                html = "<div class=\"col-lg-12 text-center text-muted\">";
                html += "<i class=\"fa fa-exclamation-circle fa-3x\" style=\"margin-bottom: 10px;\"></i>";
                html += "<h4>No JellyStat data available</h4>";
                html += "<p>Check your JellyStat connection and API configuration.</p>";
                html += "</div>";
            }
            
            $("#jellystat-content").html(html);
        }

        // Auto-refresh setup
        var refreshInterval = ' . $refreshInterval . ';
        if (refreshInterval > 0) {
            jellyStatRefreshTimer = setInterval(function() {
                refreshJellyStatData();
            }, refreshInterval);
        }

        // Update time display every 30 seconds
        setInterval(updateJellyStatLastRefreshTime, 30000);

        // Initial load
        $(document).ready(function() {
            refreshJellyStatData();
        });

        // Cleanup timer when page unloads
        $(window).on("beforeunload", function() {
            if (jellyStatRefreshTimer) {
                clearInterval(jellyStatRefreshTimer);
            }
        });
        
        </script>
        ';
    }

    /**
     * Main function to get JellyStat data
     */
    public function getJellyStatData($options = null)
    {
        if (!$this->homepageItemPermissions($this->jellystatHomepagePermissions('main'), true)) {
            $this->setAPIResponse('error', 'User not approved to view this homepage item - check plugin configuration', 401);
            return false;
        }

        try {
            $url = $this->config['jellyStatURL'] ?? '';
            $token = $this->config['jellyStatApikey'] ?? '';
            $days = intval($this->config['homepageJellyStatDays'] ?? 30);
            
            if (empty($url) || empty($token)) {
                $this->setAPIResponse('error', 'JellyStat URL or API key not configured', 500);
                return false;
            }
            
            $stats = $this->fetchJellyStatStats($url, $token, $days);
            
            if (isset($stats['error']) && $stats['error']) {
                $this->setAPIResponse('error', $stats['message'], 500);
                return false;
            }
            
            $this->setAPIResponse('success', 'JellyStat data retrieved successfully', 200, $stats);
            return true;
            
        } catch (Exception $e) {
            $this->setAPIResponse('error', 'Failed to retrieve JellyStat data: ' . $e->getMessage(), 500);
            return false;
        }
    }

    /**
     * Fetch statistics from JellyStat API
     */
    private function fetchJellyStatStats($url, $token, $days = 30)
    {
        $disableCert = $this->config['jellyStatDisableCertCheck'] ?? false;
        $customCert = $this->config['jellyStatUseCustomCertificate'] ?? false;
        
        // Use internal URL for server-side API calls if configured, otherwise use main URL
        $internalUrl = $this->config['jellyStatInternalURL'] ?? '';
        $apiUrl = !empty($internalUrl) ? $internalUrl : $url;
        
        $options = $this->requestOptions($apiUrl, null, $disableCert, $customCert);
        $baseUrl = $this->qualifyURL($apiUrl);
        
        $stats = [
            'period' => "{$days} days",
            'libraries' => [],
            'library_totals' => [],
            'server_info' => [],
            'most_watched_movies' => [],
            'most_watched_shows' => [],
            'most_listened_music' => []
        ];
        
        $mostWatchedCount = $this->config['homepageJellyStatMostWatchedCount'] ?? 10;
        
        try {
            // Get Library Statistics - use query parameter authentication
            $librariesUrl = $baseUrl . '/api/getLibraries?apiKey=' . urlencode($token);
            $response = Requests::get($librariesUrl, [], $options);
            if ($response->success) {
                $data = json_decode($response->body, true);
                if (is_array($data) && !isset($data['error'])) {
                    // Process individual libraries
                    $stats['libraries'] = array_map(function($lib) {
                        return [
                            'name' => $lib['Name'] ?? 'Unknown Library',
                            'type' => $this->getCollectionTypeLabel($lib['CollectionType'] ?? 'unknown'),
                            'item_count' => $lib['item_count'] ?? 0,
                            'season_count' => $lib['season_count'] ?? 0,
                            'episode_count' => $lib['episode_count'] ?? 0,
                            'total_play_time' => $lib['total_play_time'] ? $this->formatJellyStatDuration($lib['total_play_time']) : '0 min',
                            'play_time_raw' => $lib['total_play_time'] ?? 0,
                            'collection_type' => $lib['CollectionType'] ?? 'unknown'
                        ];
                    }, $data);
                    
                    // Calculate totals across all libraries
                    $totalItems = array_sum(array_column($data, 'item_count'));
                    $totalSeasons = array_sum(array_column($data, 'season_count'));
                    $totalEpisodes = array_sum(array_column($data, 'episode_count'));
                    $totalPlayTime = array_sum(array_column($data, 'total_play_time'));
                    
                    // Calculate library type breakdowns
                    $typeBreakdown = [];
                    foreach ($data as $lib) {
                        $type = $lib['CollectionType'] ?? 'unknown';
                        if (!isset($typeBreakdown[$type])) {
                            $typeBreakdown[$type] = [
                                'count' => 0,
                                'items' => 0,
                                'play_time' => 0,
                                'label' => $this->getCollectionTypeLabel($type)
                            ];
                        }
                        $typeBreakdown[$type]['count']++;
                        $typeBreakdown[$type]['items'] += $lib['item_count'] ?? 0;
                        $typeBreakdown[$type]['play_time'] += $lib['total_play_time'] ?? 0;
                    }
                    
                    $stats['library_totals'] = [
                        'total_libraries' => count($data),
                        'total_items' => $totalItems,
                        'total_seasons' => $totalSeasons,
                        'total_episodes' => $totalEpisodes,
                        'total_play_time' => $this->formatJellyStatDuration($totalPlayTime),
                        'total_play_time_raw' => $totalPlayTime,
                        'type_breakdown' => $typeBreakdown
                    ];
                    
                    // Server information
                    $stats['server_info'] = [
                        'server_id' => $data[0]['ServerId'] ?? 'Unknown',
                        'last_updated' => date('c')
                    ];
                }
            }
            
            // Get History data and process to extract most watched content
            $historyUrl = $baseUrl . '/api/getHistory?apiKey=' . urlencode($token) . '&size=500';
            $response = Requests::get($historyUrl, [], $options);
            if ($response->success) {
                $historyData = json_decode($response->body, true);
                if (is_array($historyData) && isset($historyData['results']) && is_array($historyData['results'])) {
                    // Process history to get most watched content
                    $processedData = $this->processJellyStatHistory($historyData['results']);
                    
                    // Extract most watched items based on user settings
                    if ($this->config['homepageJellyStatShowMostWatchedMovies'] ?? false) {
                        $stats['most_watched_movies'] = array_slice($processedData['movies'], 0, $mostWatchedCount);
                    }
                    
                    if ($this->config['homepageJellyStatShowMostWatchedShows'] ?? false) {
                        $stats['most_watched_shows'] = array_slice($processedData['shows'], 0, $mostWatchedCount);
                    }
                    
                    if ($this->config['homepageJellyStatShowMostListenedMusic'] ?? false) {
                        $stats['most_listened_music'] = array_slice($processedData['music'], 0, $mostWatchedCount);
                    }
                }
            }
            
        } catch (Exception $e) {
            return ['error' => true, 'message' => 'Failed to fetch JellyStat data: ' . $e->getMessage()];
        }
        
        return $stats;
    }
    
    /**
     * Get human-readable label for collection type
     */
    private function getCollectionTypeLabel($type)
    {
        $labels = [
            'movies' => 'Movies',
            'tvshows' => 'TV Shows',
            'music' => 'Music',
            'mixed' => 'Mixed Content',
            'unknown' => 'Other'
        ];
        
        return $labels[$type] ?? ucfirst($type);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($size, $precision = 2)
    {
        if ($size == 0) return '0 B';
        
        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }

    /**
     * Format duration for display (JellyStat specific)
     */
    private function formatJellyStatDuration($ticks)
    {
        if ($ticks == 0) return 'Unknown';
        
        // Convert ticks to seconds (ticks are in 100-nanosecond intervals)
        $seconds = $ticks / 10000000;
        
        if ($seconds < 3600) {
            return gmdate('i:s', $seconds);
        } else {
            return gmdate('H:i:s', $seconds);
        }
    }
    
    /**
     * Process JellyStat history data to extract most watched content
     */
    private function processJellyStatHistory($historyResults)
    {
        $processed = [
            'movies' => [],
            'shows' => [],
            'music' => []
        ];
        
        // Group items by ID and count plays
        $itemStats = [];
        
        foreach ($historyResults as $result) {
            // Determine content type based on available data
            $contentType = 'unknown';
            $itemId = null;
            $title = 'Unknown';
            $year = null;
            $serverId = $result['ServerId'] ?? null;
            
            // Check if it's a TV show (has SeriesName)
            if (!empty($result['SeriesName'])) {
                $contentType = 'show';
                $itemId = $result['SeriesName']; // Use series name as unique identifier
                $title = $result['SeriesName'];
                // Try to extract year from episode or series data
                if (!empty($result['EpisodeName'])) {
                    // This is an episode, count toward the series
                    $episodeTitle = $result['EpisodeName'];
                    if (preg_match('/\b(19|20)\d{2}\b/', $episodeTitle, $matches)) {
                        $year = $matches[0];
                    }
                }
            }
            // Check if it's a movie (has NowPlayingItemName but no SeriesName)
            elseif (!empty($result['NowPlayingItemName']) && empty($result['SeriesName'])) {
                // Determine if it's likely a movie or music based on duration or other hints
                $itemName = $result['NowPlayingItemName'];
                $duration = $result['PlaybackDuration'] ?? 0;
                
                // If duration is very short (< 10 minutes) and no video streams, likely music
                $hasVideo = false;
                if (isset($result['MediaStreams']) && is_array($result['MediaStreams'])) {
                    foreach ($result['MediaStreams'] as $stream) {
                        if (($stream['Type'] ?? '') === 'Video') {
                            $hasVideo = true;
                            break;
                        }
                    }
                }
                
                if (!$hasVideo || $duration < 600) { // Less than 10 minutes and no video = likely music
                    $contentType = 'music';
                    $title = $itemName;
                    // For music, try to extract artist info
                    // Music tracks might have format like "Artist - Song" or just "Song"
                } else {
                    $contentType = 'movie';
                    $title = $itemName;
                    // Try to extract year from movie title
                    if (preg_match('/\((19|20)\d{2}\)/', $title, $matches)) {
                        $year = $matches[1] . $matches[2];
                        $title = trim(str_replace($matches[0], '', $title));
                    }
                }
                
                $itemId = $result['NowPlayingItemId'] ?? $itemName;
            }
            
            if ($itemId && $contentType !== 'unknown') {
                $key = $contentType . '_' . $itemId;
                
                if (!isset($itemStats[$key])) {
                    // Extract poster/image information from JellyStat API response
                    $posterPath = null;
                    $actualItemId = null;
                    
                    // Get the actual Jellyfin/Emby item ID for poster generation
                    // Note: JellyStat history API doesn't provide poster paths directly,
                    // so we'll use item IDs with JellyStat's image proxy API
                    if ($contentType === 'movie') {
                        // For movies, use the NowPlayingItemId
                        $actualItemId = $result['NowPlayingItemId'] ?? null;
                    } elseif ($contentType === 'show') {
                        // Debug: Log all available IDs for TV shows to understand data structure
                        $this->setLoggerChannel('JellyStat')->info("JellyStat TV Show Debug - Series: {$result['SeriesName']}");
                        $this->setLoggerChannel('JellyStat')->info("Available IDs: SeriesId=" . ($result['SeriesId'] ?? 'null') . 
                                 ", ShowId=" . ($result['ShowId'] ?? 'null') . 
                                 ", ParentId=" . ($result['ParentId'] ?? 'null') . 
                                 ", NowPlayingItemId=" . ($result['NowPlayingItemId'] ?? 'null'));
                        
                        // For TV shows, be more selective about ID selection to ensure we get series posters
                        // Priority: SeriesId (if exists) > ShowId > NowPlayingItemId (only if it looks like series) > ParentId
                        $actualItemId = null;
                        
                        if (!empty($result['SeriesId'])) {
                            // SeriesId is the most reliable for series posters
                            $actualItemId = $result['SeriesId'];
                            $this->setLoggerChannel('JellyStat')->info("Using SeriesId: {$actualItemId}");
                        } elseif (!empty($result['ShowId'])) {
                            // ShowId is also series-specific
                            $actualItemId = $result['ShowId'];
                            $this->setLoggerChannel('JellyStat')->info("Using ShowId: {$actualItemId}");
                        } elseif (!empty($result['NowPlayingItemId'])) {
                            // Try NowPlayingItemId - it might be the series ID if we're looking at series-level data
                            $actualItemId = $result['NowPlayingItemId'];
                            $this->setLoggerChannel('JellyStat')->info("Using NowPlayingItemId: {$actualItemId}");
                        } elseif (!empty($result['ParentId'])) {
                            // Last resort: ParentId (might be series, season, or library)
                            $actualItemId = $result['ParentId'];
                            $this->setLoggerChannel('JellyStat')->info("Using ParentId: {$actualItemId}");
                        }
                        
                        if (!$actualItemId) {
                            $this->setLoggerChannel('JellyStat')->info("No suitable ID found for TV show: {$result['SeriesName']}");
                        }
                    } elseif ($contentType === 'music') {
                        // For music, use NowPlayingItemId (album/track)
                        $actualItemId = $result['NowPlayingItemId'] ?? null;
                    }
                    
                    $itemStats[$key] = [
                        'id' => $actualItemId ?? $itemId,  // Use actual item ID if available, fallback to name-based ID
                        'title' => $title,
                        'type' => $contentType,
                        'play_count' => 0,
                        'total_duration' => 0,
                        'year' => $year,
                        'server_id' => $serverId,
                        'poster_path' => $posterPath,
                        'first_played' => $result['ActivityDateInserted'] ?? null,
                        'last_played' => $result['ActivityDateInserted'] ?? null
                    ];
                }
                
                $itemStats[$key]['play_count']++;
                $itemStats[$key]['total_duration'] += $result['PlaybackDuration'] ?? 0;
                
                // Debug: Log each play count increment
                if ($contentType === 'show') {
                    $this->setLoggerChannel('JellyStat')->info("Play count increment for {$title}: now {$itemStats[$key]['play_count']} (Episode: {$result['EpisodeName']}, User: {$result['UserName']}, Date: {$result['ActivityDateInserted']})");
                }
                
                // Update last played time
                $currentTime = $result['ActivityDateInserted'] ?? null;
                if ($currentTime && (!$itemStats[$key]['last_played'] || $currentTime > $itemStats[$key]['last_played'])) {
                    $itemStats[$key]['last_played'] = $currentTime;
                }
                
                // Update first played time  
                if ($currentTime && (!$itemStats[$key]['first_played'] || $currentTime < $itemStats[$key]['first_played'])) {
                    $itemStats[$key]['first_played'] = $currentTime;
                }
            }
        }
        
        // Separate by content type and sort by play count
        foreach ($itemStats as $item) {
            switch ($item['type']) {
                case 'movie':
                    $processed['movies'][] = $item;
                    break;
                case 'show':
                    $processed['shows'][] = $item;
                    break;
                case 'music':
                    $processed['music'][] = $item;
                    break;
            }
        }
        
        // Sort each category by play count (descending)
        usort($processed['movies'], function($a, $b) {
            return $b['play_count'] - $a['play_count'];
        });
        
        usort($processed['shows'], function($a, $b) {
            return $b['play_count'] - $a['play_count'];
        });
        
        usort($processed['music'], function($a, $b) {
            return $b['play_count'] - $a['play_count'];
        });
        
        return $processed;
    }
    
    /**
     * Fetch most viewed content by type using JellyStat's native API endpoint
     * This matches exactly what the JellyStat web UI uses
     */
    private function fetchJellyStatMostViewedByType($baseUrl, $token, $options, $type, $days, $limit)
    {
        try {
            // Log the API call for debugging
            $this->setLoggerChannel('JellyStat')->info("fetchJellyStatMostViewedByType called with type: {$type}, days: {$days}");
            
            // Use query parameter authentication like the other working JellyStat API calls
            $apiUrl = $baseUrl . '/stats/getMostViewedByType?apiKey=' . urlencode($token) . '&days=' . intval($days) . '&type=' . urlencode($type);
            
            $this->setLoggerChannel('JellyStat')->info("Making GET request to: {$apiUrl}");
            
            // Make GET request to JellyStat API using query parameters (same as other working endpoints)
            $response = Requests::get($apiUrl, [], $options);
            
            $this->setLoggerChannel('JellyStat')->info("Response status: {$response->status_code}");
            
            if ($response->success) {
                $data = json_decode($response->body, true);
                
                if (is_array($data)) {
                    $this->setLoggerChannel('JellyStat')->info("Received " . count($data) . " items from JellyStat API for type: {$type}");
                    
                    // Process the response data to match our expected format
                    $processedItems = [];
                    
                    foreach ($data as $item) {
                        $processedItem = [
                            'id' => $item['Id'] ?? null,
                            'title' => $item['Name'] ?? 'Unknown',
                            'play_count' => $item['Plays'] ?? 0,
                            'total_duration' => $item['total_playback_duration'] ?? 0,
                            'type' => strtolower($type), // Normalize type
                            'server_id' => null, // JellyStat doesn't return server ID in this endpoint
                            'poster_path' => null, // Will be generated using item ID
                            'year' => null, // Not provided by this endpoint
                            'archived' => $item['archived'] ?? false
                        ];
                        
                        // Only include non-archived items
                        if (!$processedItem['archived']) {
                            $processedItems[] = $processedItem;
                        }
                        
                        // Limit results
                        if (count($processedItems) >= $limit) {
                            break;
                        }
                    }
                    
                    $this->setLoggerChannel('JellyStat')->info("Processed " . count($processedItems) . " items for type: {$type}");
                    return $processedItems;
                } else {
                    $this->setLoggerChannel('JellyStat')->error('JellyStat getMostViewedByType returned invalid data format for type: ' . $type . '. Response: ' . substr($response->body, 0, 500));
                    return [];
                }
            } else {
                $this->setLoggerChannel('JellyStat')->error('JellyStat getMostViewedByType API request failed for type: ' . $type . ' - HTTP ' . $response->status_code . '. Response: ' . substr($response->body, 0, 500));
                return [];
            }
            
        } catch (Exception $e) {
            $this->setLoggerChannel('JellyStat')->error('JellyStat getMostViewedByType exception for type: ' . $type . ' - ' . $e->getMessage());
            return [];
        }
    }
}
