# Authenticating

To authenticate users using your LDAP server, call the `auth()->attempt()`
method on your provider:

```php
try {

    if ($provider->auth()->attempt($username, $password)) {
        // Credentials were correct.
    } else {
        // Credentials were incorrect.
    }

} catch (\Adldap\Auth\UsernameRequiredException $e) {
    // The user didn't supply a username.
} catch (\Adldap\Auth\PasswordRequiredException $e) {
    // The user didn't supply a password.
}
```

> **Note**: Authenticating does not actually set up a PHP session or perform any
> sort of login functionality. The attempt() method merely tries to bind to
> your LDAP server as the specified user and returns true / false on its result.

## Binding as Authenticated Users

To bind the users to your LDAP connection that you authenticate (which
means *run all further LDAP operations under this user*),
pass in `true` into the third parameter:

```php
$username = 'jdoe';
$password = 'Password123';

if ($provider->auth()->attempt($username, $password, $bindAsUser = true)) {
    // Credentials were correct.
    
    // All LDAP operations will be ran under John Doe.
}
```

> **Note**: By default, `$bindAsUser` is false, this means that all LDAP
> operations are ran under your configured administrator account unless
> otherwise specified.

## Manually Binding as Administrator

To manually bind as your configured administrator, use the `bindAsAdministrator()` method:

```php
try {
    $provider->auth()->bindAsAdministrator();

    // Successfully bound to server.
} catch (\Adldap\Auth\BindException $e) {
    // There was an issue binding to the LDAP server.
}
```

## Manually Binding as A User

To manually bind as a user, use the `bind()` method:

```php
try {
    $provider->auth()->bind($username, $password);

     // Successfully bound to server.
} catch (\Adldap\Auth\BindException $e) {
    // There was an issue binding to the LDAP server.
}
```

> **Note**: Manually binding as a user **will not** validate their
> username or password to ensure they are not empty.
>
> This means, a user could pass in empty strings and could anonymously
> authenticate to your server if you don't validate their input.
