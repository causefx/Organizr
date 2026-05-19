<?php

trait KomgaHomepageItem
{
    public function komgaSettingsArray($infoOnly = false)
    {
        $homepageInformation = [
            'name' => 'Komga',
            'enabled' => true,
            'image' => 'plugins/images/komga.svg',
            'category' => 'Entertainment',
            'settingsArray' => __FUNCTION__
        ];
        if ($infoOnly) {
            return $homepageInformation;
        }
        $homepageSettings = [
            'debug' => true,
            'settings' => [
                'Enable' => [
                    $this->settingsOption('enable', 'homepageKomgaEnabled', ['label' => 'Activate Komga', 'help' => 'Display the Komga module on the home page']),
                    $this->settingsOption('auth', 'homepageKomgaAuth', ['label' => 'Authentification']),
                ],
            ]
        ];
        return array_merge($homepageInformation, $homepageSettings);
    }

    public function komgaHomepagePermissions($key = null)
    {
        $permissions = [
            'main' => [
                'enabled' => [
                    'homepageKomgaEnabled'
                ],
                'auth' => [
                    'homepageKomgaAuth'
                ]
            ]
        ];
        return $this->homepageCheckKeyPermissions($key, $permissions);
    }

    public function homepageOrderKomga()
    {
        if ($this->homepageItemPermissions($this->komgaHomepagePermissions('main'))) {

            return '
				<div id="' . __FUNCTION__ . '">
					<div id="komgaLatestBookContainer" class="homepage-item" data-id="KOMGA">
						<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Komga Books...</h2></div>
					</div>
				</div>
				';
        }
    }

}