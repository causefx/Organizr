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
            // For iframe mode, just test if the URL is reachable
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
                $options = $this->requestOptions($url, null, $disableCert, $customCert);
                
                // Test JellyStat API - try to get server info or stats
                $testUrl = $this->qualifyURL($url) . '/api/getLibraries';
                $headers = ['Authorization' => 'Bearer ' . $token];
                
                $response = Requests::get($testUrl, $headers, $options);
                if ($response->success) {
                    $data = json_decode($response->body, true);
                    if (isset($data) && is_array($data)) {
                        $this->setAPIResponse('success', 'Successfully connected to JellyStat API', 200);
                        return true;
                    }
                    // Log the actual response for debugging
                    $this->writeLog('error', 'JellyStat API test: Valid HTTP response but invalid data format. Response: ' . substr($response->body, 0, 500));
                }
                
                // Log first endpoint failure details
                $firstError = "HTTP {$response->status_code}: " . substr($response->body, 0, 200);
                $this->writeLog('error', "JellyStat API test failed on /api/getLibraries: {$firstError}");
                
                // Fallback test - try different API endpoint structure
                $testUrl = $this->qualifyURL($url) . '/api/v1/stats';
                $response = Requests::get($testUrl, $headers, $options);
                if ($response->success) {
                    $this->setAPIResponse('success', 'Successfully connected to JellyStat API', 200);
                    return true;
                }
                
                // Log second endpoint failure details
                $secondError = "HTTP {$response->status_code}: " . substr($response->body, 0, 200);
                $this->writeLog('error', "JellyStat API test failed on /api/v1/stats: {$secondError}");
                
                // Try basic connection test to see if JellyStat is even running
                $response = Requests::get($this->qualifyURL($url), [], $options);
                if ($response->success) {
                    $this->setAPIResponse('error', 'JellyStat is reachable but API endpoints are not responding correctly. Check API key and JellyStat version.', 500);
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

        function getJellyStatData() {
            return organizrAPI2("GET", "api/v2/homepage/jellystat")
            .done(function(data) {
                if (data && data.response && data.response.result === "success" && data.response.data) {
                    renderJellyStatData(data.response.data);
                } else {
                    $("#jellystat-content").html("<div class=\"col-lg-12 text-center text-danger\">Failed to load JellyStat data</div>");
                }
            })
            .fail(function(xhr, status, error) {
                $("#jellystat-content").html("<div class=\"col-lg-12 text-center text-danger\">Error loading JellyStat data</div>");
            });
        }
        
        function renderJellyStatData(stats) {
            var html = "";
            
            // Library Statistics
            if (' . $showLibraries . ' && stats.libraries && stats.libraries.length > 0) {
                html += "<div class=\"col-lg-6\">";
                html += "<h5><i class=\"fa fa-folder text-info\"></i> Library Statistics</h5>";
                html += "<div class=\"table-responsive\">";
                html += "<table class=\"table table-striped table-condensed\">";
                html += "<thead><tr><th>Library</th><th>Items</th><th>Size</th></tr></thead>";
                html += "<tbody>";
                
                stats.libraries.slice(0, ' . $maxItems . ').forEach(function(lib) {
                    html += "<tr>";
                    html += "<td><strong>" + (lib.name || "Unknown Library") + "</strong></td>";
                    html += "<td><span class=\"label label-primary\">" + (lib.item_count || 0) + "</span></td>";
                    html += "<td>" + (lib.size || "Unknown") + "</td>";
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
        $options = $this->requestOptions($url, null, $disableCert, $customCert);
        $baseUrl = $this->qualifyURL($url);
        $headers = ['Authorization' => 'Bearer ' . $token];
        
        $stats = [
            'period' => "{$days} days",
            'libraries' => [],
            'users' => [],
            'most_watched' => [],
            'recent_activity' => []
        ];
        
        try {
            // Get Library Statistics
            $librariesUrl = $baseUrl . '/api/getLibraries';
            $response = Requests::get($librariesUrl, $headers, $options);
            if ($response->success) {
                $data = json_decode($response->body, true);
                if (is_array($data)) {
                    $stats['libraries'] = array_map(function($lib) {
                        return [
                            'name' => $lib['Name'] ?? $lib['name'] ?? 'Unknown Library',
                            'item_count' => $lib['ItemCount'] ?? $lib['item_count'] ?? 0,
                            'size' => $this->formatBytes($lib['Size'] ?? $lib['size'] ?? 0)
                        ];
                    }, $data);
                }
            }
            
            // Get User Statistics
            $usersUrl = $baseUrl . '/api/getUserStats';
            $response = Requests::get($usersUrl, $headers, $options);
            if ($response->success) {
                $data = json_decode($response->body, true);
                if (is_array($data)) {
                    $stats['users'] = array_map(function($user) {
                        return [
                            'name' => $user['UserName'] ?? $user['name'] ?? 'Unknown User',
                            'play_count' => $user['PlayCount'] ?? $user['play_count'] ?? 0,
                            'last_activity' => $user['LastActivityDate'] ?? $user['last_activity'] ?? null
                        ];
                    }, $data);
                }
            }
            
            // Get Most Watched Content
            $mostWatchedUrl = $baseUrl . '/api/getMostWatched?days=' . $days;
            $response = Requests::get($mostWatchedUrl, $headers, $options);
            if ($response->success) {
                $data = json_decode($response->body, true);
                if (is_array($data)) {
                    $stats['most_watched'] = array_map(function($item) {
                        return [
                            'title' => $item['Name'] ?? $item['title'] ?? 'Unknown Title',
                            'type' => $item['Type'] ?? $item['type'] ?? 'Unknown',
                            'play_count' => $item['PlayCount'] ?? $item['play_count'] ?? 0,
                            'runtime' => $this->formatDuration($item['RunTimeTicks'] ?? $item['runtime'] ?? 0),
                            'year' => $item['ProductionYear'] ?? $item['year'] ?? null
                        ];
                    }, $data);
                }
            }
            
            // Get Recent Activity
            $recentActivityUrl = $baseUrl . '/api/getRecentActivity?days=' . $days;
            $response = Requests::get($recentActivityUrl, $headers, $options);
            if ($response->success) {
                $data = json_decode($response->body, true);
                if (is_array($data)) {
                    $stats['recent_activity'] = array_map(function($activity) {
                        return [
                            'date' => $activity['Date'] ?? $activity['date'] ?? date('c'),
                            'user' => $activity['UserName'] ?? $activity['user'] ?? 'Unknown User',
                            'title' => $activity['ItemName'] ?? $activity['title'] ?? 'Unknown Title',
                            'type' => $activity['ItemType'] ?? $activity['type'] ?? 'Unknown'
                        ];
                    }, $data);
                }
            }
            
        } catch (Exception $e) {
            return ['error' => true, 'message' => 'Failed to fetch JellyStat data: ' . $e->getMessage()];
        }
        
        return $stats;
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
     * Format duration for display
     */
    private function formatDuration($ticks)
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
}
