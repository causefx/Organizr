# trakt.tv Provider for OAuth 2.0 Client
[![Latest Version](https://img.shields.io/github/release/bogstag/oauth2-trakt.svg?style=flat-square)](https://github.com/bogstag/oauth2-trakt/releases)
[![Build Status](https://img.shields.io/travis/Bogstag/oauth2-trakt/master.svg?style=flat-square)](https://travis-ci.org/Bogstag/oauth2-trakt)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/bogstag/oauth2-trakt.svg?style=flat-square)](https://scrutinizer-ci.com/g/bogstag/oauth2-trakt/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/bogstag/oauth2-trakt.svg?style=flat-square)](https://scrutinizer-ci.com/g/bogstag/oauth2-trakt)
[![StyleCI](https://styleci.io/repos/83116136/shield?branch=master?style=flat)](https://styleci.io/repos/83116136)
[![Total Downloads](https://img.shields.io/packagist/dt/bogstag/oauth2-trakt.svg?style=flat-square)](https://packagist.org/packages/bogstag/oauth2-trakt)
[![Software License](https://img.shields.io/packagist/l/bogstag/oauth2-trakt.svg?style=flat-square)](https://packagist.org/packages/bogstag/oauth2-trakt)

This package provides trakt.tv OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require bogstag/oauth2-trakt
```

## Usage

Usage is the same as The League's OAuth client, using `\Bogstag\OAuth2\Client\Provider\Trakt` as the provider.

### Authorization Code Flow

```php
$provider = new Bogstag\OAuth2\Client\Provider\Trakt([
    'clientId'          => '{trakt-client-id}',
    'clientSecret'      => '{trakt-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url'
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getName());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/bogstag/oauth2-trakt/blob/master/CONTRIBUTING.md) for details.


## Credits

- [Krister Bogstag](https://github.com/bogstag)
- [All Contributors](https://github.com/bogstag/oauth2-trakt/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/bogstag/oauth2-trakt/blob/master/LICENSE) for more information.
