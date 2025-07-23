<?php

/**
 * User Watch Statistics Homepage Plugin
 * Provides comprehensive user watching statistics from Plex/Emby/Jellyfin
 */

trait HomepageUserWatchStats
{
    /**
     * Get user watch statistics data
     */
    public function userWatchStatsHomepagePermissions($user)
    {
        // Check if user has access to statistics
        if ($user['groupID'] == 0 || $user['groupID'] == 1) {
            return true;
        }
        return false;
    }

    /**
     * Main function to get watch statistics
     */
    public function getUserWatchStats($options = null)
    {
        if (!$this->homepageItemPermissions($this->userWatchStatsHomepagePermissions($this->user), true)) {
            return false;
        }

        try {
            $mediaServer = $this->config['homepageUserWatchStatsService'] ?? 'plex';
            $days = intval($options['days'] ?? 30);
            
            switch (strtolower($mediaServer)) {
                case 'plex':
                    return $this->getPlexWatchStats($days);
                case 'emby':
                    return $this->getEmbyWatchStats($days);
                case 'jellyfin':
                    return $this->getJellyfinWatchStats($days);
                default:
                    return $this->getPlexWatchStats($days);
            }
        } catch (Exception $e) {
            $this->writeLog('error', 'User Watch Stats Error: ' . $e->getMessage(), 'SYSTEM');
            return [
                'error' => true,
                'message' => 'Failed to retrieve watch statistics'
            ];
        }
    }

    /**
     * Get Plex watch statistics via Tautulli API
     */
    private function getPlexWatchStats($days = 30)
    {
        $tautulliUrl = $this->config['plexURL'] ?? '';
        $tautulliToken = $this->config['plexToken'] ?? '';
        
        if (empty($tautulliUrl) || empty($tautulliToken)) {
            return ['error' => true, 'message' => 'Tautulli URL or API key not configured'];
        }

        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $stats = [
            'period' => "{$days} days",
            'start_date' => $startDate,
            'end_date' => $endDate,
            'most_watched' => $this->getTautulliMostWatched($tautulliUrl, $tautulliToken, $days),
            'least_watched' => $this->getTautulliLeastWatched($tautulliUrl, $tautulliToken, $days),
            'user_stats' => $this->getTautulliUserStats($tautulliUrl, $tautulliToken, $days),
            'recent_activity' => $this->getTautulliRecentActivity($tautulliUrl, $tautulliToken),
            'top_users' => $this->getTautulliTopUsers($tautulliUrl, $tautulliToken, $days)
        ];

        return $stats;
    }

    /**
     * Get most watched content from Tautulli
     */
    private function getTautulliMostWatched($url, $token, $days)
    {
        $endpoint = rtrim($url, '/') . '/api/v2';
        $params = [
            'apikey' => $token,
            'cmd' => 'get_home_stats',
            'time_range' => $days,
            'stats_type' => 'plays',
            'stats_count' => 10
        ];
        
        $response = $this->guzzle->request('GET', $endpoint, [
            'query' => $params,
            'timeout' => 15,
            'connect_timeout' => 15,
            'http_errors' => false
        ]);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), true);
            return $data['response']['data'] ?? [];
        }

        return [];
    }

    /**
     * Get user statistics from Tautulli
     */
    private function getTautulliUserStats($url, $token, $days)
    {
        $endpoint = rtrim($url, '/') . '/api/v2';
        $params = [
            'apikey' => $token,
            'cmd' => 'get_user_watch_time_stats',
            'time_range' => $days
        ];
        
        $response = $this->guzzle->request('GET', $endpoint, [
            'query' => $params,
            'timeout' => 15,
            'connect_timeout' => 15,
            'http_errors' => false
        ]);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), true);
            return $data['response']['data'] ?? [];
        }

        return [];
    }

    /**
     * Get top users from Tautulli
     */
    private function getTautulliTopUsers($url, $token, $days)
    {
        $endpoint = rtrim($url, '/') . '/api/v2';
        $params = [
            'apikey' => $token,
            'cmd' => 'get_users',
            'length' => 25
        ];
        
        $response = $this->guzzle->request('GET', $endpoint, [
            'query' => $params,
            'timeout' => 15,
            'connect_timeout' => 15,
            'http_errors' => false
        ]);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), true);
            $users = $data['response']['data']['data'] ?? [];
            
            // Sort by play count
            usort($users, function($a, $b) {
                return ($b['play_count'] ?? 0) - ($a['play_count'] ?? 0);
            });
            
            return array_slice($users, 0, 10);
        }

        return [];
    }

    /**
     * Get recent activity from Tautulli
     */
    private function getTautulliRecentActivity($url, $token)
    {
        $endpoint = rtrim($url, '/') . '/api/v2';
        $params = [
            'apikey' => $token,
            'cmd' => 'get_recently_added',
            'count' => 10
        ];
        
        $response = $this->guzzle->request('GET', $endpoint, [
            'query' => $params,
            'timeout' => 15,
            'connect_timeout' => 15,
            'http_errors' => false
        ]);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), true);
            return $data['response']['data']['recently_added'] ?? [];
        }

        return [];
    }

    /**
     * Get least watched content (inverse of most watched)
     */
    private function getTautulliLeastWatched($url, $token, $days)
    {
        $endpoint = rtrim($url, '/') . '/api/v2';
        $params = [
            'apikey' => $token,
            'cmd' => 'get_libraries',
        ];
        
        $response = $this->guzzle->request('GET', $endpoint, [
            'query' => $params,
            'timeout' => 15,
            'connect_timeout' => 15,
            'http_errors' => false
        ]);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), true);
            $libraries = $data['response']['data'] ?? [];
            
            $leastWatched = [];
            foreach ($libraries as $library) {
                $libraryStats = $this->getTautulliLibraryStats($url, $token, $library['section_id'], $days);
                if (!empty($libraryStats)) {
                    $leastWatched = array_merge($leastWatched, array_slice($libraryStats, -10));
                }
            }
            
            return $leastWatched;
        }

        return [];
    }

    /**
     * Get library statistics for least watched calculation
     */
    private function getTautulliLibraryStats($url, $token, $sectionId, $days)
    {
        $endpoint = rtrim($url, '/') . '/api/v2';
        $params = [
            'apikey' => $token,
            'cmd' => 'get_library_media_info',
            'section_id' => $sectionId,
            'length' => 50,
            'order_column' => 'play_count',
            'order_dir' => 'asc'
        ];
        
        $response = $this->guzzle->request('GET', $endpoint, [
            'query' => $params,
            'timeout' => 15,
            'connect_timeout' => 15,
            'http_errors' => false
        ]);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), true);
            return $data['response']['data']['data'] ?? [];
        }

        return [];
    }

    /**
     * Get Emby watch statistics
     */
    private function getEmbyWatchStats($days = 30)
    {
        $embyUrl = $this->config['embyURL'] ?? '';
        $embyToken = $this->config['embyToken'] ?? '';
        
        if (empty($embyUrl) || empty($embyToken)) {
            return ['error' => true, 'message' => 'Emby URL or API key not configured'];
        }

        // Implement Emby-specific statistics gathering
        return $this->getGenericMediaServerStats('emby', $embyUrl, $embyToken, $days);
    }

    /**
     * Get Jellyfin watch statistics
     */
    private function getJellyfinWatchStats($days = 30)
    {
        $jellyfinUrl = $this->config['jellyfinURL'] ?? '';
        $jellyfinToken = $this->config['jellyfinToken'] ?? '';
        
        if (empty($jellyfinUrl) || empty($jellyfinToken)) {
            return ['error' => true, 'message' => 'Jellyfin URL or API key not configured'];
        }

        // Implement Jellyfin-specific statistics gathering
        return $this->getGenericMediaServerStats('jellyfin', $jellyfinUrl, $jellyfinToken, $days);
    }

    /**
     * Generic media server stats for Emby/Jellyfin
     */
    private function getGenericMediaServerStats($type, $url, $token, $days)
    {
        // Basic structure for now - can be expanded based on Emby/Jellyfin APIs
        return [
            'period' => "{$days} days",
            'start_date' => date('Y-m-d', strtotime("-{$days} days")),
            'end_date' => date('Y-m-d'),
            'message' => ucfirst($type) . ' statistics coming soon',
            'most_watched' => [],
            'least_watched' => [],
            'user_stats' => [],
            'recent_activity' => [],
            'top_users' => []
        ];
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

    /**
     * Get user avatar URL
     */
    private function getUserAvatar($userId, $mediaServer = 'plex')
    {
        switch ($mediaServer) {
            case 'plex':
                return $this->getPlexUserAvatar($userId);
            case 'emby':
                return $this->getEmbyUserAvatar($userId);
            case 'jellyfin':
                return $this->getJellyfinUserAvatar($userId);
            default:
                return '/plugins/images/organizr/user-bg.png';
        }
    }

    /**
     * Get Plex user avatar
     */
    private function getPlexUserAvatar($userId)
    {
        $tautulliUrl = $this->config['plexURL'] ?? '';
        $tautulliToken = $this->config['plexToken'] ?? '';
        
        if (empty($tautulliUrl) || empty($tautulliToken)) {
            return '/plugins/images/organizr/user-bg.png';
        }

        $endpoint = rtrim($tautulliUrl, '/') . "/api/v2";
        $params = [
            'apikey' => $tautulliToken,
            'cmd' => 'get_user_thumb',
            'user_id' => $userId
        ];

        try {
            $response = $this->guzzle->request('GET', $endpoint, [
                'query' => $params,
                'timeout' => 10,
                'connect_timeout' => 10,
                'http_errors' => false
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                return $data['response']['data']['thumb'] ?? '/plugins/images/organizr/user-bg.png';
            }
        } catch (Exception $e) {
            // Return default avatar on error
        }

        return '/plugins/images/organizr/user-bg.png';
    }

    /**
     * Get Emby user avatar
     */
    private function getEmbyUserAvatar($userId)
    {
        // Implement Emby avatar logic
        return '/plugins/images/organizr/user-bg.png';
    }

    /**
     * Get Jellyfin user avatar
     */
    private function getJellyfinUserAvatar($userId)
    {
        // Implement Jellyfin avatar logic
        return '/plugins/images/organizr/user-bg.png';
    }
}