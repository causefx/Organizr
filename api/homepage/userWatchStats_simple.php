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
}
