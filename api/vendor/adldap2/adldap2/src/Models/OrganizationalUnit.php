<?php

namespace Adldap\Models;

use Adldap\Models\Concerns\HasDescription;

/**
 * Class OrganizationalUnit
 *
 * Represents an LDAP organizational unit.
 *
 * @package Adldap\Models
 */
class OrganizationalUnit extends Entry
{
    use HasDescription;

    /**
     * Retrieves the organization units OU attribute.
     *
     * @return string
     */
    public function getOu()
    {
        return $this->getFirstAttribute($this->schema->organizationalUnitShort());
    }

    /**
     * {@inheritdoc}
     */
    protected function getCreatableDn()
    {
        return $this->getDnBuilder()->addOU($this->getOu());
    }
}
