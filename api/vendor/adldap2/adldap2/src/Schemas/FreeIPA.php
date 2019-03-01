<?php

namespace Adldap\Schemas;

class FreeIPA extends Schema
{
    /**
     * {@inheritdoc}
     */
    public function distinguishedName()
    {
        return 'dn';
    }

    /**
     * {@inheritdoc}
     */
    public function objectCategory()
    {
        return 'objectclass';
    }

    /**
     * {@inheritdoc}
     */
    public function objectClassGroup()
    {
        return 'ipausergroup';
    }

    /**
     * {@inheritdoc}
     */
    public function distinguishedNameSubKey()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function filterEnabled()
    {
        return '(!(UserAccountControl:1.2.840.113556.1.4.803:=2))';
    }

    /**
     * {@inheritdoc}
     */
    public function filterDisabled()
    {
        return '(UserAccountControl:1.2.840.113556.1.4.803:=2)';
    }

    /**
     * {@inheritdoc}
     */
    public function lockoutTime()
    {
        return 'lockouttime';
    }

    /**
     * {@inheritdoc}
     */
    public function objectClassOu()
    {
        return 'organizationalunit';
    }

    /**
     * {@inheritdoc}
     */
    public function objectClassPerson()
    {
        return 'person';
    }

    /**
     * {@inheritdoc}
     */
    public function objectClassUser()
    {
        return 'organizationalPerson';
    }

    /**
     * {@inheritdoc}
     */
    public function objectGuid()
    {
        return 'objectguid';
    }

    /**
     * {@inheritdoc}
     */
    public function objectGuidRequiresConversion()
    {
        return true;
    }
}
