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
                $("#watchstats-content").html("<div class=\"col-lg-12 text-center\"><i class=\"fa fa-spinner fa-spin\"></i> Loading statistics...</div>");

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

            function getUserWatchStatsData() {
                return organizrAPI2("GET", "api/v2/homepage/userWatchStats")
                .done(function(data) {
                    if (data && data.response && data.response.result === "success" && data.response.data) {
                        renderWatchStatsData(data.response.data);
                    } else {
                        $("#watchstats-content").html("<div class=\"col-lg-12 text-center text-danger\">Failed to load statistics</div>");
                    }
                })
                .fail(function(xhr, status, error) {
                    $("#watchstats-content").html("<div class=\"col-lg-12 text-center text-danger\">Error loading statistics</div>");
                });
            }
            
            function renderWatchStatsData(stats) {
                var html = "";
                
                // Most Watched Content
                if (stats.most_watched && stats.most_watched.length > 0) {
                    html += "<div class=\"col-lg-12\">";
                    html += "<h5><i class=\"fa fa-star text-warning\"></i> Most Watched Content</h5>";
                    html += "<div class=\"table-responsive\">";
                    html += "<table class=\"table table-striped table-condensed\">";
                    html += "<thead><tr><th>Title</th><th>Type</th><th>Plays</th><th>Runtime</th><th>Year</th></tr></thead>";
                    html += "<tbody>";
                    
                    stats.most_watched.slice(0, 10).forEach(function(item) {
                        html += "<tr>";
                        html += "<td><strong>" + (item.title || "Unknown Title") + "</strong></td>";
                        html += "<td>" + (item.type || "Unknown") + "</td>";
                        html += "<td><span class=\"label label-primary\">" + (item.total_plays || 0) + "</span></td>";
                        html += "<td>" + (item.runtime || "Unknown") + "</td>";
                        html += "<td>" + (item.year || "N/A") + "</td>";
                        html += "</tr>";
                    });
                    
                    html += "</tbody></table></div></div>";
                }
                
                // User Statistics  
                if (stats.user_stats && stats.user_stats.length > 0) {
                    html += "<div class=\"col-lg-12\" style=\"margin-top: 20px;\">";
                    html += "<h5><i class=\"fa fa-users\"></i> Server Users (" + stats.user_stats.length + " total)</h5>";
                    html += "<div class=\"row\">";
                    
                    stats.user_stats.slice(0, 12).forEach(function(user) {
                        var lastActivity = "Never";
                        if (user.LastActivityDate && user.LastActivityDate !== "0001-01-01T00:00:00.0000000Z") {
                            var activityDate = new Date(user.LastActivityDate);
                            lastActivity = activityDate.toLocaleDateString();
                        }
                        var isAdmin = user.Policy && user.Policy.IsAdministrator;
                        var isDisabled = user.Policy && user.Policy.IsDisabled;
                        var badgeClass = isAdmin ? "label-success" : (isDisabled ? "label-danger" : "label-info");
                        var badgeText = isAdmin ? "Admin" : (isDisabled ? "Disabled" : "User");
                        
                        html += "<div class=\"col-md-4 col-sm-6\" style=\"margin-bottom: 10px;\">";
                        html += "<div class=\"media\">";
                        html += "<div class=\"media-left\"><i class=\"fa fa-user fa-2x text-muted\"></i></div>";
                        html += "<div class=\"media-body\">";
                        html += "<h6 class=\"media-heading\">" + (user.Name || "Unknown User") + " <span class=\"label " + badgeClass + "\">" + badgeText + "</span></h6>";
                        html += "<small class=\"text-muted\">Last Activity: " + lastActivity + "</small>";
                        html += "</div></div></div>";
                    });
                    
                    html += "</div></div>";
                }
                
                if (!html) {
                    html = "<div class=\"col-lg-12 text-center text-muted\">";
                    html += "<i class=\"fa fa-exclamation-circle fa-3x\" style=\"margin-bottom: 10px;\"></i>";
                    html += "<h4>No statistics available</h4>";
                    html += "<p>Start watching some content to see statistics here!</p>";
                    html += "</div>";
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
            $this->setAPIResponse('error', 'Failed to retrieve watch statistics: ' . $e->getMessage(), 500);
            return false;
        }
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
            'user_stats' => $this->getEmbyUserStats($embyUrl, $embyToken, $days),
            'recent_activity' => $this->getEmbyRecentActivity($embyUrl, $embyToken),
        ];

        return $stats;
    }

    /**
     * Get most watched content from Emby
     */
    private function getEmbyMostWatched($url, $token, $days)
    {
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
            
            // Aggregate play counts - use actual PlayCount if available, otherwise count as 1 per user
            foreach ($userWatchedItems as $item) {
                $itemId = $item['Id'] ?? $item['title']; // Use ID if available, title as fallback
                $userPlayCount = $item['play_count'] ?? 0; // Get actual play count from this user
                
                if ($userPlayCount > 0) {
                    // Add this user's play count to the total for this item
                    $playCountsByItem[$itemId] = ($playCountsByItem[$itemId] ?? 0) + $userPlayCount;
                    
                    // Store item details (only need to do this once per item)
                    if (!isset($itemDetails[$itemId])) {
                        $itemDetails[$itemId] = [
                            'title' => $item['title'] ?? 'Unknown Title',
                            'runtime' => $item['runtime'] ?? 'Unknown',
                            'type' => $item['type'] ?? 'Unknown',
                            'year' => $item['year'] ?? null
                        ];
                    }
                } elseif ($item['is_played'] ?? false) {
                    // Fallback: if no play count but marked as played, count as 1 for this user
                    $playCountsByItem[$itemId] = ($playCountsByItem[$itemId] ?? 0) + 1;
                    
                    // Store item details
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
     * Get watched content for a specific user
     */
    private function getEmbyUserWatchedContent($url, $token, $userId, $days)
    {
        // Try to get all items for this user, including play counts
        $apiURL = rtrim($url, '/') . '/emby/Users/' . $userId . '/Items?api_key=' . $token . 
                  '&Recursive=true&IncludeItemTypes=Movie,Episode&Limit=200' .
                  '&Fields=Name,PlayCount,UserData,RunTimeTicks,ProductionYear&SortBy=PlayCount&SortOrder=Descending';
        
        try {
            $options = $this->requestOptions($url, null, $this->config['userWatchStatsDisableCertCheck'] ?? false, $this->config['userWatchStatsUseCustomCertificate'] ?? false);
            $response = Requests::get($apiURL, [], $options);
            
            if ($response->success) {
                $data = json_decode($response->body, true);
                $items = $data['Items'] ?? [];

                $watchedContent = [];
                foreach ($items as $item) {
                    $playCount = $item['UserData']['PlayCount'] ?? 0;
                    $isPlayed = $item['UserData']['Played'] ?? false;
                    
                    // Include items that have been played OR have a play count > 0
                    if ($playCount > 0 || $isPlayed) {
                        $watchedContent[] = [
                            'Id' => $item['Id'] ?? null,
                            'title' => $item['Name'] ?? 'Unknown Title',
                            'play_count' => $playCount, // Use actual play count from UserData
                            'is_played' => $isPlayed,
                            'runtime' => $item['RunTimeTicks'] ? $this->formatDuration($item['RunTimeTicks'] / 10000000) : 'Unknown',
                            'type' => $item['Type'] ?? 'Unknown',
                            'year' => $item['ProductionYear'] ?? null
                        ];
                    }
                }
                return $watchedContent;
            }
        } catch (Requests_Exception $e) {
            // Nothing we can do
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
            // Nothing we can do
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
            // Nothing we can do
        }

        return [];
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
    
    // Stub functions for other media servers
    private function getPlexWatchStats($days = 30) { return ['error' => true, 'message' => 'Plex not implemented yet']; }
    private function getJellyfinWatchStats($days = 30) { return ['error' => true, 'message' => 'Jellyfin not implemented yet']; }
}
