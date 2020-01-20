# Sonarr
PHP Wrapper for Sonarr https://sonarr.tv

Here is the Sonarr API Documentation that this package implements: https://github.com/Sonarr/Sonarr/wiki/API

## Installation
```ruby
composer require kryptonit3/sonarr
```

## Example Usage
```php
use Kryptonit3\Sonarr\Sonarr;
```
```php
public function addSeries()
{
    $sonarr = new Sonarr('http://127.0.0.1:8989', 'cf7544f71b6c4efcbb84b49011fc965c'); // URL and API Key
    
    return $sonarr->postSeries([
        'tvdbId' => 73739,
        'title' => 'Lost',
        'qualityProfileId' => 3, // HD-720p
        'rootFolderPath' => '/volume1/Plex/Shows'
    ]);
}
```
### HTTP Auth
If your site requires HTTP Auth username and password you may supply it like this. Please note, if you are using HTTP Auth without SSL you are sending your username and password unprotected across the internet.
```php
$sonarr = new Sonarr('http://127.0.0.1:8989', 'cf7544f71b6c4efcbb84b49011fc965c', 'my-username', 'my-password');
```

### Output
```json
{
  "title": "Lost",
  "alternateTitles": [
    
  ],
  "sortTitle": "lost",
  "seasonCount": 0,
  "totalEpisodeCount": 0,
  "episodeCount": 0,
  "episodeFileCount": 0,
  "sizeOnDisk": 0,
  "status": "continuing",
  "images": [
    
  ],
  "seasons": [
    
  ],
  "year": 0,
  "path": "\/volume1\/Plex\/Shows\/Lost",
  "profileId": 3,
  "seasonFolder": true,
  "monitored": true,
  "useSceneNumbering": false,
  "runtime": 0,
  "tvdbId": 73739,
  "tvRageId": 0,
  "tvMazeId": 0,
  "seriesType": "standard",
  "cleanTitle": "lost",
  "genres": [
    
  ],
  "tags": [
    
  ],
  "added": "2016-02-06T18:11:26.475637Z",
  "addOptions": {
    "searchForMissingEpisodes": false,
    "ignoreEpisodesWithFiles": true,
    "ignoreEpisodesWithoutFiles": true
  },
  "qualityProfileId": 3,
  "id": 90
}
```

For available methods reference included [Sonarr::class](src/Sonarr.php)

Note: when posting data with key => value pairs, keys are case-sensitive.
