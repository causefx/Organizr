# Connecting

- [Setup](#setup)
- [Dynamically Connecting](#dynamically-connecting)
- [Setting a Default Connection](#setting-a-default-connection)
- [Custom Connections](#custom-connections)
- [Custom Schemas](#custom-schemas)

## Setup

After installation, you'll need to create a couple objects to start running operations on your LDAP server.

First, we'll define our configuration array (outlined [here](configuration.md)):

```php
$config = [
    'default' => ['...'],
];
```

We'll then create a new Adldap instance and pass in the configuration array:

```php
$ad = new \Adldap\Adldap($config);

try {
    // Connect to the provider you specified in your configuration.
    $provider = $ad->connect('default');
    
    // Connection was successful.
    
    // We can now perform operations on the connection.
    $user = $provider->search()->users()->find('jdoe');

} catch (\Adldap\Auth\BindException $e) {
    die("Can't connect / bind to the LDAP server! Error: $e");
}
```

## Dynamically Connecting

If you prefer, you can actually call all provider methods on your default provider through your `Adldap` instance.

For example:

```php
$ad = new \Adldap\Adldap($config);

try {
    $user = $ad->search()->users()->find('jdoe');
} catch (\Adldap\Auth\BindException $e) {
    //
}
```

Adldap will automatically connect to your default provider and perform all method calls upon it.

## Setting a default connection

If you name your connection something other than `default`, you'll have to set that as your default connection:

For example:

```php
$config = [
    'ad_acme_company' => ['...'],
];

$ad = new \Adldap\Adldap($config);

$ad->setDefaultProvider('ad_acme_company');

$user = $ad->search()->users()->find('jdoe');
```

Adldap will automatically connect to your new default provider.

## Custom Connections

Whenever you don't supply a new provider with an object that's an instance of
`Adldap\Connections\ConnectionInterface`, a default connection is created for you.

A connection object is a wrapper for PHP's LDAP calls. This allows you to tweak how
things are passed into these methods if needed.

To create a custom connection, you can either extend the default connection class
(`Adldap\Connections\Ldap`), or implement the `ConnectionInterface`.

For example:

```php
class CustomConnection extends \Adldap\Connections\Ldap
{
    public function connect($hostname = [], $port = '389')
    {
        // Perform an `ldap_connect()` your own way...
    }
}
```

Now that we have our own connection class, we can instantiate it and pass it to the provider:

```php
$config = ['...'];

$connection = new CustomConnection();

$provider = new \Adldap\Connections\Provider($config, $connection);
```

## Custom Schemas

Some LDAP installations differ and you may need to tweak what some attributes are. This is where the schema comes in.

By default, if no schema is passed into the third parameter of a provider instance, a default schema is created.

The schema must extend from an already existing schema, or implement `Adldap\Contracts\Schemas\SchemaInterface`.

Let's create a custom schema:

```php
class CustomSchema extends \Adldap\Schemas\ActiveDirectory
{
    public function email()
    {
        return 'mail';
    }
}
```

Now we'll put it all together:

```php
$config = ['...'];

$connection = new CustomConnection();

$schema = new CustomSchema();

$provider = new \Adldap\Connections\Provider($config, $connection, $schema);
```

Now our provider will utilize our custom schema and connection classes.
