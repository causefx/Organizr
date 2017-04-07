<?php

namespace Kryptonit3\SickRage;

use GuzzleHttp\Client;
use Kryptonit3\SickRage\Exceptions\InvalidException;

class SickRage
{
    protected $url;
    protected $apiKey;
    protected $httpAuthUsername;
    protected $httpAuthPassword;

    public function __construct($url, $apiKey, $httpAuthUsername = null, $httpAuthPassword = null)
    {
        $this->url = rtrim($url, '/\\'); // Example: http://127.0.0.1:8081 (no trailing forward-backward slashes)
        $this->apiKey = $apiKey;
        $this->httpAuthUsername = $httpAuthUsername;
        $this->httpAuthPassword = $httpAuthPassword;
    }

    /**
     * Displays the information of a specific episode matching the corresponding tvdbid, season and episode number.
     *
     * @param int $tvdbId tvdbid unique show id
     * @param int $season season number
     * @param int $episode episode number
     * @param int $fullPath 0: file name only 1: full path
     * @return string
     * @throws InvalidException
     */
    public function episode($tvdbId, $season, $episode, $fullPath = 0)
    {
        $uri = 'episode';
        $uriData = [
            'tvdbid' => $tvdbId,
            'season' => $season,
            'episode' => $episode,
            'full_path' => $fullPath
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
     * Initiate a search for a specific episode matching the corresponding tvdbid, season and episode number.
     *
     * @param int $tvdbId tvdbid unique show id
     * @param int $season season number
     * @param int $episode episode number
     * @return string
     * @throws InvalidException
     */
    public function episodeSearch($tvdbId, $season, $episode)
    {
        $uri = 'episode';
        $uriData = [
            'tvdbid' => $tvdbId,
            'season' => $season,
            'episode' => $episode
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
     * Set the status of an epsiode or season.
     *
     * @param int $tvdbId tvdbid unique show id
     * @param int $season season number
     * @param string $status wanted, skipped, archived, ignored
     * @param int|null $episode episode number
     * --- if an episode is not provided, then the whole seasons' status will be set.
     * @param int $force 0: not existing episodes 1: include existing episodes (can replace downloaded episodes)
     * @return string
     * @throws InvalidException
     */
    public function episodeSetStatus($tvdbId, $season, $status, $episode = null, $force = 0)
    {
        $uri = 'episode.setstatus';
        $uriData = [
            'tvdbid' => $tvdbId,
            'season' => $season,
            'status' => $status,
            'force' => $force
        ];
        if ( $episode ) { $uriData['episode'] = $episode; }

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
     * Display scene exceptions for all or a given show.
     *
     * @param int|null $tvdbId tvdbid unique show id
     * @return string
     * @throws InvalidException
     */
    public function exceptions($tvdbId = null)
    {
        $uri = 'exceptions';
        $uriData = [];
        if ( $tvdbId ) { $uriData['tvdbid'] = $tvdbId; }

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
     * Display the upcoming episodes for the shows currently added in the users' database.
     *
     * @param string $sort date, network, name
     * @param string $type missed, today, soon, later - multiple types can be passed when delimited by |
     * --- missed - show's date is older than today
     * --- today - show's date is today
     * --- soon - show's date greater than today but less than a week
     * --- later - show's date greater than a week
     * @param int|null $paused 0: do not show paused 1: show paused
     * --- if not set then the user's default setting in SickRage is used
     * @return string
     * @throws InvalidException
     */
    public function future($sort = 'date', $type = 'missed|today|soon|later', $paused = null)
    {
        $uri = 'future';
        $uriData = [
            'sort' => $sort,
            'type' => $type
        ];
        if ( $paused ) { $uriData['paused'] = $paused; }

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
     * Display SickRage's downloaded/snatched history.
     *
     * @param int $limit Use 0 if you want to see all history, note this could cause
     * --- heavy cpu/disk usage for the user as well as cause your application to time out
     * --- while it's waiting for the data.
     * @param string|null $type downloaded, snatched
     * @return string
     * @throws InvalidException
     */
    public function history($limit = 100, $type = null)
    {
        $uri = 'history';
        $uriData = [
            'limit' => $limit
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
     * Clear SickRage's history.
     *
     * @return string
     * @throws InvalidException
     */
    public function historyClear()
    {
        $uri = 'history.clear';

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
     * Trim SickRage's history by removing entries greater than 30 days old.
     *
     * @return string
     * @throws InvalidException
     */
    public function historyTrim()
    {
        $uri = 'history.trim';

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
     * View SickRage's log.
     *
     * @param string $minLevel debug, info, warning, error
     * @return string
     * @throws InvalidException
     */
    public function logs($minLevel = 'error')
    {
        $uri = 'history.trim';
        $uriData = [
            'min_level' => $minLevel
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
     * Display information for a given show.
     *
     * @param int $tvdbId tvdbid unique show id
     * @return string
     * @throws InvalidException
     */
    public function show($tvdbId)
    {
        $uri = 'show';
        $uriData = [
            'tvdbid' => $tvdbId
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
     * Add a show to SickRage using an existing folder.
     *
     * @param int $tvdbId tvdbid unique show id
     * @param string $location path to existing show folder
     * @param int|null $flattenFolders
     * --- 0: use season folders if part of rename string
     * --- 1: do not use season folders
     * --- if not provided then the config setting (default) is used
     * @param string|null $initial multiple types can be passed when delimited by |
     * --- sdtv, sddvd, hdtv, rawhdtv, fullhdtv, hdwebdl, fullhdwebdl, hdbluray, fullhdbluray, unknown
     * --- if not provided then the config setting (default) is used
     * @param string|null $archive multiple types can be passed when delimited by |
     * --- sddvd, hdtv, rawhdtv, fullhdtv, hdwebdl, fullhdwebdl, hdbluray, fullhdbluray
     * --- if not provided then the config setting (default) is used
     * @return string
     * @throws InvalidException
     */
    public function showAddExisting($tvdbId, $location, $flattenFolders = null, $initial = null, $archive = null)
    {
        $uri = 'show.addexisting';
        $uriData = [
            'tvdbid' => $tvdbId,
            'location' => $location
        ];

        if ( $flattenFolders ) { $uriData['flatten_folders'] = $flattenFolders; }
        if ( $initial ) { $uriData['initial'] = $initial; }
        if ( $archive ) { $uriData['archive'] = $archive; }

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
     * Add a show to SickRage providing the parent directory where the tv shows folder should be created.
     *
     * @param int $tvdbId tvdbid unique show id
     * @param string|null $location path to existing folder to store show
     * --- if not provided then the config setting (default) is used -- if valid
     * @param string $lang two letter tvdb language, en = english
     * --- en, zh, hr, cs, da, nl, fi, fr, de, el, he, hu, it, ja, ko, no, pl, pt, ru, sl, es, sv, tr
     * @param int|null $flattenFolders
     * --- 0: use season folders if part of rename string
     * --- 1: do not use season folders
     * --- if not provided then the config setting (default) is used
     * @param string|null $status wanted, skipped, archived, ignored
     * --- if not provided then the config setting (default) is used
     * @param string|null $initial multiple types can be passed when delimited by |
     * --- sdtv, sddvd, hdtv, rawhdtv, fullhdtv, hdwebdl, fullhdwebdl, hdbluray, fullhdbluray, unknown
     * --- if not provided then the config setting (default) is used
     * @param string|null $archive multiple types can be passed when delimited by |
     * --- sddvd, hdtv, rawhdtv, fullhdtv, hdwebdl, fullhdwebdl, hdbluray, fullhdbluray
     * --- if not provided then the config setting (default) is used
     * @return string
     * @throws InvalidException
     */
    public function showAddNew($tvdbId, $location = null, $lang = 'en', $flattenFolders = null,
                                $status = null, $initial = null, $archive = null)
    {
        $uri = 'show.addnew';
        $uriData = [
            'tvdbid' => $tvdbId,
            'lang' => $lang
        ];

        if ( $flattenFolders ) { $uriData['flatten_folders'] = $flattenFolders; }
        if ( $initial ) { $uriData['initial'] = $initial; }
        if ( $archive ) { $uriData['archive'] = $archive; }
        if ( $status ) { $uriData['status'] = $status; }
        if ( $location ) { $uriData['location'] = $location; }

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
     * Display if the poster/banner SickRage's image cache is valid.
     *
     * @param int $tvdbId tvdbid unique show id
     * @return string
     * @throws InvalidException
     */
    public function showCache($tvdbId)
    {
        $uri = 'show.cache';
        $uriData = [
            'tvdbid' => $tvdbId
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
     * Delete a show from SickRage.
     *
     * @param int $tvdbId tvdbid unique show id
     * @return string
     * @throws InvalidException
     */
    public function showDelete($tvdbId)
    {
        $uri = 'show.delete';
        $uriData = [
            'tvdbid' => $tvdbId
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
     * Retrieve the stored banner image from SickRage's cache for a particular tvdbid.
     * If no image is found then the default sb banner is shown.
     *
     * @param int $tvdbId tvdbid unique show id
     * @return string
     * @throws InvalidException
     */
    public function showGetBanner($tvdbId)
    {
        $uri = 'show.getbanner';
        $uriData = [
            'tvdbid' => $tvdbId
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
     * Retrieve the stored poster image from SickRage's cache for a particular tvdbid.
     * If no image is found then the default sb poster is shown.
     *
     * @param int $tvdbId tvdbid unique show id
     * @return string
     * @throws InvalidException
     */
    public function showGetPoster($tvdbId)
    {
        $uri = 'show.getposter';
        $uriData = [
            'tvdbid' => $tvdbId
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
     * Get quality settings of a show in SickRage.
     *
     * @param int $tvdbId tvdbid unique show id
     * @return string
     * @throws InvalidException
     */
    public function showGetQuality($tvdbId)
    {
        $uri = 'show.getquality';
        $uriData = [
            'tvdbid' => $tvdbId
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
     * Set a show's paused state in SickRage.
     *
     * @param int $tvdbId tvdbid unique show id
     * @param int $pause 0: unpause show 1: pause show
     * @return string
     * @throws InvalidException
     */
    public function showPause($tvdbId, $pause = 0)
    {
        $uri = 'show.pause';
        $uriData = [
            'tvdbid' => $tvdbId,
            'pause' => $pause
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
     * Refresh a show in SickRage by rescanning local files.
     *
     * @param int $tvdbId tvdbid unique show id
     * @return string
     * @throws InvalidException
     */
    public function showRefresh($tvdbId)
    {
        $uri = 'show.refresh';
        $uriData = [
            'tvdbid' => $tvdbId
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
     * Display the season list for a given show.
     *
     * @param int $tvdbId tvdbid unique show id
     * @param string $sort asc, desc
     * @return string
     * @throws InvalidException
     */
    public function showSeasonList($tvdbId, $sort = 'desc')
    {
        $uri = 'show.seasonlist';
        $uriData = [
            'tvdbid' => $tvdbId,
            'sort' => $sort
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
     * Display a listing of episodes for all or a given season.
     *
     * @param int $tvdbId tvdbid unique show id
     * @param int|null $season season number
     * @return string
     * @throws InvalidException
     */
    public function showSeasons($tvdbId, $season = null)
    {
        $uri = 'show.seasons';
        $uriData = [
            'tvdbid' => $tvdbId
        ];
        if ( is_numeric($season) ) { $uriData['season'] = $season; }

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
     * Set desired quality of a show in SickRage.
     *
     * @param int $tvdbId tvdbid unique show id
     * @param string|null $initial multiple types can be passed when delimited by |
     * --- sdtv, sddvd, hdtv, rawhdtv, fullhdtv, hdwebdl, fullhdwebdl, hdbluray, fullhdbluray, unknown
     * @param string|null $archive multiple types can be passed when delimited by |
     * --- sddvd, hdtv, rawhdtv, fullhdtv, hdwebdl, fullhdwebdl, hdbluray, fullhdbluray
     * @return string
     * @throws InvalidException
     */
    public function showSetQuality($tvdbId, $initial = null, $archive = null)
    {
        $uri = 'show.setquality';
        $uriData = [
            'tvdbid' => $tvdbId
        ];
        if ( $initial ) { $uriData['initial'] = $initial; }
        if ( $archive ) { $uriData['archive'] = $archive; }

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
     * Display episode statistics for a given show.
     *
     * @param int $tvdbId tvdbid unique show id
     * @return string
     * @throws InvalidException
     */
    public function showStats($tvdbId)
    {
        $uri = 'show.stats';
        $uriData = [
            'tvdbid' => $tvdbId
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
     * Update a show in SickRage by pulling down information from TVDB and rescan local files.
     *
     * @param int $tvdbId tvdbid unique show id
     * @return string
     * @throws InvalidException
     */
    public function showUpdate($tvdbId)
    {
        $uri = 'show.update';
        $uriData = [
            'tvdbid' => $tvdbId
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
     * Display a list of all shows in SickRage.
     *
     * @param string $sort id, name
     * --- id - sort by tvdbid
     * --- name - sort by show name
     * @param int|null $paused if not set then both paused and non paused are shown
     * --- 0: show only non paused
     * --- 1: show only paused
     * @return string
     * @throws InvalidException
     */
    public function shows($sort = 'id', $paused = null)
    {
        $uri = 'shows';
        $uriData = [
            'sort' => $sort
        ];
        if ( $paused ) { $uriData['paused'] = $paused; }

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
     * Display global episode and show statistics.
     *
     * @return string
     * @throws InvalidException
     */
    public function showsStats()
    {
        $uri = 'shows.stats';

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
     * Display misc SickRage related information.
     * This is also the default command that the api will show if none is specified.
     *
     * @return string
     * @throws InvalidException
     */
    public function sb()
    {
        $uri = 'sb';

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
     * Add a root (parent) directory (only if it is a valid location),
     * and set as the default root dir if requested to SickRages's config.
     *
     * @param string $location full path to a root (parent) directory of tv shows
     * @param int $default
     * --- 0: do not change global default
     * --- 1: set location as the new global default
     * @return string
     * @throws InvalidException
     */
    public function sbAddRootDir($location, $default = 0)
    {
        $uri = 'sb.addrootdir';
        $uriData = [
            'location' => $location,
            'default' => $default
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
     * Query the SickBeard scheduler.
     *
     * @return string
     * @throws InvalidException
     */
    public function sbCheckScheduler()
    {
        $uri = 'sb.checkscheduler';

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
     * Delete a root (parent) directory from the root directory list in SickRage's config.
     *
     * @param string $location
     * @return string
     * @throws InvalidException
     */
    public function sbDeleteRootDir($location)
    {
        $uri = 'sb.deleterootdir';
        $uriData = [
            'location' => $location
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
     * Force the episode search early.
     *
     * @return string
     * @throws InvalidException
     */
    public function sbForceSearch()
    {
        $uri = 'sb.forcesearch';

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
     * Get default settings from SickBeard's config.
     *
     * @return string
     * @throws InvalidException
     */
    public function sbGetDefaults()
    {
        $uri = 'sb.getdefaults';

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
     * Get un-claimed messages from the ui.notification queue.
     *
     * @return string
     * @throws InvalidException
     */
    public function sbGetMessages()
    {
        $uri = 'sb.getmessages';

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
     * Get the root (parent) directories from SickBeard's config, test if valid, and which is the default.
     *
     * @return string
     * @throws InvalidException
     */
    public function sbGetRootDirs()
    {
        $uri = 'sb.getrootdirs';

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
     * Pause the backlog search.
     *
     * @param int $pause
     * --- 0: unpause the backlog
     * --- 1: pause the backlog
     * @return string
     * @throws InvalidException
     */
    public function sbPauseBacklog($pause = 0)
    {
        $uri = 'sb.pausebacklog';
        $uriData = [
            'pause' => $pause
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
     * Check to see if SickRage is running.
     *
     * @return string
     * @throws InvalidException
     */
    public function sbPing()
    {
        $uri = 'sb.ping';

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
     * Restart SickRage.
     *
     * @return string
     * @throws InvalidException
     */
    public function sbRestart()
    {
        $uri = 'sb.restart';

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
     * Search TVDB for a show with a given string or tvdbid.
     *
     * @param string|null $name show name
     * @param int|null $tvdbId tvdbid unique show id
     * @param string $lang two letter tvdb language, en = english
     * --- en, zh, hr, cs, da, nl, fi, fr, de, el, he, hu, it, ja, ko, no, pl, pt, ru, sl, es, sv, tr
     * @return string
     * @throws InvalidException
     */
    public function sbSearchTvdb($name = null, $tvdbId = null, $lang = 'en')
    {
        $uri = 'sb.searchtvdb';
        $uriData = [
            'lang' => $lang
        ];
        if ( $name ) { $uriData['name'] = $name; }
        if ( $tvdbId ) { $uriData['tvdbid'] = $tvdbId; }

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
     * Set default settings for SickRage.
     *
     * @param int|null $futureShowPaused
     * --- 0: exclude paused shows on coming ep
     * --- 1: include paused shows on coming ep
     * @param string|null $status wanted, skipped, archived, ignored
     * @param int|null $flattenFolders
     * --- 0: use season folders if part of rename string
     * --- 1: do not use season folders
     * @param string|null $initial multiple types can be passed when delimited by |
     * --- sdtv, sddvd, hdtv, rawhdtv, fullhdtv, hdwebdl, fullhdwebdl, hdbluray, fullhdbluray, unknown
     * @param string|null $archive multiple types can be passed when delimited by |
     * --- sddvd, hdtv, rawhdtv, fullhdtv, hdwebdl, fullhdwebdl, hdbluray, fullhdbluray
     * @return string
     * @throws InvalidException
     */
    public function sbSetDefaults($futureShowPaused = null, $status = null,
                                  $flattenFolders = null, $initial = null, $archive = null)
    {
        $uri = 'sb.setdefaults';
        $uriData = [];
        if ( $futureShowPaused ) { $uriData['future_show_paused'] = $futureShowPaused; }
        if ( $status ) { $uriData['status'] = $status; }
        if ( $flattenFolders ) { $uriData['flatten_folders'] = $flattenFolders; }
        if ( $initial ) { $uriData['initial'] = $initial; }
        if ( $archive ) { $uriData['archive'] = $archive; }

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
     * Shutdown SickRage.
     *
     * @return string
     * @throws InvalidException
     */
    public function sbShutdown()
    {
        $uri = 'sb.shutdown';

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
            $url = $this->url . '/api/' . $this->apiKey . '/?cmd=' . $params['uri'] . '&' . http_build_query($params['data']);
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
