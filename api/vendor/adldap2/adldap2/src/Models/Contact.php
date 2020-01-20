<?php

namespace Adldap\Models;

/**
 * Class Contact
 *
 * Represents an LDAP contact.
 *
 * @package Adldap\Models
 */
class Contact extends Entry
{
    use Concerns\HasMemberOf,
        Concerns\HasUserProperties;
}
