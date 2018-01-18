# Schema

In Adldap2, a Schema class has been implemented. This means that if your
active directory schema differs is some way for specific attributes,
you can customize them and those attribute names and it will
persist throughout using Adldap2. The schema also provides
a convenient way of accessing Schema attributes.

Let's get started.

Adldap2 comes with an `Adldap\Schemas\ActiveDirectory` schema by default, which implements `Adldap\Schemas\SchemaInterface`.

You can either extend from the `ActiveDirectory` schema, or create your own and implement the `SchemaInterface`.

Please browse the [Schema Interface](/src/Schemas/SchemaInterface.php) to view all of the schema methods.

Your Schema:

```php
namespace App\Schemas;

use Adldap\Schemas\ActiveDirectory;

class MySchema extends ActiveDirectory
{
    /**
     * {@inheritdoc}
     */
    public function objectCategory()
    {
        return 'objectcategory';
    }
}
```

Injecting your custom schema:

```php
// Your configuration array.
$config = ['...'];

// New up your custom schema.
$mySchema = new \App\Schema\MySchema();

// Create a new connection provider, and inject your schema.
$provider = new \Adldap\Connections\Provider($config, $connection = null, $mySchema);

// Add the provider to your Adldap instance.
$adldap->addProvider($provider, $name = 'default');

// Connect to your provider.
$adldap->connect('default');
```
