# The Computer Model

> **Note**: This model contains the traits `HasDescription`, `HasLastLogonAndLogOff` & `HasCriticalSystemObject`.
> For more information, visit the documentation:
>
> [HasDescription](traits/has-description.md),
> [HasLastLogonAndLogOff](traits/has-last-login-last-logoff.md),
> [HasCriticalSystemObject](traits/has-critical-system-object.md)

## List of Available 'Getter' Methods:

```php
$computer = $provider->search()->computers()->find('ACME-EXCHANGE');

// Returns 'Windows Server 2003'
$computer->getOperatingSystem();

// Returns '5.2 (3790)';
$computer->getOperatingSystemVersion();

// Returns 'Service Pack 1';
$computer->getOperatingSystemServicePack();

// Returns 'ACME-DESKTOP001.corp.acme.org'
$computer->getDnsHostName();

$computer->getLastLogOff();

$computer->getLastLogon();

$computer->getLastLogonTimestamp();
```
