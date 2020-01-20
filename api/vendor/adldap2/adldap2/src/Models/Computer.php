<?php

namespace Adldap\Models;

use Adldap\Models\Concerns\HasDescription;
use Adldap\Models\Concerns\HasUserAccountControl;
use Adldap\Models\Concerns\HasLastLogonAndLogOff;
use Adldap\Models\Concerns\HasCriticalSystemObject;

/**
 * Class Computer
 *
 * Represents an LDAP computer / server.
 *
 * @package Adldap\Models
 */
class Computer extends Entry
{
    use HasCriticalSystemObject,
        HasDescription,
        HasLastLogonAndLogOff,
        HasUserAccountControl;

    /**
     * Returns the computers operating system.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms679076(v=vs.85).aspx
     *
     * @return string
     */
    public function getOperatingSystem()
    {
        return $this->getFirstAttribute($this->schema->operatingSystem());
    }

    /**
     * Returns the computers operating system version.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms679079(v=vs.85).aspx
     *
     * @return string
     */
    public function getOperatingSystemVersion()
    {
        return $this->getFirstAttribute($this->schema->operatingSystemVersion());
    }

    /**
     * Returns the computers operating system service pack.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms679078(v=vs.85).aspx
     *
     * @return string
     */
    public function getOperatingSystemServicePack()
    {
        return $this->getFirstAttribute($this->schema->operatingSystemServicePack());
    }

    /**
     * Returns the computers DNS host name.
     *
     * @return string
     */
    public function getDnsHostName()
    {
        return $this->getFirstAttribute($this->schema->dnsHostName());
    }

    /**
     * Returns the computers bad password time.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675243(v=vs.85).aspx
     *
     * @return string
     */
    public function getBadPasswordTime()
    {
        return $this->getFirstAttribute($this->schema->badPasswordTime());
    }

    /**
     * Returns the computers account expiry date.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675098(v=vs.85).aspx
     *
     * @return string
     */
    public function getAccountExpiry()
    {
        return $this->getFirstAttribute($this->schema->accountExpires());
    }
}
