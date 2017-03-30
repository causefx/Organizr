<?php

namespace Kryptonit3\CouchPotato;

use GuzzleHttp\Client;
use Kryptonit3\CouchPotato\Exceptions\InvalidException;

class CouchPotato
{
    protected $url;
    protected $apiKey;
    protected $httpAuthUsername;
    protected $httpAuthPassword;

    public function __construct($url, $apiKey, $httpAuthUsername = null, $httpAuthPassword = null)
    {
        $this->url = rtrim($url, '/\\'); // Example: http://127.0.0.1:5050 (no trailing forward-backward slashes)
        $this->apiKey = $apiKey;
        $this->httpAuthUsername = $httpAuthUsername;
        $this->httpAuthPassword = $httpAuthPassword;
    }

    /**
     * Check if app available.
     *
     * @return string
     * @throws InvalidException
     */
    public function getAppAvailable()
    {
        $uri = 'app.available';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Restart the app.
     *
     * @return string
     * @throws InvalidException
     */
    public function getAppRestart()
    {
        $uri = 'app.restart';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Shutdown the app.
     *
     * @return string
     * @throws InvalidException
     */
    public function getAppShutdown()
    {
        $uri = 'app.shutdown';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Get version.
     *
     * @return string
     * @throws InvalidException
     */
    public function getAppVersion()
    {
        $uri = 'app.version';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * List all available categories
     *
     * @return string
     * @throws InvalidException
     */
    public function getCategoryList()
    {
        $uri = 'category.list';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Return the directory list of a given directory
     *
     * @param null $path
     * @param bool|true $showHidden
     * @return string
     * @throws InvalidException
     */
    public function getDirectoryList($path = null, $showHidden = true)
    {
        $uri = 'directory.list';
        $uriData = [];

        if ($path) {
            $uriData['path'] = $path;
        }
        if ($showHidden) {
            $uriData['show_hidden'] = true;
        }

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Remove all the log files
     *
     * @return string
     * @throws InvalidException
     */
    public function getLoggingClear()
    {
        $uri = 'logging.clear';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Get the full log file by number
     *
     * @param int $nr Number of the log to get.
     * @return string
     * @throws InvalidException
     */
    public function getLoggingGet($nr = 0)
    {
        $uri = 'logging.get';
        $uriData = [
            'nr' => $nr
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * @param string $type Type of logging, default "error" | info, debug
     * @param array $args All other params will be printed in the log string.
     * @return string
     * @throws InvalidException
     */
    public function getLoggingLog($type = 'error', array $args)
    {
        $uri = 'logging.log';
        $uriData = [
            'type' => $type,
            'args' => array_flatten($args)
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Get a partial log
     *
     * @param int $lines Number of lines. Last to first. Default 30
     * @param string $type Type of log | all(default), error, info, debug
     * @return string
     * @throws InvalidException
     */
    public function getLoggingPartial($lines = 30, $type = 'all')
    {
        $uri = 'logging.partial';
        $uriData = [
            'lines' => $lines,
            'type' => $type
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Get the progress of current manage update
     *
     * @return string
     * @throws InvalidException
     */
    public function getManageProgress()
    {
        $uri = 'manage.progress';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Update the library by scanning for new movies
     *
     * @param bool|false $full Do a full update or just recently changed/added movies.
     * @return string
     * @throws InvalidException
     */
    public function getManageUpdate($full = false)
    {
        $uri = 'manage.update';
        $uriData = [];
        if ($full) {
            $uriData['full'] = true;
        }

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Delete a media from the wanted list
     *
     * @param int $id media ID(s) you want to delete. (comma separated)
     * @param string $deleteFrom all (default), wanted, manage
     * @return string
     * @throws InvalidException
     */
    public function getMediaDelete($id, $deleteFrom = 'all')
    {
        $uri = 'media.delete';
        $uriData = [
            'id' => $id,
            'delete_from' => $deleteFrom
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Get media by id
     *
     * @param int $id The id of the media
     * @return string
     * @throws InvalidException
     */
    public function getMediaGet($id)
    {
        $uri = 'media.get';
        $uriData = [
            'id' => $id
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * List media
     *
     * OPTIONAL PARAMETERS
     * status (array or csv) Filter media by status. Example:"active,done"
     * search (string) Search media title
     * release_status (array or csv) Filter media by status of its releases. Example:"snatched,available"
     * limit_offset (string) Limit and offset the media list. Examples: "50" or "50,30"
     * type (string) Media type to filter on.
     * starts_with (string) Starts with these characters. Example: "a" returns all media starting with the letter "a"
     *
     * @param array $params
     * @return string
     * @throws InvalidException
     */
    public function getMediaList(array $params = [])
    {
        $uri = 'media.list';
        $uriData = [];

        if (array_key_exists('status', $params)) {
            $uriData['status'] = $params['status'];
        }
        if (array_key_exists('search', $params)) {
            $uriData['search'] = $params['search'];
        }
        if (array_key_exists('release_status', $params)) {
            $uriData['release_status'] = $params['release_status'];
        }
        if (array_key_exists('limit_offset', $params)) {
            $uriData['limit_offset'] = $params['limit_offset'];
        }
        if (array_key_exists('type', $params)) {
            $uriData['type'] = $params['type'];
        }
        if (array_key_exists('starts_with', $params)) {
            $uriData['starts_with'] = $params['starts_with'];
        }

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Refresh a any media type by ID
     *
     * @param int $id Movie, Show, Season or Episode ID(s) you want to refresh. (comma separated)
     * @return string
     * @throws InvalidException
     */
    public function getMediaRefresh($id)
    {
        $uri = 'media.refresh';
        $uriData = [
            'id' => $id
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Add new movie to the wanted list
     *
     * PARAMETERS
     * profile_id (string) ID of quality profile you want the add the movie in. If empty will use the default profile.
     * title (string) Movie title to use for searches. Has to be one of the titles returned by movie.search.
     * identifier (string) IMDB id of the movie your want to add.
     * category_id (string) ID of category you want the add the movie in. If empty will use no category.
     * force_readd (string) Force re-add even if movie already in wanted or manage. Default: True
     *
     * @param array $params
     * @return string
     * @throws InvalidException
     */
    public function getMovieAdd(array $params)
    {
        $uri = 'movie.add';
        $uriData = [];

        if (array_key_exists('profile_id', $params)) {
            $uriData['profile_id'] = $params['profile_id'];
        }
        if (array_key_exists('title', $params)) {
            $uriData['title'] = $params['title'];
        }
        if (array_key_exists('identifier', $params)) {
            $uriData['identifier'] = $params['identifier'];
        }
        if (array_key_exists('category_id', $params)) {
            $uriData['category_id'] = $params['category_id'];
        }
        if (array_key_exists('force_readd', $params)) {
            $uriData['force_readd'] = $params['force_readd'];
        }

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Delete a movie from the wanted list
     *
     * @param int $id Movie ID(s) you want to delete. (comma separated)
     * @param string $deleteFrom Delete movie from this page all (default), wanted, manage
     * @return string
     * @throws InvalidException
     */
    public function getMovieDelete($id, $deleteFrom = 'all')
    {
        $uri = 'movie.delete';
        $uriData = [
            'id' => $id,
            'delete_from' => $deleteFrom
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Edit Movies
     *
     * PARAMETERS
     * profile_id (string) ID of quality profile you want the edit the movie to.
     * default_title (string) Movie title to use for searches. Has to be one of the titles returned by movie.search.
     * id (string) Movie ID(s) you want to edit. (comma separated)
     * category_id (string) ID of category you want the add the movie in. If empty will use no category.
     *
     * @param array $params
     * @return string
     * @throws InvalidException
     */
    public function getMovieEdit(array $params)
    {
        $uri = 'movie.edit';
        $uriData = [];

        if (array_key_exists('profile_id', $params)) {
            $uriData['profile_id'] = $params['profile_id'];
        }
        if (array_key_exists('default_title', $params)) {
            $uriData['default_title'] = $params['default_title'];
        }
        if (array_key_exists('id', $params)) {
            $uriData['id'] = $params['id'];
        }
        if (array_key_exists('category_id', $params)) {
            $uriData['category_id'] = $params['category_id'];
        }

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * List Movies
     *
     * OPTIONAL PARAMETERS
     * status (array or csv) Filter movie by status. Example:"active,done"
     * search (string) Search movie title
     * release_status (array or csv) Filter movie by status of its releases. Example:"snatched,available"
     * limit_offset (string) Limit and offset the movie list. Examples: "50" or "50,30"
     * starts_with (string) Starts with these characters. Example: "a" returns all movies starting with the letter "a"
     *
     * @param array $params
     * @return string
     * @throws InvalidException
     */
    public function getMovieList(array $params = [])
    {
        $uri = 'movie.list';
        $uriData = [];

        if (array_key_exists('status', $params)) {
            $uriData['status'] = $params['status'];
        }
        if (array_key_exists('search', $params)) {
            $uriData['search'] = $params['search'];
        }
        if (array_key_exists('release_status', $params)) {
            $uriData['release_status'] = $params['release_status'];
        }
        if (array_key_exists('limit_offset', $params)) {
            $uriData['limit_offset'] = $params['limit_offset'];
        }
        if (array_key_exists('starts_with', $params)) {
            $uriData['starts_with'] = $params['starts_with'];
        }

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Starts a full search for all wanted movies
     *
     * @return string
     * @throws InvalidException
     */
    public function getMovieSearcherFull()
    {
        $uri = 'movie.searcher.full_search';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Get the progress of current full search
     *
     * @return string
     * @throws InvalidException
     */
    public function getMovieSearcherProgress()
    {
        $uri = 'movie.searcher.progress';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Marks the snatched results as ignored and try the next best release
     *
     * @param int $id The id of the media
     * @return string
     * @throws InvalidException
     */
    public function getMovieSearcherTryNext($id)
    {
        $uri = 'movie.searcher.try_next';
        $uriData = [
            'id' => $id
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Get list of notifications
     *
     * @param string|null $limit Limit and offset the notification list. Examples: "50" or "50,30"
     * @return string
     * @throws InvalidException
     */
    public function getNotificationList($limit = null)
    {
        $uri = 'notification.list';
        $uriData = [];
        if ( $limit ) { $uriData['limit_offset'] = $limit; }

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Mark notifications as read
     *
     * @param int $id Notification id you want to mark as read. All if ids is empty.
     * @return string
     * @throws InvalidException
     */
    public function getNotificationMarkRead($id = null)
    {
        $uri = 'notification.markread';
        $uriData = [];
        if ( $id ) { $uriData['id'] = $id; }

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * List all available profiles
     *
     * @return string
     * @throws InvalidException
     */
    public function getProfileList()
    {
        $uri = 'profile.list';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * List all available qualities
     *
     * @return string
     * @throws InvalidException
     */
    public function getQualityList()
    {
        $uri = 'quality.list';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Delete releases
     *
     * @param int $id ID of the release object in release-table
     * @return string
     * @throws InvalidException
     */
    public function getReleaseDelete($id)
    {
        $uri = 'release.delete';
        $uriData = [
            'id' => $id
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Toggle ignore, for bad or wrong releases
     *
     * @param int $id ID of the release object in release-table
     * @return string
     * @throws InvalidException
     */
    public function getReleaseIgnore($id)
    {
        $uri = 'release.ignore';
        $uriData = [
            'id' => $id
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Send a release manually to the downloaders
     *
     * @param int $id ID of the release object in release-table
     * @return string
     * @throws InvalidException
     */
    public function getReleaseManualDownload($id)
    {
        $uri = 'release.manual_download';
        $uriData = [
            'id' => $id
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Get the progress of current renamer scan
     *
     * @return string
     * @throws InvalidException
     */
    public function getRenamerProgress()
    {
        $uri = 'renamer.progress';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * For the renamer to check for new files to rename in a folder
     *
     * OPTIONAL PARAMETERS
     * files (string) Provide the release files if more releases are in the same media_folder, delimited with a '|'. Note that no dedicated release folder is expected for releases with one file.
     * base_folder (string) The folder to find releases in. Leave empty for default folder.
     * download_id (string) The nzb/torrent ID of the release in media_folder. 'downloader' is required with this option.
     * status (string) The status of the release: 'completed' (default) or 'seeding'
     * to_folder (string) The folder to move releases to. Leave empty for default folder.
     * async (int) Set to 1 if you dont want to fire the renamer.scan asynchronous.
     * media_folder (string) The folder of the media to scan. Keep empty for default renamer folder.
     * downloader (string) The downloader the release has been downloaded with. 'download_id' is required with this option.
     *
     * @param array $params
     * @return string
     * @throws InvalidException
     */
    public function getRenamerScan(array $params = [])
    {
        $uri = 'renamer.scan';
        $uriData = [];

        if (array_key_exists('files', $params)) {
            $uriData['files'] = $params['files'];
        }
        if (array_key_exists('base_folder', $params)) {
            $uriData['base_folder'] = $params['base_folder'];
        }
        if (array_key_exists('download_id', $params)) {
            $uriData['download_id'] = $params['download_id'];
        }
        if (array_key_exists('status', $params)) {
            $uriData['status'] = $params['status'];
        }
        if (array_key_exists('to_folder', $params)) {
            $uriData['to_folder'] = $params['to_folder'];
        }
        if (array_key_exists('async', $params)) {
            $uriData['async'] = $params['async'];
        }
        if (array_key_exists('media_folder', $params)) {
            $uriData['media_folder'] = $params['media_folder'];
        }
        if (array_key_exists('downloader', $params)) {
            $uriData['downloader'] = $params['downloader'];
        }

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Search the info in providers for a movie
     *
     * @param string $query The (partial) movie name you want to search for
     * @param string|null $type Search for a specific media type. Leave empty to search all.
     * @return string
     * @throws InvalidException
     */
    public function getSearch($query, $type = null)
    {
        $uri = 'search';
        $uriData = [
            'q' => $query
        ];
        if ( $type ) { $uriData['type'] = $type; }

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Starts a full search for all media
     *
     * @return string
     * @throws InvalidException
     */
    public function getSearcherFull()
    {
        $uri = 'searcher.full_search';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Get the progress of all media searches
     *
     * @return string
     * @throws InvalidException
     */
    public function getSearcherProgress()
    {
        $uri = 'searcher.progress';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Return the options and its values of settings.conf.
     * Including the default values and group ordering used on the settings page.
     *
     * @return string
     * @throws InvalidException
     */
    public function getSettings()
    {
        $uri = 'settings';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Save setting to config file (settings.conf)
     *
     * @param string $section The section name in settings.conf
     * @param string $name The option name
     * @param  string $value The value you want to save
     * @return string
     * @throws InvalidException
     */
    public function getSettingsSave($section, $name, $value)
    {
        $uri = 'settings.save';
        $uriData = [
            'section' => $section,
            'name' => $name,
            'value' => $value
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Check for available update
     *
     * @return string
     * @throws InvalidException
     */
    public function getUpdaterCheck()
    {
        $uri = 'updater.check';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Get updater information
     *
     * @return string
     * @throws InvalidException
     */
    public function getUpdaterInfo()
    {
        $uri = 'updater.info';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch (\Exception $e) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Process requests with Guzzle
     *
     * @param array $params
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function _request(array $params)
    {
        $client = new Client();

        if ( $params['type'] == 'get' ) {
            $url = $this->url . '/api/' . $this->apiKey . '/' . $params['uri'] . '?' . http_build_query($params['data']);
            $options = [];
            if ( $this->httpAuthUsername && $this->httpAuthPassword ) {
                $options['auth'] = [
                    $this->httpAuthUsername,
                    $this->httpAuthPassword
                ];
            }

            return $client->get($url, $options);
        }
    }
}