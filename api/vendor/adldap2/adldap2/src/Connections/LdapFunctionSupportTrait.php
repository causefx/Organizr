<?php

namespace Adldap\Connections;

trait LdapFunctionSupportTrait
{
    /**
     * Returns true / false if the current
     * PHP install supports LDAP.
     *
     * @return bool
     */
    public function isSupported()
    {
        return function_exists('ldap_connect');
    }

    /**
     * Returns true / false if the current
     * PHP install supports an SASL bound
     * LDAP connection.
     *
     * @return bool
     */
    public function isSaslSupported()
    {
        return function_exists('ldap_sasl_bind');
    }

    /**
     * Returns true / false if the current
     * PHP install supports LDAP paging.
     *
     * @return bool
     */
    public function isPagingSupported()
    {
        return function_exists('ldap_control_paged_result');
    }

    /**
     * Returns true / false if the current
     * PHP install supports batch modification.
     * Requires PHP 5.4 >= 5.4.26, PHP 5.5 >= 5.5.10 or PHP 5.6 >= 5.6.0.
     *
     * @return bool
     */
    public function isBatchSupported()
    {
        return function_exists('ldap_modify_batch');
    }
}
