<?php
/**
 * TLDDatabase: Abstraction for Public Suffix List in PHP.
 *
 * @link      https://github.com/layershifter/TLDDatabase
 *
 * @copyright Copyright (c) 2016, Alexander Fedyashov
 * @license   https://raw.githubusercontent.com/layershifter/TLDDatabase/master/LICENSE Apache 2.0 License
 */

namespace LayerShifter\TLDDatabase;

use LayerShifter\TLDDatabase\Exceptions\IOException;
use LayerShifter\TLDDatabase\Exceptions\StoreException;

/**
 * Class for operations with database from Public Suffix List.
 */
class Store
{
    /**
     * @const string Path to database which is supplied with library.
     */
    const DATABASE_FILE = '/../resources/database.php';
    /**
     * @const      int Type that is assigned when a suffix is ICANN TLD zone.
     *
     * @deprecated This constant is result of a typo, use `TYPE_ICANN` const instead.
     */
    const TYPE_ICCAN = 1;
    /**
     * @const int Type that is assigned when a suffix is ICANN TLD zone.
     */
    const TYPE_ICANN = 1;
    /**
     * @const int Type that is assigned when a suffix is private domain.
     */
    const TYPE_PRIVATE = 2;

    /**
     * @var array|int[] Array of suffixes where key is suffix and value is type of suffix.
     */
    private $suffixes;

    /**
     * Store constructor.
     *
     * @param string $databaseFile Optional, full path to database file.
     *
     * @throws IOException
     */
    public function __construct($databaseFile = null)
    {
        $databaseFile = null === $databaseFile
            ? __DIR__ . Store::DATABASE_FILE
            : $databaseFile;

        if (!file_exists($databaseFile)) {
            throw new IOException(sprintf('Database file (%s) does not exists', $databaseFile));
        }

        /** @noinspection PhpIncludeInspection */
        $this->suffixes = require $databaseFile;

        if (!is_array($this->suffixes)) {
            throw new IOException(sprintf(
                'Database file (%s) is seriously malformed, try reinstall package or run update again',
                $databaseFile
            ));
        }
    }

    /**
     * Checks existence of suffix entry in database. Returns true if suffix entry exists.
     *
     * @param string $suffix Suffix which existence will be checked in database.
     *
     * @return bool
     */
    public function isExists($suffix)
    {
        return array_key_exists($suffix, $this->suffixes);
    }

    /**
     * Checks type of suffix entry. Returns true if suffix is ICANN TLD zone.
     *
     * @param string $suffix Suffix which type will be checked.
     *
     * @return int
     *
     * @throws StoreException
     */
    public function getType($suffix)
    {
        if (!array_key_exists($suffix, $this->suffixes)) {
            throw new StoreException(sprintf(
                'Provided suffix (%s) does not exists in database, check existence of entry with isExists() method ' .
                'before',
                $suffix
            ));
        }

        return $this->suffixes[ $suffix ];
    }

    /**
     * Checks type of suffix entry. Returns true if suffix is ICANN TLD zone.
     *
     * @param string $suffix Suffix which type will be checked.
     *
     * @deprecated This method is result of a typo, use `isICANN` const instead
     *
     * @return bool
     */
    public function isICCAN($suffix)
    {
        return $this->isICANN($suffix);
    }

    /**
     * Checks type of suffix entry. Returns true if suffix is ICANN TLD zone.
     *
     * @param string $suffix Suffix which type will be checked.
     *
     * @return bool
     */
    public function isICANN($suffix)
    {
        return $this->getType($suffix) === Store::TYPE_ICANN;
    }

    /**
     * Checks type of suffix entry. Returns true if suffix is private.
     *
     * @param string $suffix Suffix which type will be checked.
     *
     * @return bool
     */
    public function isPrivate($suffix)
    {
        return $this->getType($suffix) === Store::TYPE_PRIVATE;
    }
}
