<?php
namespace Adldap\Models;

use Adldap\Models\Concerns\HasMemberOf;

/**
 * Class ForeignSecurityPrincipal
 *
 * Represents an LDAP ForeignSecurityPrincipal.
 *
 * @package Adldap\Models
 */
class ForeignSecurityPrincipal extends Entry
{
    use HasMemberOf;
}
