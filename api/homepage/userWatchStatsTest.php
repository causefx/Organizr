<?php

trait UserWatchStatsTestHomepageItem
{
    public function userWatchStatsTestSettingsArray($infoOnly = false)
    {
        $homepageInformation = [
            'name' => 'User Watch Statistics Test',
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
                    $this->settingsOption('enable', 'homepageUserWatchStatsTestEnabled'),
                    $this->settingsOption('auth', 'homepageUserWatchStatsTestAuth'),
                ],
                'Connection' => [
                    $this->settingsOption('url', 'plexURL', ['label' => 'Tautulli URL']),
                    $this->settingsOption('token', 'plexToken', ['label' => 'Tautulli API Key']),
                    $this->settingsOption('disable-cert-check', 'plexDisableCertCheck'),
                    $this->settingsOption('use-custom-certificate', 'plexUseCustomCertificate'),
                ],
                'Test Connection' => [
                    $this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
                    $this->settingsOption('test', 'userWatchStatsTest'),
                ]
            ]
        ];
        return array_merge($homepageInformation, $homepageSettings);
    }

    public function testConnectionUserWatchStatsTest()
    {
        if (!$this->homepageItemPermissions($this->userWatchStatsTestHomepagePermissions('test'), true)) {
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

    public function userWatchStatsTestHomepagePermissions($key = null)
    {
        $permissions = [
            'test' => [
                'enabled' => [
                    'homepageUserWatchStatsTestEnabled',
                ],
                'auth' => [
                    'homepageUserWatchStatsTestAuth',
                ],
                'not_empty' => [
                    'plexURL',
                    'plexToken'
                ]
            ],
            'main' => [
                'enabled' => [
                    'homepageUserWatchStatsTestEnabled'
                ],
                'auth' => [
                    'homepageUserWatchStatsTestAuth'
                ],
                'not_empty' => [
                    'plexURL',
                    'plexToken'
                ]
            ]
        ];
        return $this->homepageCheckKeyPermissions($key, $permissions);
    }

    public function homepageOrderUserWatchStatsTest()
    {
        if ($this->homepageItemPermissions($this->userWatchStatsTestHomepagePermissions('main'))) {
            return '
            <div id="' . __FUNCTION__ . '">
                <div class="white-box">
                    <div class="white-box-header">
                        <i class="fa fa-bar-chart"></i> User Watch Statistics Test
                    </div>
                    <div class="white-box-content">
                        <div class="row">
                            <div class="col-lg-12 text-center">
                                <h4>Test Plugin Working!</h4>
                                <p>This is a test version of the User Watch Statistics plugin.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            ';
        }
    }

    public function getUserWatchStatsTest($options = null)
    {
        if (!$this->homepageItemPermissions($this->userWatchStatsTestHomepagePermissions('main'), true)) {
            return false;
        }

        try {
            $stats = [
                'message' => 'Test plugin is working',
                'status' => 'success'
            ];
            
            $this->setAPIResponse('success', 'Test plugin loaded successfully', 200, $stats);
            return true;
            
        } catch (Exception $e) {
            $this->setAPIResponse('error', 'Test plugin error: ' . $e->getMessage(), 500);
            return false;
        }
    }
}
