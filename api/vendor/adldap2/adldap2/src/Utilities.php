<?php

namespace Adldap;

class Utilities
{
    /**
     * Converts a DN string into an array of RDNs.
     *
     * This will also decode hex characters into their true
     * UTF-8 representation embedded inside the DN as well.
     *
     * @param string $dn
     * @param bool   $removeAttributePrefixes
     *
     * @return array|false
     */
    public static function explodeDn($dn, $removeAttributePrefixes = true)
    {
        $dn = ldap_explode_dn($dn, ($removeAttributePrefixes ? 1 : 0));

        if (is_array($dn) && array_key_exists('count', $dn)) {
            foreach ($dn as $rdn => $value) {
                $dn[$rdn] = self::unescape($value);
            }
        }

        return $dn;
    }

    /**
     * Returns true / false if the current
     * PHP install supports escaping values.
     *
     * @return bool
     */
    public static function isEscapingSupported()
    {
        return function_exists('ldap_escape');
    }

    /**
     * Returns an escaped string for use in an LDAP filter.
     *
     * @param string $value
     * @param string $ignore
     * @param $flags
     *
     * @return string
     */
    public static function escape($value, $ignore = '', $flags = 0)
    {
        if (!static::isEscapingSupported()) {
            return static::escapeManual($value, $ignore, $flags);
        }

        return ldap_escape($value, $ignore, $flags);
    }

    /**
     * Escapes the inserted value for LDAP.
     *
     * @param string $value
     * @param string $ignore
     * @param int    $flags
     *
     * @return string
     */
    protected static function escapeManual($value, $ignore = '', $flags = 0)
    {
        // If a flag was supplied, we'll send the value off
        // to be escaped using the PHP flag values
        // and return the result.
        if ($flags) {
            return static::escapeManualWithFlags($value, $ignore, $flags);
        }

        // Convert ignore string into an array.
        $ignores = static::ignoreStrToArray($ignore);

        // Convert the value to a hex string.
        $hex = bin2hex($value);

        // Separate the string, with the hex length of 2, and
        // place a backslash on the end of each section.
        $value = chunk_split($hex, 2, '\\');

        // We'll append a backslash at the front of the string
        // and remove the ending backslash of the string.
        $value = '\\'.substr($value, 0, -1);

        // Go through each character to ignore.
        foreach ($ignores as $charToIgnore) {
            // Convert the character to ignore to a hex.
            $hexed = bin2hex($charToIgnore);

            // Replace the hexed variant with the original character.
            $value = str_replace('\\'.$hexed, $charToIgnore, $value);
        }

        // Finally we can return the escaped value.
        return $value;
    }

    /**
     * Escapes the inserted value with flags. Supplying either 1
     * or 2 into the flags parameter will escape only certain values.
     *
     * @param string $value
     * @param string $ignore
     * @param int    $flags
     *
     * @return string
     */
    protected static function escapeManualWithFlags($value, $ignore = '', $flags = 0)
    {
        // Convert ignore string into an array
        $ignores = static::ignoreStrToArray($ignore);

        // The escape characters for search filters
        $escapeFilter = ['\\', '*', '(', ')'];

        // The escape characters for distinguished names
        $escapeDn = ['\\', ',', '=', '+', '<', '>', ';', '"', '#'];

        switch ($flags) {
            case 1:
                // Int 1 equals to LDAP_ESCAPE_FILTER
                $escapes = $escapeFilter;
                break;
            case 2:
                // Int 2 equals to LDAP_ESCAPE_DN
                $escapes = $escapeDn;
                break;
            case 3:
                // If both LDAP_ESCAPE_FILTER and LDAP_ESCAPE_DN are used
                $escapes = array_unique(array_merge($escapeDn, $escapeFilter));
                break;
            default:
                // We've been given an invalid flag, we'll escape everything to be safe.
                return static::escapeManual($value, $ignore);
        }

        foreach ($escapes as $escape) {
            // Make sure the escaped value isn't being ignored.
            if (!in_array($escape, $ignores)) {
                $hexed = static::escape($escape);

                $value = str_replace($escape, $hexed, $value);
            }
        }

        return $value;
    }

    /**
     * Un-escapes a hexadecimal string into
     * its original string representation.
     *
     * @param string $value
     *
     * @return string
     */
    public static function unescape($value)
    {
        $callback = function ($matches) {
            return chr(hexdec($matches[1]));
        };

        return preg_replace_callback('/\\\([0-9A-Fa-f]{2})/', $callback, $value);
    }

    /**
     * Convert a binary SID to a string SID.
     *
     * @author Chad Sikorra
     *
     * @link https://github.com/ChadSikorra
     * @link https://stackoverflow.com/questions/39533560/php-ldap-get-user-sid
     *
     * @param string $value The Binary SID
     *
     * @return string|null
     */
    public static function binarySidToString($value)
    {
        // Revision - 8bit unsigned int (C1)
        // Count - 8bit unsigned int (C1)
        // 2 null bytes
        // ID - 32bit unsigned long, big-endian order
        $sid = @unpack('C1rev/C1count/x2/N1id', $value);

        if (!isset($sid['id']) || !isset($sid['rev'])) {
            return;
        }

        $revisionLevel = $sid['rev'];

        $identifierAuthority = $sid['id'];

        $subs = isset($sid['count']) ? $sid['count'] : 0;

        $sidHex = $subs ? bin2hex($value) : '';

        $subAuthorities = [];

        // The sub-authorities depend on the count, so only get as
        // many as the count, regardless of data beyond it.
        for ($i = 0; $i < $subs; $i++) {
            $data = implode('', array_reverse(
                str_split(
                    substr($sidHex, 16 + ($i * 8), 8),
                    2
                )
            ));

            $subAuthorities[] = hexdec($data);
        }

        // Tack on the 'S-' and glue it all together...
        return 'S-'.$revisionLevel.'-'.$identifierAuthority.implode(
            preg_filter('/^/', '-', $subAuthorities)
        );
    }

    /**
     * Convert a binary GUID to a string GUID.
     *
     * @param string $binGuid
     *
     * @return string|null
     */
    public static function binaryGuidToString($binGuid)
    {
        if (trim($binGuid) == '' || is_null($binGuid)) {
            return;
        }

        $hex = unpack('H*hex', $binGuid)['hex'];

        $hex1 = substr($hex, -26, 2).substr($hex, -28, 2).substr($hex, -30, 2).substr($hex, -32, 2);
        $hex2 = substr($hex, -22, 2).substr($hex, -24, 2);
        $hex3 = substr($hex, -18, 2).substr($hex, -20, 2);
        $hex4 = substr($hex, -16, 4);
        $hex5 = substr($hex, -12, 12);

        $guid = sprintf('%s-%s-%s-%s-%s', $hex1, $hex2, $hex3, $hex4, $hex5);

        return $guid;
    }

    /**
     * Converts a string GUID to it's hex variant.
     *
     * @param string $string
     *
     * @return string
     */
    public static function stringGuidToHex($string)
    {
        $hex = '\\' . substr($string, 6, 2) . '\\' . substr($string, 4, 2) . '\\' . substr($string, 2, 2) . '\\' . substr($string, 0, 2);
        $hex = $hex . '\\' . substr($string, 11, 2) . '\\' . substr($string, 9, 2);
        $hex = $hex . '\\' . substr($string, 16, 2) . '\\' . substr($string, 14, 2);
        $hex = $hex . '\\' . substr($string, 19, 2) . '\\' . substr($string, 21, 2);
        $hex = $hex . '\\' . substr($string, 24, 2) . '\\' . substr($string, 26, 2) . '\\' . substr($string, 28, 2) . '\\' . substr($string, 30, 2) . '\\' . substr($string, 32, 2) . '\\' . substr($string, 34, 2);

        return $hex;
    }

    /**
     * Encode a password for transmission over LDAP.
     *
     * @param string $password The password to encode
     *
     * @return string
     */
    public static function encodePassword($password)
    {
        return iconv('UTF-8', 'UTF-16LE', '"'.$password.'"');
    }

    /**
     * Round a Windows timestamp down to seconds and remove
     * the seconds between 1601-01-01 and 1970-01-01.
     *
     * @param float $windowsTime
     *
     * @return float
     */
    public static function convertWindowsTimeToUnixTime($windowsTime)
    {
        return round($windowsTime / 10000000) - 11644473600;
    }

    /**
     * Convert a Unix timestamp to Windows timestamp.
     *
     * @param float $unixTime
     *
     * @return float
     */
    public static function convertUnixTimeToWindowsTime($unixTime)
    {
        return ($unixTime + 11644473600) * 10000000;
    }

    /**
     * Validates that the inserted string is an object SID.
     *
     * @param string $sid
     *
     * @return bool
     */
    public static function isValidSid($sid)
    {
        return (bool) preg_match("/^S-\d(-\d{1,10}){1,16}$/i", $sid);
    }

    /**
     * Validates that the inserted string is an object GUID.
     *
     * @param string $guid
     *
     * @return bool
     */
    public static function isValidGuid($guid)
    {
        return (bool) preg_match('/^([0-9a-fA-F]){8}(-([0-9a-fA-F]){4}){3}-([0-9a-fA-F]){12}$/', $guid);
    }

    /**
     * Converts an ignore string into an array.
     *
     * @param string $ignore
     *
     * @return array
     */
    protected static function ignoreStrToArray($ignore)
    {
        $ignore = trim($ignore);

        return $ignore ? str_split($ignore) : [];
    }
}
