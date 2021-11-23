<?php

trait BookmarksHomepageItem
{
	public function bookmarksSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Bookmarks',
			'enabled' => true,
			'image' => 'plugins/images/bookmark.png',
			'category' => 'Links',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'Enable' => [
					$this->settingsOption('enable', 'homepageBookmarksEnabled'),
					$this->settingsOption('auth', 'homepageBookmarksAuth'),
				],
				'Options' => [
					$this->settingsOption('title', 'homepageBookmarksHeader'),
					$this->settingsOption('toggle-title', 'homepageBookmarksHeaderToggle'),
					$this->settingsOption('enable', 'homepageBookmarksEnabled', ['label' => 'Enable Bookmarks', 'help' => 'Toggles the view module for Bookmarks']),
					$this->settingsOption('refresh', 'homepageBookmarksRefresh'),
				],
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function bookmarksHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageBookmarksEnabled'
				],
				'auth' => [
					'homepageBookmarksAuth'
				]
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}
	
	public function homepageOrderBookmarks()
	{
		if ($this->homepageItemPermissions($this->bookmarksHomepagePermissions('main'))) {

			return '
				<div id="' . __FUNCTION__ . '">
					<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Bookmarks...</h2></div>
					<script>
						// Bookmarks And Air
						homepageBookmarks("' . $this->config['homepageBookmarksRefresh'] . '");
						// End Bookmarks And Air
					</script>
				</div>
				';
		}
	}

}