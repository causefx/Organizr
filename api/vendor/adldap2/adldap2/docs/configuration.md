# Configuration

- [Using an Array](#using-a-configuration-array)
- [Using DomainConfiguration](#using-a-domainconfiguration-object)
- [Definitions](#definitions)
    - [Account Prefix (optional)](#account-prefix-optional)
    - [Account Suffix (optional)](#account-suffix-optional)
    - [Admin Account Suffix (optional)](#admin-account-suffix-optional)
    - [Domain Controllers (required)](#domain-controllers-required)
    - [Port (optional)](#port-optional)
    - [Base Distinguished Name (required)](#base-distinguished-name-required)
    - [Administrator Username & Password (required)](#administrator-username--password-required)
    - [Follow Referrals (optional)](#follow-referrals-optional)
    - [SSL & TLS (optional)](#ssl--tls-optional)
    - [Timeout](#timeout)
    - [Custom Options](#custom-options)

Configuring Adldap2 is really easy. Let's get started.

## Using a configuration array

You can configure Adldap2 by supplying an array. Keep in mind not all of these are required. This will be discussed below.
Here is an example array with all possible configuration options:

```php
// Create the configuration array.
$config = [    // Mandatory Configuration Options
    'domain_controllers'    => ['corp-dc1.corp.acme.org', 'corp-dc2.corp.acme.org'],
    'base_dn'               => 'dc=corp,dc=acme,dc=org',
    'admin_username'        => 'admin',
    'admin_password'        => 'password',
    
    // Optional Configuration Options
    'account_prefix'        => 'ACME-',
    'account_suffix'        => '@acme.org',
    'admin_account_prefix'  => 'ACME-ADMIN-',
    'admin_account_suffix'  => '@acme.org',
    'port'                  => 389,
    'follow_referrals'      => false,
    'use_ssl'               => false,
    'use_tls'               => false,
    'timeout'               => 5,
    
    // Custom LDAP Options
    'custom_options'        => [
        // See: http://php.net/ldap_set_option
        LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_HARD
    ]
];

// Create a new Adldap Provider instance.
$provider = new \Adldap\Connections\Provider($config);
```

## Using a DomainConfiguration object

If you'd prefer, you can also construct a `DomainConfiguration` object:

```php
// Setting configuration options via construct:
$config = new \Adldap\Configuration\DomainConfiguration([
    'domain_controllers' => [
        'corp-dc1.corp.acme.org',
        'corp-dc2.corp.acme.org',
    ],
]);

// Setting configuration options via `set()` method:
$config->set('domain_controllers', [
    'corp-dc1.corp.acme.org',
    'corp-dc2.corp.acme.org',
]);

$provider = new \Adldap\Connections\Provider($config);
```

## Definitions

### Account Prefix (optional)

The account prefix option is the prefix of your user accounts in AD. This is usually not needed (if utilizing the
account suffix), however the functionality is in place if you would like to only allow certain users with
the specified prefix to login, or add a domain so you're users do not have to specify one.

### Account Suffix (optional)

The account suffix option is the suffix of your user accounts in AD. For example, if your domain DN is `DC=corp,DC=acme,DC=org`,
then your account suffix would be `@corp.acme.org`. This is then appended to then end of your user accounts on authentication.

For example, if you're binding as a user, and your username is `jdoe`, then Adldap would try to authenticate with
your server as `jdoe@corp.acme.org`.

### Admin Account Prefix (optional)

The admin account prefix option is the prefix of your administrator account in AD. Having a separate prefix for user accounts
and administrator accounts allows you to bind your admin under a different prefix than user accounts.

### Admin Account Suffix (optional)

The admin account suffix option is the suffix of your administrator account in AD. Having a separate suffix for user accounts
and administrator accounts allows you to bind your admin under a different suffix than user accounts.

### Domain Controllers (required)

The domain controllers option is an array of servers located on your network that serve Active Directory. You insert as many
servers or as little as you'd like depending on your forest (with the minimum of one of course).

For example, if the server name that hosts AD on my network is named `ACME-DC01`, then I would insert `['ACME-DC01.corp.acme.org']`
inside the domain controllers option array.

### Port (optional)

The port option is used for authenticating and binding to your AD server. The default ports are already used for non SSL and SSL connections (389 and 636).

Only insert a port if your AD server uses a unique port.

### Base Distinguished Name (required)

The base distinguished name is the base distinguished name you'd like to perform operations on. An example base DN would be `DC=corp,DC=acme,DC=org`.

If one is not defined, you will not retrieve any search results.

### Administrator Username & Password (required)

When connecting to your AD server, an administrator username and password is required to be able to query and run operations on your server(s).
You can use any account that has these permissions.

### Follow Referrals (optional)

The follow referrals option is a boolean to tell active directory to follow a referral to another server on your network if the
server queried knows the information your asking for exists, but does not yet contain a copy of it locally. This option is defaulted to false.

For more information, visit: https://technet.microsoft.com/en-us/library/cc978014.aspx

### SSL & TLS (optional)

If you need to be able to change user passwords on your server, then an SSL *or* TLS connection is required. All other operations
are allowed on unsecured protocols. These options are definitely recommended if you have the ability to connect to your server
securely.

### Timeout

The timeout option allows you to configure the amount of seconds to wait until your application receives a response from your LDAP server.

The default is 5 seconds.

### Custom Options

Arbitrary options can be set for the connection to fine-tune TLS and connection behavior. Please note that `LDAP_OPT_PROTOCOL_VERSION`,
`LDAP_OPT_NETWORK_TIMEOUT` and `LDAP_OPT_REFERRALS` will be ignored if set. These are set above with the `version`, `timeout` and
`follow_referrals` keys respectively. Valid options are listed in the [PHP documentation for ldap_set_option](http://php.net/ldap_set_option).
