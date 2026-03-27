<?php
$GLOBALS['plugins']['Komga'] = array(
    'name' => 'Komga',
    'author' => 'JamesAdams',
    'category' => 'Entertainment',
    'link' => '',
    'license' => 'personal',
    'idPrefix' => 'KOMGA',
    'configPrefix' => 'KOMGA',
    'version' => '1.0.9',
    'image' => 'plugins/images/komga.svg',
    'settings' => true,
    'bind' => true,
    'api' => 'api/v2/plugins/komga/settings',
    'homepage' => true
);

class KomgaPlugin extends Organizr
{
    public function __construct()
    {
        parent::__construct();
    }

    public function _pluginGetSettings()
    {
        $libraries = [
            ['name' => 'All Libraries', 'value' => 'all']
        ];

        $url = $this->config['KOMGA-url'] ?? '';
        $apiKey = $this->config['KOMGA-apikey'] ?? '';

        if ($url && $apiKey) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, rtrim($url, '/') . '/api/v1/libraries');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-API-Key: $apiKey",
                "Accept: application/json"
            ]);
            // 2 second timeout so settings load doesn't hang forever
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            $response = curl_exec($ch);
            curl_close($ch);

            if ($response) {
                $data = json_decode($response, true);
                if (is_array($data) && !isset($data['status'])) {
                    // Komga might wrap in 'content' array
                    $items = isset($data['content']) ? $data['content'] : $data;
                    foreach ($items as $lib) {
                        if (isset($lib['id']) && isset($lib['name'])) {
                            $libraries[] = [
                                'name' => $lib['name'],
                                'value' => $lib['id']
                            ];
                        }
                    }
                }
            }
        }

        $groupsData = $this->getAllGroups();
        $groups = isset($groupsData['groups']) ? $groupsData['groups'] : $groupsData;
        $groupSettings = [];
        $groupLibraryOptions = array_merge([['name' => 'Default (Follow Global Setting)', 'value' => 'default']], $libraries);

        if (is_array($groups)) {
            foreach ($groups as $group) {
                $groupID = $group['group_id'] ?? $group['id'];
                $groupName = $group['group'] ?? $group['name'] ?? 'Group ' . $groupID;
                $groupSettings[] = array(
                    'type' => 'select2',
                    'class' => 'form-control',
                    'id' => 'komga-select-library-group-' . $groupID,
                    'name' => 'KOMGA-library-group-' . $groupID,
                    'label' => 'Library for ' . $groupName,
                    'value' => (string)($this->config['KOMGA-library-group-' . $groupID] ?? 'default'),
                    'options' => $groupLibraryOptions
                );
            }
        }

        return array(
            'Information' => array(
                    array(
                    'type' => 'html',
                    'label' => 'Description',
                    'html' => '<span lang="en">Configure your Komga server integration to display a tab of your recently added books on the Organizr homepage.</span>'
                )
            ),
            'Komga Settings' => array(
                    array(
                    'type' => 'select',
                    'name' => 'KOMGA-minAuth',
                    'label' => 'Minimum authentication to view component',
                    'value' => (string)($this->config['KOMGA-minAuth'] ?? '1'),
                    'options' => $this->groupSelect()
                ),
                    array(
                    'type' => 'input',
                    'name' => 'KOMGA-url',
                    'label' => 'Komga URL',
                    'placeholder' => 'ex: https://komga.domain.com',
                    'value' => (string)($this->config['KOMGA-url'] ?? '')
                ),
                    array(
                    'type' => 'password-alt',
                    'name' => 'KOMGA-apikey',
                    'label' => 'Komga API Key',
                    'value' => (string)($this->config['KOMGA-apikey'] ?? '')
                ),
                    array(
                    'type' => 'input',
                    'name' => 'KOMGA-title',
                    'label' => 'Homepage component title',
                    'value' => (string)($this->config['KOMGA-title'] ?? 'Recently added books')
                ),
                    array(
                    'type' => 'input',
                    'name' => 'KOMGA-tab-name',
                    'label' => 'Organizr Komga Tab Name',
                    'help' => 'This is the tab name that will be opened when clicking on a book.',
                    'placeholder' => 'ex: Komga-(Livres)',
                    'value' => (string)($this->config['KOMGA-tab-name'] ?? 'Komga')
                ),
                    array(
                    'type' => 'select2',
                    'class' => 'form-control',
                    'id' => 'komga-select-library',
                    'name' => 'KOMGA-libraries',
                    'label' => 'Global Specific Library',
                    'value' => (string)($this->config['KOMGA-libraries'] ?? 'all'),
                    'options' => $libraries
                )
            ),
            'Group Overrides' => $groupSettings
        );
    }
}