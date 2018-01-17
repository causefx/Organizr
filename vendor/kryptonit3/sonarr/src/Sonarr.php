<?php

namespace Kryptonit3\Sonarr;

use GuzzleHttp\Client;
use Kryptonit3\Sonarr\Exceptions\InvalidException;

class Sonarr
{
    protected $url;
    protected $apiKey;
    protected $httpAuthUsername;
    protected $httpAuthPassword;

    public function __construct($url, $apiKey, $httpAuthUsername = null, $httpAuthPassword = null)
    {
        $this->url = rtrim($url, '/\\'); // Example: http://127.0.0.1:8989 (no trailing forward-backward slashes)
        $this->apiKey = $apiKey;
        $this->httpAuthUsername = $httpAuthUsername;
        $this->httpAuthPassword = $httpAuthPassword;
    }

    /**
     * Gets upcoming episodes, if start/end are not supplied episodes airing today and tomorrow will be returned
     * When supplying start and/or end date you must supply date in format yyyy-mm-dd
     * Example: $sonarr->getCalendar('2015-01-25', '2016-01-15');
     * 'start' and 'end' not required. You may supply, one or both.
     *
     * @param string|null $start
     * @param string|null $end
     * @return array|object|string
     * @throws InvalidException
     */
    public function getCalendar($start = null, $end = null, $sonarrUnmonitored = 'false')
    {
        $uriData = [];

        if ( $start ) {
            if ( $this->validateDate($start) ) {
                $uriData['start'] = $start;
            } else {
                throw new InvalidException('Start date string was not recognized as a valid DateTime. Format must be yyyy-mm-dd.');
            }
        }
        if ( $end ) {
            if ( $this->validateDate($end) ) {
                $uriData['end'] = $end;
            } else {
                throw new InvalidException('End date string was not recognized as a valid DateTime. Format must be yyyy-mm-dd.');
            }
        }
        if ( $sonarrUnmonitored == 'true' ) {
            $uriData['unmonitored'] = 'true';
            }
            
        try {
            $response = $this->_request(
                [
                    'uri' => 'calendar',
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Queries the status of a previously started command, or all currently started commands.
     *
     * @param null $id Unique ID of the command
     * @return array|object|string
     * @throws InvalidException
     */
    public function getCommand($id = null)
    {
        $uri = ($id) ? 'command/' . $id : 'command';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Publish a new command for Sonarr to run.
     * These commands are executed asynchronously; use GET to retrieve the current status.
     *
     * Commands and their parameters can be found here:
     * https://github.com/Sonarr/Sonarr/wiki/Command#commands
     *
     * @param $name
     * @param array|null $params
     * @return string
     * @throws InvalidException
     */
    public function postCommand($name, array $params = null)
    {
        $uri = 'command';
        $uriData = [
            'name' => $name
        ];

        if ( array_key_exists('seriesId', $params) ) { $uriData['seriesId'] = $params['seriesId']; }
        if ( array_key_exists('episodeIds', $params) ) { $uriData['episodeIds'] = $params['episodeIds']; }
        if ( array_key_exists('seasonNumber', $params) ) { $uriData['seasonNumber'] = $params['seasonNumber']; }
        if ( array_key_exists('path', $params) ) { $uriData['path'] = $params['path']; }
        if ( array_key_exists('downloadClientId', $params) ) { $uriData['downloadClientId'] = $params['downloadClientId']; }
        if ( array_key_exists('files', $params) ) { $uriData['files'] = $params['files']; }
        if ( array_key_exists('seriesIds', $params) ) { $uriData['seriesIds'] = $params['seriesIds']; }

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'post',
                    'data' => $uriData
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Gets Diskspace
     *
     * @return array|object|string
     * @throws InvalidException
     */
    public function getDiskspace()
    {
        $uri = 'diskspace';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Returns all episodes for the given series
     *
     * @param $seriesId
     * @return array|object|string
     * @throws InvalidException
     */
    public function getEpisodes($seriesId)
    {
        $uri = 'episode';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => [
                        'SeriesId' => $seriesId
                    ]
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Returns the episode with the matching id
     *
     * @param $id
     * @return string
     * @throws InvalidException
     */
    public function getEpisode($id)
    {
        $uri = 'episode';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri . '/' . $id,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Update the given episodes, currently only monitored is changed, all other modifications are ignored.
     *
     * Required: All parameters; You should perform a getEpisode(id)
     * and submit the full body with the changes, as other values may be editable in the future.
     *
     * @param array $data
     * @return string
     * @throws InvalidException
     */
    public function updateEpisode(array $data)
    {
        $uri = 'episode';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'put',
                    'data' => $data
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Returns all episode files for the given series
     *
     * @param $seriesId
     * @return array|object|string
     * @throws InvalidException
     */
    public function getEpisodeFiles($seriesId)
    {
        $uri = 'episodefile';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => [
                        'SeriesId' => $seriesId
                    ]
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Returns the episode file with the matching id
     *
     * @param $id
     * @return string
     * @throws InvalidException
     */
    public function getEpisodeFile($id)
    {
        $uri = 'episodefile';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri . '/' . $id,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Delete the given episode file
     *
     * @param $id
     * @return string
     * @throws InvalidException
     */
    public function deleteEpisodeFile($id)
    {
        $uri = 'episodefile';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri . '/' . $id,
                    'type' => 'delete',
                    'data' => []
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Gets history (grabs/failures/completed).
     *
     * @param int $page Page Number
     * @param int $pageSize Results per Page
     * @param string $sortKey 'series.title' or 'date'
     * @param string $sortDir 'asc' or 'desc'
     * @return array|object|string
     * @throws InvalidException
     */
    public function getHistory($page = 1, $pageSize = 10, $sortKey = 'series.title', $sortDir = 'asc')
    {
        $uri = 'history';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => [
                        'page' => $page,
                        'pageSize' => $pageSize,
                        'sortKey' => $sortKey,
                        'sortDir' => $sortDir
                    ]
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Gets missing episode (episodes without files).
     *
     * @param int $page Page Number
     * @param int $pageSize Results per Page
     * @param string $sortKey 'series.title' or 'airDateUtc'
     * @param string $sortDir 'asc' or 'desc'
     * @return array|object|string
     * @throws InvalidException
     */
    public function getWantedMissing($page = 1, $pageSize = 10, $sortKey = 'series.title', $sortDir = 'asc')
    {
        $uri = 'wanted/missing';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => [
                        'page' => $page,
                        'pageSize' => $pageSize,
                        'sortKey' => $sortKey,
                        'sortDir' => $sortDir
                    ]
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Displays currently downloading info
     *
     * @return array|object|string
     * @throws InvalidException
     */
    public function getQueue()
    {
        $uri = 'queue';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Gets all quality profiles
     *
     * @return array|object|string
     * @throws InvalidException
     */
    public function getProfiles()
    {
        $uri = 'profile';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Get release by episode id
     *
     * @param $episodeId
     * @return string
     * @throws InvalidException
     */
    public function getRelease($episodeId)
    {
        $uri = 'release';
        $uriData = [
            'episodeId' => $episodeId
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Adds a previously searched release to the download client,
     * if the release is still in Sonarr's search cache (30 minute cache).
     * If the release is not found in the cache Sonarr will return a 404.
     *
     * @param $guid
     * @return string
     * @throws InvalidException
     */
    public function postRelease($guid)
    {
        $uri = 'release';
        $uriData = [
            'guid' => $guid
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'post',
                    'data' => $uriData
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Push a release to download client
     *
     * @param $title
     * @param $downloadUrl
     * @param $downloadProtocol (Usenet or Torrent)
     * @param $publishDate (ISO8601 Date)
     * @return string
     * @throws InvalidException
     */
    public function postReleasePush($title, $downloadUrl, $downloadProtocol, $publishDate)
    {
        $uri = 'release';
        $uriData = [
            'title' => $title,
            'downloadUrl' => $downloadUrl,
            'downloadProtocol' => $downloadProtocol,
            'publishDate' => $publishDate
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'post',
                    'data' => $uriData
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Gets root folder
     *
     * @return array|object|string
     * @throws InvalidException
     */
    public function getRootFolder()
    {
        $uri = 'rootfolder';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Returns all series in your collection
     *
     * @return array|object|string
     * @throws InvalidException
     */
    public function getSeries()
    {
        $uri = 'series';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Adds a new series to your collection
     *
     * NOTE: if you do not add the required params, then the series wont function.
     * Some of these without the others can indeed make a "series". But it wont function properly in Sonarr.
     *
     * Required: tvdbId (int) title (string) qualityProfileId (int) titleSlug (string) seasons (array)
     * See GET output for format
     *
     * path (string) - full path to the series on disk or rootFolderPath (string)
     * Full path will be created by combining the rootFolderPath with the series title
     *
     * Optional: tvRageId (int) seasonFolder (bool) monitored (bool)
     *
     * @param array $data
     * @param bool|true $onlyFutureEpisodes It can be used to control which episodes Sonarr monitors
     * after adding the series, setting to true (default) will only monitor future episodes.
     *
     * @return array|object|string
     * @throws InvalidException
     */
    public function postSeries(array $data, $onlyFutureEpisodes = true)
    {
        $uri = 'series';
        $uriData = [];

        // Required
        $uriData['tvdbId'] = $data['tvdbId'];
        $uriData['title'] = $data['title'];
        $uriData['qualityProfileId'] = $data['qualityProfileId'];

        if ( array_key_exists('titleSlug', $data) ) { $uriData['titleSlug'] = $data['titleSlug']; }
        if ( array_key_exists('seasons', $data) ) { $uriData['seasons'] = $data['seasons']; }
        if ( array_key_exists('path', $data) ) { $uriData['path'] = $data['path']; }
        if ( array_key_exists('rootFolderPath', $data) ) { $uriData['rootFolderPath'] = $data['rootFolderPath']; }
        if ( array_key_exists('tvRageId', $data) ) { $uriData['tvRageId'] = $data['tvRageId']; }
        $uriData['seasonFolder'] = ( array_key_exists('seasonFolder', $data) ) ? $data['seasonFolder'] : true;
        if ( array_key_exists('monitored', $data) ) { $uriData['monitored'] = $data['monitored']; }
        if ( $onlyFutureEpisodes ) {
            $uriData['addOptions'] = [
                'ignoreEpisodesWithFiles' => true,
                'ignoreEpisodesWithoutFiles' => true
            ];
        }

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'post',
                    'data' => $uriData
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Delete the series with the given ID
     *
     * @param int $id
     * @param bool|true $deleteFiles
     * @return string
     * @throws InvalidException
     */
    public function deleteSeries($id, $deleteFiles = true)
    {
        $uri = 'series';
        $uriData = [];
        $uriData['deleteFiles'] = ($deleteFiles) ? 'true' : 'false';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri . '/' . $id,
                    'type' => 'delete',
                    'data' => $uriData
                ]


            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Searches for new shows on trakt
     * Search by name or tvdbid
     * Example: 'The Blacklist' or 'tvdb:266189'
     *
     * @param string $searchTerm query string for the search (Use tvdb:12345 to lookup TVDB ID 12345)
     * @return string
     * @throws InvalidException
     */
    public function getSeriesLookup($searchTerm)
    {
        $uri = 'series/lookup';
        $uriData = [
            'term' => $searchTerm
        ];

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => $uriData
                ]


            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Get System Status
     *
     * @return string
     * @throws InvalidException
     */
    public function getSystemStatus()
    {
        $uri = 'system/status';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]


            );
        } catch ( \Exception $e ) {
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
        $options = [
            'headers' => [
                'X-Api-Key' => $this->apiKey    
            ]    
        ];
        
        if ( $this->httpAuthUsername && $this->httpAuthPassword ) {
            $options['auth'] = [
                $this->httpAuthUsername,
                $this->httpAuthPassword
            ];
        }

        if ( $params['type'] == 'get' ) {
            $url = $this->url . '/api/' . $params['uri'] . '?' . http_build_query($params['data']);

            return $client->get($url, $options);
        }

        if ( $params['type'] == 'put' ) {
            $url = $this->url . '/api/' . $params['uri'];
            $options['json'] = $params['data'];
            
            return $client->put($url, $options);
        }

        if ( $params['type'] == 'post' ) {
            $url = $this->url . '/api/' . $params['uri'];
            $options['json'] = $params['data'];
            
            return $client->post($url, $options);
        }

        if ( $params['type'] == 'delete' ) {
            $url = $this->url . '/api/' . $params['uri'] . '?' . http_build_query($params['data']);

            return $client->delete($url, $options);
        }
    }

    /**
     * Verify date is in proper format
     *
     * @param $date
     * @param string $format
     * @return bool
     */
    private function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
}
