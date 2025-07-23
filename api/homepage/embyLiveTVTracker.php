<?php

trait EmbyLiveTVTrackerHomepageItem
{
    public function embyLiveTVTrackerSettingsArray($infoOnly = false)
    {
        $homepageInformation = [
            'name' => 'EmbyLiveTVTracker',
            'enabled' => strpos('personal', $this->config['license']) !== false,
            'image' => 'plugins/images/homepage/embyLiveTVTracker.png',
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
                    $this->settingsOption('enable', 'homepageEmbyLiveTVTrackerEnabled'),
                    $this->settingsOption('auth', 'homepageEmbyLiveTVTrackerAuth'),
                ],
                'Connection' => [
                    $this->settingsOption('url', 'embyURL'),
                    $this->settingsOption('token', 'embyToken'),
                    $this->settingsOption('disable-cert-check', 'embyDisableCertCheck'),
                    $this->settingsOption('use-custom-certificate', 'embyUseCustomCertificate'),
                ],
                'Display Options' => [
                    $this->settingsOption('number', 'homepageEmbyLiveTVTrackerRefresh', ['label' => 'Auto-refresh Interval (minutes)', 'min' => 1, 'max' => 60]),
                    $this->settingsOption('switch', 'homepageEmbyLiveTVTrackerCompactView', ['label' => 'Use Compact View']),
                    $this->settingsOption('switch', 'homepageEmbyLiveTVTrackerShowDuration', ['label' => 'Show Recording Duration']),
                    $this->settingsOption('switch', 'homepageEmbyLiveTVTrackerShowSeriesInfo', ['label' => 'Show Series Information']),
                    $this->settingsOption('switch', 'homepageEmbyLiveTVTrackerShowUserInfo', ['label' => 'Show User Information']),
                    $this->settingsOption('number', 'homepageEmbyLiveTVTrackerMaxItems', ['label' => 'Maximum Scheduled Items', 'min' => 5, 'max' => 50]),
                    $this->settingsOption('switch', 'homepageEmbyLiveTVTrackerShowCompleted', ['label' => 'Show Completed Recordings']),
                    $this->settingsOption('number', 'homepageEmbyLiveTVTrackerDaysShown', ['label' => 'Days of Completed Recordings', 'min' => 1, 'max' => 30]),
                    $this->settingsOption('number', 'homepageEmbyLiveTVTrackerMaxCompletedItems', ['label' => 'Maximum Completed Items', 'min' => 5, 'max' => 50]),
                    $this->settingsOption('switch', 'homepageEmbyLiveTVTrackerDebug', ['label' => 'Enable Debug Logging']),
                ],
                'Test Connection' => [
                    $this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
                    $this->settingsOption('test', 'embyLiveTVTracker'),
                ]
            ]
        ];
        return array_merge($homepageInformation, $homepageSettings);
    }

    public function testConnectionEmbyLiveTVTracker()
    {
        if (!$this->homepageItemPermissions($this->embyLiveTVTrackerHomepagePermissions('test'), true)) {
            return false;
        }
        $url = $this->qualifyURL($this->config['embyURL']);
        $url = $url . "/emby/System/Info?api_key=" . $this->config['embyToken'];
        $options = $this->requestOptions($url, null, $this->config['embyDisableCertCheck'], $this->config['embyUseCustomCertificate']);
        try {
            $response = Requests::get($url, [], $options);
            if ($response->success) {
                $info = json_decode($response->body, true);
                if (isset($info['ServerName'])) {
                    // Test LiveTV functionality
                    $liveTvUrl = $this->qualifyURL($this->config['embyURL']) . '/emby/LiveTv/Info?api_key=' . $this->config['embyToken'];
                    try {
                        $liveTvResponse = Requests::get($liveTvUrl, [], $options);
                        $liveTvInfo = json_decode($liveTvResponse->body, true);
                        $hasLiveTV = isset($liveTvInfo['Services']) && count($liveTvInfo['Services']) > 0;
                        $message = 'Successfully connected to ' . $info['ServerName'];
                        if ($hasLiveTV) {
                            $message .= ' with LiveTV support enabled';
                        } else {
                            $message .= ' (Warning: LiveTV may not be configured)';
                        }
                        $this->setAPIResponse('success', $message, 200);
                    } catch (Exception $e) {
                        $this->setAPIResponse('success', 'Connected to ' . $info['ServerName'] . ' but LiveTV status unknown', 200);
                    }
                } else {
                    $this->setAPIResponse('error', 'Invalid response from Emby server', 500);
                }
                return true;
            } else {
                $this->setAPIResponse('error', 'Emby Connection Error', 500);
                return false;
            }
        } catch (Requests_Exception $e) {
            $this->setResponse(500, $e->getMessage());
            return false;
        }
    }

    public function embyLiveTVTrackerHomepagePermissions($key = null)
    {
        $permissions = [
            'test' => [
                'enabled' => [
                    'homepageEmbyLiveTVTrackerEnabled',
                ],
                'auth' => [
                    'homepageEmbyLiveTVTrackerAuth',
                ],
                'not_empty' => [
                    'embyURL',
                    'embyToken'
                ]
            ],
            'main' => [
                'enabled' => [
                    'homepageEmbyLiveTVTrackerEnabled'
                ],
                'auth' => [
                    'homepageEmbyLiveTVTrackerAuth'
                ],
                'not_empty' => [
                    'embyURL',
                    'embyToken'
                ]
            ]
        ];
        return $this->homepageCheckKeyPermissions($key, $permissions);
    }

    public function homepageOrderEmbyLiveTVTracker()
    {
        if ($this->homepageItemPermissions($this->embyLiveTVTrackerHomepagePermissions('main'))) {
            $refreshInterval = ($this->config['homepageEmbyLiveTVTrackerRefresh'] ?? 5) * 60000; // Convert minutes to milliseconds
            $compactView = ($this->config['homepageEmbyLiveTVTrackerCompactView'] ?? false) ? 'true' : 'false';
            $showDuration = ($this->config['homepageEmbyLiveTVTrackerShowDuration'] ?? true) ? 'true' : 'false';
            $showSeriesInfo = ($this->config['homepageEmbyLiveTVTrackerShowSeriesInfo'] ?? true) ? 'true' : 'false';
            $showUserInfo = ($this->config['homepageEmbyLiveTVTrackerShowUserInfo'] ?? false) ? 'true' : 'false';
            $maxItems = $this->config['homepageEmbyLiveTVTrackerMaxItems'] ?? 10;
            $showCompleted = ($this->config['homepageEmbyLiveTVTrackerShowCompleted'] ?? true) ? 'true' : 'false';
            $daysShown = $this->config['homepageEmbyLiveTVTrackerDaysShown'] ?? 7;
            $maxCompletedItems = $this->config['homepageEmbyLiveTVTrackerMaxCompletedItems'] ?? 5;

            $panelClass = ($compactView === 'true') ? 'panel-compact' : '';
            $statsClass = ($compactView === 'true') ? 'col-sm-6' : 'col-sm-3';

            return '
            <div id="' . __FUNCTION__ . '">
                <div class="white-box ' . $panelClass . '">
                    <div class="white-box-header">
                        <i class="fa fa-tv"></i> Emby LiveTV Tracker
                        <span class="pull-right">
                            <small id="embylivetv-last-update" class="text-muted"></small>
                            <button class="btn btn-xs btn-primary" onclick="refreshEmbyLiveTVData()" title="Refresh Data">
                                <i class="fa fa-refresh" id="embylivetv-refresh-icon"></i>
                            </button>
                        </span>
                    </div>
                    <div class="white-box-content">
                        <div class="row" id="embylivetv-stats">
                            <div class="' . $statsClass . '">
                                <div class="text-center">
                                    <h3 id="embylivetv-active-timers" class="text-success">-</h3>
                                    <small>Active Timers</small>
                                </div>
                            </div>
                            <div class="' . $statsClass . '">
                                <div class="text-center">
                                    <h3 id="embylivetv-series-timers" class="text-info">-</h3>
                                    <small>Series Timers</small>
                                </div>
                            </div>
                            ' . (($compactView === 'false') ? '
                            <div class="' . $statsClass . '">
                                <div class="text-center">
                                    <h3 id="embylivetv-today-recordings" class="text-warning">-</h3>
                                    <small>Today\'s Recordings</small>
                                </div>
                            </div>
                            <div class="' . $statsClass . '">
                                <div class="text-center">
                                    <h3 id="embylivetv-total-recordings" class="text-primary">-</h3>
                                    <small>Total Recordings</small>
                                </div>
                            </div>
                            ' : '') . '
                        </div>

                        <!-- Scheduled Recordings Table -->
                        <div class="row" style="margin-top: 20px;">
                            <div class="col-lg-12">
                                <h4>
                                    Scheduled Recordings
                                    <small class="text-muted">Upcoming and active timers</small>
                                </h4>
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped table-condensed">
                                        <thead>
                                            <tr>
                                                <th width="120">Date</th>
                                                <th>Series</th>
                                                ' . (($showSeriesInfo === 'true') ? '<th>Episode Title</th>' : '') . '
                                                <th>Channel</th>
                                                ' . (($showUserInfo === 'true') ? '<th>User</th>' : '') . '
                                                ' . (($showDuration === 'true') ? '<th width="80">Duration</th>' : '') . '
                                                <th width="70">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="embylivetv-scheduled-table">
                                            <tr>
                                                <td colspan="' . (4 + ($showSeriesInfo === 'true' ? 1 : 0) + ($showUserInfo === 'true' ? 1 : 0) + ($showDuration === 'true' ? 1 : 0)) . '" class="text-center">
                                                    <i class="fa fa-spinner fa-spin"></i> Loading...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Completed Recordings Table (only if enabled) -->
                        ' . (($showCompleted === 'true') ? '
                        <div class="row" style="margin-top: 20px;">
                            <div class="col-lg-12">
                                <h4>
                                    Completed Recordings
                                    <small class="text-muted">Recent recordings</small>
                                </h4>
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped table-condensed">
                                        <thead>
                                            <tr>
                                                <th width="120">Date</th>
                                                <th>Series</th>
                                                ' . (($showSeriesInfo === 'true') ? '<th>Series</th>' : '') . '
                                                ' . (($showDuration === 'true') ? '<th width="80">Duration</th>' : '') . '
                                                <th width="70">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="embylivetv-completed-table">
                                            <tr>
                                                <td colspan="' . (3 + ($showSeriesInfo === 'true' ? 1 : 0) + ($showDuration === 'true' ? 1 : 0)) . '" class="text-center">
                                                    <i class="fa fa-spinner fa-spin"></i> Loading...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                </div>
                        </div>
                        ' : '') . '
                    </div>
                </div>
            </div>

            <style>
            .panel-compact .white-box-content { padding: 10px; }
            .panel-compact h3 { margin: 5px 0; font-size: 1.8em; }
            .panel-compact small { font-size: 0.85em; }
            #' . __FUNCTION__ . ' .table-condensed td { padding: 4px 8px; font-size: 0.9em; }
            #' . __FUNCTION__ . ' .status-success { color: #5cb85c; }
            #' . __FUNCTION__ . ' .status-recording { color: #d9534f; }
            #' . __FUNCTION__ . ' .status-scheduled { color: #f0ad4e; }
            </style>

            <script>
            var embyLiveTVRefreshTimer;
            var embyLiveTVLastRefresh = 0;

            function refreshEmbyLiveTVData() {
                var refreshIcon = $("#embylivetv-refresh-icon");
                refreshIcon.addClass("fa-spin");

                // Show loading state
                $("#embylivetv-stats h3").text("-");
                $("#embylivetv-scheduled-table").html("<tr><td colspan=\"' . (4 + ($showSeriesInfo === 'true' ? 1 : 0) + ($showUserInfo === 'true' ? 1 : 0) + ($showDuration === 'true' ? 1 : 0)) . '\" class=\"text-center\"><i class=\"fa fa-spinner fa-spin\"></i> Loading...</td></tr>");
                ' . (($showCompleted === 'true') ? '$("#embylivetv-completed-table").html("<tr><td colspan=\"' . (4 + ($showSeriesInfo === 'true' ? 1 : 0) + ($showUserInfo === 'true' ? 1 : 0) + ($showDuration === 'true' ? 1 : 0)) . '\" class=\"text-center\"><i class=\"fa fa-spinner fa-spin\"></i> Loading...</td></tr>");' : '') . ';

                // Load stats and activity
                homepageEmbyLiveTVTrackerStats()
                .always(function() {
                    refreshIcon.removeClass("fa-spin");
                    embyLiveTVLastRefresh = Date.now();
                    updateEmbyLiveTVLastRefreshTime();
                });
            }

            function updateEmbyLiveTVLastRefreshTime() {
                if (embyLiveTVLastRefresh > 0) {
                    var ago = Math.floor((Date.now() - embyLiveTVLastRefresh) / 1000);
                    var timeText = ago < 60 ? ago + "s ago" : Math.floor(ago / 60) + "m ago";
                    $("#embylivetv-last-update").text("Updated " + timeText);
                }
            }

            function homepageEmbyLiveTVTrackerStats() {
                return organizrAPI2("GET", "api/v2/homepage/embyLiveTVTracker/stats")
                .done(function(data) {
                    console.log("Stats response received:", data);
                    if (data && data.response && data.response.result === "success" && data.response.data) {
                        console.log("Stats data is valid, loading activity...");
                        $("#embylivetv-active-timers").text(data.response.data.activeTimers || "0");
                        $("#embylivetv-series-timers").text(data.response.data.seriesTimers || "0");
                        ' . (($compactView === 'false') ? '
                        $("#embylivetv-today-recordings").text(data.response.data.todaysRecordings || "0");
                        $("#embylivetv-total-recordings").text(data.response.data.totalRecordings || "0");
                        ' : '') . '
                        
                        // Load activity
                        homepageEmbyLiveTVTrackerActivity();
                    } else {
                        console.error("Stats response structure issue:", {
                            hasData: !!data,
                            hasResponse: !!(data && data.response),
                            result: data && data.response && data.response.result,
                            hasResponseData: !!(data && data.response && data.response.data)
                        });
                        console.error("Failed to load Emby LiveTV stats:", data.response ? data.response.message : "Unknown error");
                        $("#embylivetv-stats h3").text("?").attr("title", "Error loading data");
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error("Error loading Emby LiveTV stats:", error);
                    $("#embylivetv-stats h3").text("!").attr("title", "Connection failed");
                });
            }

            function homepageEmbyLiveTVTrackerActivity() {
                console.log("Activity function called - making API request...");
                return organizrAPI2("GET", "api/v2/homepage/embyLiveTVTracker/activity?days=' . ($daysShown ?: 7) . '\u0026limit=' . ($maxItems ?: 10) . '")
                .done(function(data) {
                    console.log("Activity response received:", data);
                    console.log("Response structure check:", {
                        hasData: !!data,
                        hasResponse: !!(data && data.response),
                        result: data && data.response && data.response.result,
                        hasResponseData: !!(data && data.response && data.response.data),
                        hasActivities: !!(data && data.response && data.response.data && data.response.data.activities),
                        activitiesLength: data && data.response && data.response.data && data.response.data.activities ? data.response.data.activities.length : 0
                    });
                    if (data && data.response && data.response.result === "success" && data.response.data) {
                        var scheduledRecordings = data.response.data.scheduledRecordings || [];
                        var completedRecordings = data.response.data.completedRecordings || [];
                        
                        console.log("Scheduled recordings:", scheduledRecordings.length);
                        console.log("Completed recordings:", completedRecordings.length);
                        
                        // Apply limits to each category separately to ensure we show both types
                        var maxScheduled = Math.floor(' . $maxItems . ' * 0.7); // 70% for scheduled
                        var maxCompleted = ' . $maxItems . ' - maxScheduled; // 30% for completed
                        
                        // If we have fewer scheduled than the 70% allocation, give more space to completed
                        if (scheduledRecordings.length < maxScheduled) {
                            maxCompleted = Math.min(completedRecordings.length, ' . $maxItems . ' - scheduledRecordings.length);
                        }
                        // If we have fewer completed than the 30% allocation, give more space to scheduled
                        if (completedRecordings.length < maxCompleted) {
                            maxScheduled = Math.min(scheduledRecordings.length, ' . $maxItems . ' - completedRecordings.length);
                        }
                        
                        var scheduledActivities = scheduledRecordings.slice(0, maxScheduled);
                        var completedActivities = completedRecordings.slice(0, maxCompleted);
                        
                        console.log("Split - Scheduled activities:", scheduledActivities.length);
                        console.log("Split - Completed activities:", completedActivities.length);

                        // Helper function to format scheduled activity rows
                        function formatScheduledRow(activity) {
                            var date = new Date(activity.date);
                            var formattedDate = date.toLocaleDateString() + " " + date.toLocaleTimeString([], {hour: "2-digit", minute:"2-digit"});
                            var status = activity.status || "Scheduled";
                            var statusClass = status.toLowerCase() === "completed" ? "status-success" :
                                            status.toLowerCase() === "recording" ? "status-recording" : "status-scheduled";

                            return "<tr>" +
                                "<td><small>" + formattedDate + "</small></td>" +
                                "<td>" + (activity.seriesName || activity.name || "-") + "</td>" +
                                ' . (($showSeriesInfo === 'true') ? '"<td><small>" + (activity.episodeTitle || activity.name || "-") + "</small></td>" +' : '') . '
                                "<td><small>" + (activity.channelName || "-") + "</small></td>" +
                                ' . (($showUserInfo === 'true') ? '"<td><small>" + (activity.userName || "-") + "</small></td>" +' : '') . '
                                ' . (($showDuration === 'true') ? '"<td><small>" + (activity.duration || "-") + "</small></td>" +' : '') . '
                                "<td><span class=\"" + statusClass + "\"><i class=\"fa fa-circle\"></i></span></td>" +
                                "</tr>";
                        }
                        
                        // Helper function to format completed activity rows (no channel or user columns)
                        function formatCompletedRow(activity) {
                            var date = new Date(activity.date);
                            var formattedDate = date.toLocaleDateString() + " " + date.toLocaleTimeString([], {hour: "2-digit", minute:"2-digit"});
                            var status = activity.status || "Completed";
                            var statusClass = "status-success";

                            return "<tr>" +
                                "<td><small>" + formattedDate + "</small></td>" +
                                "<td>" + (activity.seriesName || activity.name || "-") + "</td>" +
                                ' . (($showSeriesInfo === 'true') ? '"<td><small>" + (activity.seriesName || "-") + "</small></td>" +' : '') . '
                                ' . (($showDuration === 'true') ? '"<td><small>" + (activity.duration || "-") + "</small></td>" +' : '') . '
                                "<td><span class=\"" + statusClass + "\"><i class=\"fa fa-circle\"></i></span></td>" +
                                "</tr>";
                        }
                        
                        // Populate scheduled recordings table
                        var scheduledTable = $("#embylivetv-scheduled-table");
                        if (scheduledActivities.length === 0) {
                            scheduledTable.html("<tr><td colspan=\"' . (4 + ($showSeriesInfo === 'true' ? 1 : 0) + ($showDuration === 'true' ? 1 : 0) + ($showUserInfo === 'true' ? 1 : 0)) . '\" class=\"text-center text-muted\">No scheduled recordings</td></tr>");
                        } else {
                            var scheduledRows = scheduledActivities.map(formatScheduledRow).join("");
                            scheduledTable.html(scheduledRows);
                        }
                        
                        // Populate completed recordings table (only if enabled and table exists)
                        ' . (($showCompleted === 'true') ? '
                        var completedTable = $("#embylivetv-completed-table");
                        if (completedActivities.length === 0) {
                            completedTable.html("<tr><td colspan=\"' . (3 + ($showSeriesInfo === 'true' ? 1 : 0) + ($showDuration === 'true' ? 1 : 0)) . '\" class=\"text-center text-muted\">No completed recordings</td></tr>");
                        } else {
                            var completedRows = completedActivities.map(formatCompletedRow).join("");
                            completedTable.html(completedRows);
                        }
                        ' : '') . '
                    } else {
                        $("#embylivetv-scheduled-table").html("<tr><td colspan=\"' . (4 + ($showSeriesInfo === 'true' ? 1 : 0) + ($showDuration === 'true' ? 1 : 0) + ($showUserInfo === 'true' ? 1 : 0)) . '\" class=\"text-center text-muted\">No activity data available</td></tr>");
                        ' . (($showCompleted === 'true') ? '$("#embylivetv-completed-table").html("<tr><td colspan=\"' . (3 + ($showSeriesInfo === 'true' ? 1 : 0) + ($showDuration === 'true' ? 1 : 0)) . '\" class=\"text-center text-muted\">No activity data available</td></tr>");' : '') . '
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error("Error loading Emby LiveTV activity:", error);
                    $("#embylivetv-scheduled-table").html("<tr><td colspan=\"' . (4 + ($showSeriesInfo === 'true' ? 1 : 0) + ($showUserInfo === 'true' ? 1 : 0) + ($showDuration === 'true' ? 1 : 0)) . '\" class=\"text-center text-danger\">Error loading activity data</td></tr>");
                    ' . (($showCompleted === 'true') ? '$("#embylivetv-completed-table").html("<tr><td colspan=\"' . (3 + ($showSeriesInfo === 'true' ? 1 : 0) + ($showDuration === 'true' ? 1 : 0)) . '\" class=\"text-center text-danger\">Error loading activity data</td></tr>");' : '') . '
                });
            }

            // Auto-refresh setup
            var refreshInterval = ' . $refreshInterval . ';
            if (refreshInterval > 0) {
                embyLiveTVRefreshTimer = setInterval(function() {
                    refreshEmbyLiveTVData();
                }, refreshInterval);
            }

            // Update time display every 30 seconds
            setInterval(updateEmbyLiveTVLastRefreshTime, 30000);

            // Initial load
            $(document).ready(function() {
                refreshEmbyLiveTVData();
            });

            // Cleanup timer when page unloads
            $(window).on("beforeunload", function() {
                if (embyLiveTVRefreshTimer) {
                    clearInterval(embyLiveTVRefreshTimer);
                }
            });
            </script>
            ';
        }
    }

    public function getHomepageEmbyLiveTVStats()
    {
        if (!$this->homepageItemPermissions($this->embyLiveTVTrackerHomepagePermissions('main'), true)) {
            return false;
        }
        
        if (!$this->config['embyURL'] || !$this->config['embyToken']) {
            $this->setAPIResponse('error', 'Emby URL or Token not configured', 500);
            return false;
        }
        
        try {
            $options = $this->requestOptions($this->config['embyURL'], null, $this->config['embyDisableCertCheck'], $this->config['embyUseCustomCertificate']);
            $baseUrl = $this->qualifyURL($this->config['embyURL']);
            
            $stats = [
                'activeTimers' => 0,
                'seriesTimers' => 0,
                'todaysRecordings' => 0,
                'totalRecordings' => 0,
                'recentRecordings' => []
            ];
            
            // Get active timers
            $timersUrl = $baseUrl . '/emby/LiveTv/Timers?api_key=' . $this->config['embyToken'];
            try {
                $timersResponse = Requests::get($timersUrl, [], $options);
                if ($timersResponse->success) {
                    $timers = json_decode($timersResponse->body, true);
                    $stats['activeTimers'] = count($timers['Items'] ?? []);
                }
            } catch (Exception $e) {
                $this->setLoggerChannel('EmbyLiveTVTracker')->warning('Failed to get timers: ' . $e->getMessage());
            }
            
            // Get series timers
            $seriesTimersUrl = $baseUrl . '/emby/LiveTv/SeriesTimers?api_key=' . $this->config['embyToken'];
            try {
                $seriesTimersResponse = Requests::get($seriesTimersUrl, [], $options);
                if ($seriesTimersResponse->success) {
                    $seriesTimers = json_decode($seriesTimersResponse->body, true);
                    $stats['seriesTimers'] = count($seriesTimers['Items'] ?? []);
                }
            } catch (Exception $e) {
                $this->setLoggerChannel('EmbyLiveTVTracker')->warning('Failed to get series timers: ' . $e->getMessage());
            }
            
            // Get recordings from the last 90 days
            $recordingsUrl = $baseUrl . '/emby/LiveTv/Recordings?api_key=' . $this->config['embyToken'] . '&StartIndex=0&Limit=50&Fields=Overview,DateCreated&SortBy=DateCreated&SortOrder=Descending';
            try {
                $recordingsResponse = Requests::get($recordingsUrl, [], $options);
                if ($recordingsResponse->success) {
                    $recordings = json_decode($recordingsResponse->body, true);
                    $allRecordings = $recordings['Items'] ?? [];
                    
                    // Count today's recordings
                    $today = date('Y-m-d');
                    $todaysCount = 0;
                    $recentRecordings = [];
                    
                    foreach ($allRecordings as $recording) {
                        if (isset($recording['DateCreated'])) {
                            $recordDate = date('Y-m-d', strtotime($recording['DateCreated']));
                            if ($recordDate === $today) {
                                $todaysCount++;
                            }
                            
                            // Add to recent recordings list
                            $recentRecordings[] = [
                                'date' => $recordDate,
                                'program' => $recording['Name'] ?? 'Unknown',
                                'series' => $recording['SeriesName'] ?? '',
                                'channel' => $recording['ChannelName'] ?? 'Unknown Channel',
                                'status' => 'Completed'
                            ];
                        }
                    }
                    
                    $stats['todaysRecordings'] = $todaysCount;
                    $stats['totalRecordings'] = $recordings['TotalRecordCount'] ?? count($allRecordings);
                    $stats['recentRecordings'] = array_slice($recentRecordings, 0, 10); // Limit to 10 recent recordings
                }
            } catch (Exception $e) {
                $this->setLoggerChannel('EmbyLiveTVTracker')->warning('Failed to get recordings: ' . $e->getMessage());
            }
            
            $this->setAPIResponse('success', 'LiveTV stats retrieved successfully', 200, $stats);
            return true;
            
        } catch (Exception $e) {
            $this->setAPIResponse('error', 'Failed to retrieve LiveTV stats: ' . $e->getMessage(), 500);
            return false;
        }
    }

    public function getHomepageEmbyLiveTVActivity()
    {
        $debugEnabled = $this->config['homepageEmbyLiveTVTrackerDebug'] ?? false;
        
        if (!$this->homepageItemPermissions($this->embyLiveTVTrackerHomepagePermissions('main'), true)) {
            if ($debugEnabled) {
                $this->setLoggerChannel('EmbyLiveTVTracker')->warning('Permission denied for user');
            }
            $this->setAPIResponse('error', 'Permission denied', 403);
            return false;
        }
        
        if (!$this->config['embyURL'] || !$this->config['embyToken']) {
            $this->setAPIResponse('error', 'Emby URL or Token not configured', 500);
            return false;
        }
        
        try {
            if ($debugEnabled) {
                $this->setLoggerChannel('EmbyLiveTVTracker')->info('Activity method called - starting execution');
            }
            $options = $this->requestOptions($this->config['embyURL'], null, $this->config['embyDisableCertCheck'], $this->config['embyUseCustomCertificate']);
            $baseUrl = $this->qualifyURL($this->config['embyURL']);
            if ($debugEnabled) {
                $this->setLoggerChannel('EmbyLiveTVTracker')->info('Base URL configured: ' . $baseUrl);
            }
            
            $scheduledRecordings = [];
            $completedRecordings = [];
            $maxItems = intval($this->config['homepageEmbyLiveTVTrackerMaxItems'] ?? 10);
            $showCompleted = $this->config['homepageEmbyLiveTVTrackerShowCompleted'] ?? true;
            $maxCompletedItems = intval($this->config['homepageEmbyLiveTVTrackerMaxCompletedItems'] ?? 5);
            
            // Get user info if user info is enabled
            $userMap = [];
            $showUserInfo = $this->config['homepageEmbyLiveTVTrackerShowUserInfo'] ?? false;
            if ($showUserInfo) {
                $usersUrl = $baseUrl . '/emby/Users?api_key=' . $this->config['embyToken'];
                try {
                    $usersResponse = Requests::get($usersUrl, [], $options);
                    if ($usersResponse->success) {
                        $users = json_decode($usersResponse->body, true);
                        foreach ($users as $user) {
                            $userMap[$user['Id']] = $user['Name'];
                        }
                        if ($debugEnabled) {
                            $this->setLoggerChannel('EmbyLiveTVTracker')->info('Retrieved ' . count($userMap) . ' users for mapping');
                        }
                    }
                } catch (Exception $e) {
                    $this->setLoggerChannel('EmbyLiveTVTracker')->warning('Failed to get users: ' . $e->getMessage());
                }
            }
            
            // Get scheduled recordings (active timers)
            $timersUrl = $baseUrl . '/emby/LiveTv/Timers?api_key=' . $this->config['embyToken'] . '&Fields=ChannelName,ChannelId,SeriesName,ProgramInfo,StartDate,EndDate,UserId';
            if ($debugEnabled) {
                $this->setLoggerChannel('EmbyLiveTVTracker')->info('Fetching timers from URL: ' . $timersUrl);
            }
            try {
                $timersResponse = Requests::get($timersUrl, [], $options);
                if ($debugEnabled) {
                    $this->setLoggerChannel('EmbyLiveTVTracker')->info('Timers API response status: ' . ($timersResponse->success ? 'success' : 'failed'));
                    $this->setLoggerChannel('EmbyLiveTVTracker')->info('Timers API response code: ' . $timersResponse->status_code);
                }
                if ($timersResponse->success) {
                    $timers = json_decode($timersResponse->body, true);
                    $allTimers = $timers['Items'] ?? [];
                    if ($debugEnabled) {
                        $this->setLoggerChannel('EmbyLiveTVTracker')->info('Retrieved ' . count($allTimers) . ' timers from Emby API');
                        $this->setLoggerChannel('EmbyLiveTVTracker')->info('Timers API raw response length: ' . strlen($timersResponse->body));
                        $this->setLoggerChannel('EmbyLiveTVTracker')->info('First timer sample: ' . json_encode(array_slice($allTimers, 0, 1)));
                    }
                    
                    // Sort timers by start date
                    usort($allTimers, function($a, $b) {
                        $aDate = $a['StartDate'] ?? '';
                        $bDate = $b['StartDate'] ?? '';
                        return strcmp($aDate, $bDate);
                    });

                    $timersToProcess = array_slice($allTimers, 0, intval($maxItems));
                    if ($debugEnabled) {
                        $this->setLoggerChannel('EmbyLiveTVTracker')->info('Processing ' . count($timersToProcess) . ' timers (maxItems: ' . $maxItems . ')');
                        $this->setLoggerChannel('EmbyLiveTVTracker')->info('All timers count before slice: ' . count($allTimers));
                        $this->setLoggerChannel('EmbyLiveTVTracker')->info('MaxItems value: ' . $maxItems . ' (type: ' . gettype($maxItems) . ')');
                    }
                    
                    foreach ($timersToProcess as $index => $timer) {
                        if ($debugEnabled) {
                            $this->setLoggerChannel('EmbyLiveTVTracker')->info('Processing timer ' . ($index + 1) . ': ' . json_encode([
                                'Name' => $timer['Name'] ?? 'no name',
                                'StartDate' => $timer['StartDate'] ?? 'no start date',
                                'EndDate' => $timer['EndDate'] ?? 'no end date',
                                'ChannelName' => $timer['ChannelName'] ?? 'no channel name',
                                'Status' => $timer['Status'] ?? 'no status',
                                'UserId' => $timer['UserId'] ?? 'no user'
                            ]));
                        }
                        
                        // Calculate duration
                        $duration = '-';
                        if (isset($timer['StartDate']) && isset($timer['EndDate'])) {
                            $start = strtotime($timer['StartDate']);
                            $end = strtotime($timer['EndDate']);
                            if ($start && $end) {
                                $minutes = round(($end - $start) / 60);
                                $hours = floor($minutes / 60);
                                $remainingMinutes = $minutes % 60;
                                if ($hours > 0) {
                                    $duration = sprintf('%dh %dm', $hours, $remainingMinutes);
                                } else {
                                    $duration = sprintf('%dm', $minutes);
                                }
                            }
                        }
                        
                        // Get channel name - timers should have this information
                        $channelName = $timer['ChannelName'] ?? null;
                        if (empty($channelName) && !empty($timer['ChannelId'])) {
                            $channelName = 'Channel ' . $timer['ChannelId'];
                        } elseif (empty($channelName)) {
                            $channelName = 'Unknown Channel';
                        }
                        
                        // Get user name
                        $userName = null;
                        if ($showUserInfo && !empty($timer['UserId']) && isset($userMap[$timer['UserId']])) {
                            $userName = $userMap[$timer['UserId']];
                        }
                        
                        // Determine status based on timing
                        $status = 'Scheduled';
                        $startTime = strtotime($timer['StartDate'] ?? '');
                        $endTime = strtotime($timer['EndDate'] ?? '');
                        $now = time();
                        
                        if ($startTime && $endTime) {
                            if ($now >= $startTime && $now <= $endTime) {
                                $status = 'Recording';
                            } elseif ($now > $endTime) {
                                $status = 'Completed';
                            }
                        }
                        
                        // Get series name and episode title - try multiple approaches
                        $seriesName = '';
                        $episodeTitle = '';
                        
                        // Check for episode title first
                        if (!empty($timer['ProgramInfo']['EpisodeTitle'])) {
                            $episodeTitle = $timer['ProgramInfo']['EpisodeTitle'];
                        }
                        
                        // Get series name
                        if (!empty($timer['SeriesName'])) {
                            $seriesName = $timer['SeriesName'];
                        } elseif (!empty($timer['ProgramInfo']['SeriesName'])) {
                            $seriesName = $timer['ProgramInfo']['SeriesName'];
                        } elseif (!empty($timer['ProgramInfo']['Name'])) {
                            $seriesName = $timer['ProgramInfo']['Name'];
                        } elseif (!empty($timer['Name'])) {
                            $seriesName = $timer['Name'];
                        }
                        
                        // Use episode title if available, otherwise use program/series name
                        $displayName = $episodeTitle ? $episodeTitle : ($timer['Name'] ?? ($timer['ProgramInfo']['Name'] ?? 'Unknown Program'));
                        
                        $activity = [
                            'date' => $timer['StartDate'] ?? '',
                            'name' => $displayName,
                            'seriesName' => $seriesName,
                            'episodeTitle' => $episodeTitle,
                            'channelName' => $channelName,
                            'userName' => $userName,
                            'duration' => $duration,
                            'status' => $status,
                            'type' => 'timer'
                        ];
                        $this->setLoggerChannel('EmbyLiveTVTracker')->info('Created activity for timer ' . ($index + 1) . ': ' . json_encode($activity));
                        
                        // Debug timer date parsing
                        $this->setLoggerChannel('EmbyLiveTVTracker')->info('Timer date debug - StartDate: "' . ($timer['StartDate'] ?? 'null') . '", parsed startTime: ' . ($startTime ? date('Y-m-d H:i:s', $startTime) : 'failed to parse') . ', EndDate: "' . ($timer['EndDate'] ?? 'null') . '", parsed endTime: ' . ($endTime ? date('Y-m-d H:i:s', $endTime) : 'failed to parse') . ', current time: ' . date('Y-m-d H:i:s', $now) . ', calculated status: ' . $status);
                        
                        $scheduledRecordings[] = $activity;
                    }
                }
            } catch (Exception $e) {
                $this->setLoggerChannel('EmbyLiveTVTracker')->warning('Failed to get timers for activity: ' . $e->getMessage());
            }
            
            // Get completed recordings if enabled
            if ($showCompleted) {
                $recordingsUrl = $baseUrl . '/emby/LiveTv/Recordings?api_key=' . $this->config['embyToken'] . '&StartIndex=0&Limit=' . $maxCompletedItems . '&Fields=DateCreated,SeriesName,RunTimeTicks&SortBy=DateCreated&SortOrder=Descending';
                $this->setLoggerChannel('EmbyLiveTVTracker')->info('Fetching completed recordings from URL: ' . $recordingsUrl);
                try {
                    $recordingsResponse = Requests::get($recordingsUrl, [], $options);
                    if ($recordingsResponse->success) {
                        $recordings = json_decode($recordingsResponse->body, true);
                        $allRecordings = $recordings['Items'] ?? [];
                        $this->setLoggerChannel('EmbyLiveTVTracker')->info('Retrieved ' . count($allRecordings) . ' completed recordings');
                        
                        foreach ($allRecordings as $recording) {
                            if (isset($recording['DateCreated'])) {
                                // Format duration
                                $duration = '-';
                                if (isset($recording['RunTimeTicks']) && $recording['RunTimeTicks'] > 0) {
                                    $minutes = floor($recording['RunTimeTicks'] / 600000000);
                                    $hours = floor($minutes / 60);
                                    $remainingMinutes = $minutes % 60;
                                    if ($hours > 0) {
                                        $duration = sprintf('%dh %dm', $hours, $remainingMinutes);
                                    } else {
                                        $duration = sprintf('%dm', $minutes);
                                    }
                                }
                                
                                $completedRecordings[] = [
                                    'date' => $recording['DateCreated'],
                                    'name' => $recording['Name'] ?? 'Unknown Program',
                                    'seriesName' => $recording['SeriesName'] ?? '',
                                    'channelName' => 'Unknown Channel', // Completed recordings don't have reliable channel info
                                    'userName' => null, // No user info available for completed recordings
                                    'duration' => $duration,
                                    'status' => 'Completed',
                                    'type' => 'recording'
                                ];
                            }
                        }
                    }
                } catch (Exception $e) {
                    $this->setLoggerChannel('EmbyLiveTVTracker')->warning('Failed to get completed recordings: ' . $e->getMessage());
                }
            }
            
            $this->setLoggerChannel('EmbyLiveTVTracker')->info('Final scheduled recordings count: ' . count($scheduledRecordings));
            $this->setLoggerChannel('EmbyLiveTVTracker')->info('Final completed recordings count: ' . count($completedRecordings));
            $this->setLoggerChannel('EmbyLiveTVTracker')->info('Sample scheduled: ' . json_encode(array_slice($scheduledRecordings, 0, 1)));
            $this->setLoggerChannel('EmbyLiveTVTracker')->info('Sample completed: ' . json_encode(array_slice($completedRecordings, 0, 1)));
            
            $this->setAPIResponse('success', 'LiveTV activity retrieved successfully', 200, [
                'scheduledRecordings' => $scheduledRecordings,
                'completedRecordings' => $completedRecordings
            ]);
            return true;
            
        } catch (Exception $e) {
            $this->setAPIResponse('error', 'Failed to retrieve LiveTV activity: ' . $e->getMessage(), 500);
            return false;
        }
    }
}
?>
