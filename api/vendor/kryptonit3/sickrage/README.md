# SickRage
PHP Wrapper for SickRage https://sickrage.github.io/
Will also work with SickBeard

Here is the SickRage/Sickbeard API Documentation that this package implements: http://sickbeard.com/api/

## Installation
```ruby
composer require kryptonit3/sickrage
```

## Example Usage
```php
use Kryptonit3\SickRage\SickRage;
```
```php
public function addShow()
{
    $sickrage = new SickRage('http://127.0.0.1:8081', 'cf7544f71b6c4efcbb84b49011fc965c'); // URL and API Key
    
    return $sickrage->showAddNew(73739, '/volume1/Plex/Shows');
}
```
### HTTP Auth
If your site requires HTTP Auth username and password you may supply it like this. Please note, if you are using HTTP Auth without SSL you are sending your username and password unprotected across the internet.
```php
$sonarr = new Sonarr('http://127.0.0.1:8081', 'cf7544f71b6c4efcbb84b49011fc965c', 'my-username', 'my-password');
```

### Output
```json
{
  "data": {
    "name": "Lost"
  },
  "message": "Lost has been queued to be added",
  "result": "success"
}
```

For available methods reference included [SickRage::class](src/SickRage.php)