<?php

trait HomepageUserWatchStatsSimple
{
    public function userWatchStatsSimpleSettingsArray($infoOnly = false)
    {
        $homepageInformation = [
            'name' => 'User Watch Statistics Simple',
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
                    $this->settingsOption('enable', 'homepageUserWatchStatsSimpleEnabled'),
                    $this->settingsOption('auth', 'homepageUserWatchStatsSimpleAuth'),
                ],
                'Connection' => [
                    $this->settingsOption('url', 'plexURL', ['label' => 'Tautulli URL']),
                    $this->settingsOption('token', 'plexToken', ['label' => 'Tautulli API Key']),
                    $this->settingsOption('disable-cert-check', 'plexDisableCertCheck'),
                    $this->settingsOption('use-custom-certificate', 'plexUseCustomCertificate'),
                ],
                'Test Connection' => [
                    $this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
                    $this->settingsOption('test', 'userWatchStatsSimple'),
                ]
            ]
        ];
        return array_merge($homepageInformation, $homepageSettings);
    }

    public function testConnectionUserWatchStatsSimple()
    {
        if (!$this->homepageItemPermissions($this->userWatchStatsSimpleHomepagePermissions('test'), true)) {
            return false;
        }
        $url = $this->qualifyURL($this->config['plexURL']);
        $url = $url . "/api/v2?apikey=" . $this->config['plexToken'] . '&cmd=get_server_info';
        $options = $this->requestOptions($url, null, $this->config['plexDisableCertCheck'], $this->config['plexUseCustomCertificate']);
        try {
            $response = Requests::get($url, [], $options);
            if ($response->success) {
                $data = json_decode($response->body, true);
                if (isset($data['response']['result']) && $data['response']['result'] === 'success') {
                    $this->setAPIResponse('success', 'Successfully connected to Tautulli', 200);
                    return true;
                } else {
                    $this->setAPIResponse('error', 'Invalid response from Tautulli server', 500);
                }
            } else {
                $this->setAPIResponse('error', 'Tautulli Connection Error', 500);
                return false;
            }
        } catch (Requests_Exception $e) {
            $this->setResponse(500, $e->getMessage());
            return false;
        }
    }

    public function userWatchStatsSimpleHomepagePermissions($key = null)
    {
        $permissions = [
            'test' => [
                'enabled' => [
                    'homepageUserWatchStatsSimpleEnabled',
                ],
                'auth' => [
                    'homepageUserWatchStatsSimpleAuth',
                ],
                'not_empty' => [
                    'plexURL',
                    'plexToken'
                ]
            ],
            'main' => [
                'enabled' => [
                    'homepageUserWatchStatsSimpleEnabled'
                ],
                'auth' => [
                    'homepageUserWatchStatsSimpleAuth'
                ],
                'not_empty' => [
                    'plexURL',
                    'plexToken'
                ]
            ]
        ];
        return $this->homepageCheckKeyPermissions($key, $permissions);
    }

    public function homepageOrderUserWatchStatsSimple()
    {
        if ($this->homepageItemPermissions($this->userWatchStatsSimpleHomepagePermissions('main'))) {
            $refreshInterval = ($this->config['homepageUserWatchStatsSimpleRefresh'] ?? 5) * 60000; // Convert minutes to milliseconds

            return '
            <div id="' . __FUNCTION__ . '">
                <div class="white-box">
                    <div class="white-box-header">
                        <i class="fa fa-bar-chart"></i> User Watch Statistics (Simple)
                        <span class="pull-right">
                            <small id="watchstats-simple-last-update" class="text-muted"></small>
                            <button class="btn btn-xs btn-primary" onclick="refreshUserWatchStatsSimple()" title="Refresh Data">
                                <i class="fa fa-refresh" id="watchstats-simple-refresh-icon"></i>
                            </button>
                        </span>
                    </div>
                    <div class="white-box-content">
                        <div class="row" id="watchstats-simple-content">
                            <div class="col-lg-12 text-center">
                                <i class="fa fa-spinner fa-spin"></i> Loading statistics...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            var watchStatsSimpleRefreshTimer;
            var watchStatsSimpleLastRefresh = 0;

            function refreshUserWatchStatsSimple() {
                var refreshIcon = $("#watchstats-simple-refresh-icon");
                refreshIcon.addClass("fa-spin");

                // Show loading state
                $("#watchstats-simple-content").html(\'\'<div class="col-lg-12 text-center"><i class="fa fa-spinner fa-spin"></i> Loading statistics...</div>\'\'');

                // Load watch statistics
                getUserWatchStatsSimpleData()
                .always(function() {
                    refreshIcon.removeClass("fa-spin");
                    watchStatsSimpleLastRefresh = Date.now();
                    updateWatchStatsSimpleLastRefreshTime();
                });
            }

            function updateWatchStatsSimpleLastRefreshTime() {
                if (watchStatsSimpleLastRefresh > 0) {
                    var ago = Math.floor((Date.now() - watchStatsSimpleLastRefresh) / 1000);
                    var timeText = ago < 60 ? ago + "s ago" : Math.floor(ago / 60) + "m ago";
                    $("#watchstats-simple-last-update").text("Updated " + timeText);
                }
            }

            function getUserWatchStatsSimpleData() {
                return organizrAPI2("GET", "api/v2/homepage/userWatchStatsSimple")
                .done(function(data) {
                    if (data && data.response && data.response.result === "success" && data.response.data) {
                        var stats = data.response.data;
                        var html = "";
                        
                        // Display basic statistics
                        html += \'\'<div class="col-lg-12"><h4>Basic Statistics (Simplified View)</h4></div>\'\'';
                        
                        // Show some basic info
                        html += \'\'<div class="col-lg-12"><div class="alert alert-info">This is a simplified version of User Watch Statistics for testing purposes.</div></div>\'\'';
                        
                        $("#watchstats-simple-content").html(html);
                    } else {
                        $("#watchstats-simple-content").html(\'\'<div class="col-lg-12 text-center text-danger">Failed to load statistics</div>\'\''');
                    }
                })
                .fail(function(xhr, status, error) {
                    $("#watchstats-simple-content").html(\'\'<div class="col-lg-12 text-center text-danger">Error loading statistics</div>\'\''');
                });
            }

            // Auto-refresh setup
            var refreshInterval = ' . $refreshInterval . ';
            if (refreshInterval > 0) {
                watchStatsSimpleRefreshTimer = setInterval(function() {
                    refreshUserWatchStatsSimple();
                }, refreshInterval);
            }

            // Update time display every 30 seconds
            setInterval(updateWatchStatsSimpleLastRefreshTime, 30000);

            // Initial load
            $(document).ready(function() {
                refreshUserWatchStatsSimple();
            });

            // Cleanup timer when page unloads
            $(window).on("beforeunload", function() {
                if (watchStatsSimpleRefreshTimer) {
                    clearInterval(watchStatsSimpleRefreshTimer);
                }
            });
            </script>
            ';
        }
    }

    /**
     * Main function to get simple watch statistics
     */
    public function getUserWatchStatsSimple($options = null)
    {
        if (!$this->homepageItemPermissions($this->userWatchStatsSimpleHomepagePermissions('main'), true)) {
            return false;
        }

        try {
            // For the simple version, just return basic test data
            $stats = [
                'period' => '30 days',
                'message' => 'Simple version for testing',
                'status' => 'active'
            ];
            
            $this->setAPIResponse('success', 'Simple watch statistics retrieved successfully', 200, $stats);
            return true;
            
        } catch (Exception $e) {
            $this->writeLog('error', 'User Watch Stats Simple Error: ' . $e->getMessage(), 'SYSTEM');
            $this->setAPIResponse('error', 'Failed to retrieve simple watch statistics: ' . $e->getMessage(), 500);
            return false;
        }
    }
}
