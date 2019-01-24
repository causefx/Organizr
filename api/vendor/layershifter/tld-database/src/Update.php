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
use LayerShifter\TLDDatabase\Exceptions\UpdateException;
use LayerShifter\TLDDatabase\Http\AdapterInterface;
use LayerShifter\TLDDatabase\Http\CurlAdapter;

/**
 * Class that performs database update with actual data from Public Suffix List.
 */
class Update
{
    /**
     * @const string URL to Public Suffix List file.
     * @todo Switch back after https://github.com/publicsuffix/list/issues/724 will be resolved
     */
    const PUBLIC_SUFFIX_LIST_URL = 'https://raw.githubusercontent.com/publicsuffix/list/master/public_suffix_list.dat';

    /**
     * @var AdapterInterface Object of HTTP adapter.
     */
    private $httpAdapter;
    /**
     * @var string Output filename.
     */
    private $outputFileName;

    /**
     * Parser constructor.
     *
     * @param string $outputFileName Filename of target file
     * @param string $httpAdapter    Optional class name of custom HTTP adapter
     *
     * @throws UpdateException
     */
    public function __construct($outputFileName = null, $httpAdapter = null)
    {
        /*
         * Defining output filename.
         * */

        $this->outputFileName = null === $outputFileName
            ? __DIR__ . Store::DATABASE_FILE
            : $outputFileName;

        /*
         * Defining HTTP adapter.
         * */

        if (null === $httpAdapter) {
            $this->httpAdapter = new CurlAdapter();

            return;
        }

        if (!class_exists($httpAdapter)) {
            throw new Exceptions\UpdateException(sprintf('Class "%s" is not defined', $httpAdapter));
        }

        $this->httpAdapter = new $httpAdapter();

        if (!($this->httpAdapter instanceof AdapterInterface)) {
            throw new Exceptions\UpdateException(sprintf('Class "%s" is implements adapter interface', $httpAdapter));
        }
    }

    /**
     * Fetches actual Public Suffix List and writes obtained suffixes to target file.
     *
     * @return void
     *
     * @throws IOException
     */
    public function run()
    {
        /*
         * Fetching Public Suffix List and parse suffixes.
         * */

        $lines = $this->httpAdapter->get(Update::PUBLIC_SUFFIX_LIST_URL);

        $parser = new Parser($lines);
        $suffixes = $parser->parse();

        /*
         * Write file with exclusive file write lock.
         * */

        $handle = @fopen($this->outputFileName, 'w+');

        if ($handle === false) {
            throw new Exceptions\IOException(error_get_last()['message']);
        }

        if (!flock($handle, LOCK_EX)) {
            throw new Exceptions\IOException(sprintf('Cannot obtain lock to output file (%s)', $this->outputFileName));
        }

        $suffixFile = '<?php' . PHP_EOL . 'return ' . var_export($suffixes, true) . ';';
        $writtenBytes = fwrite($handle, $suffixFile);

        if ($writtenBytes === false || $writtenBytes !== strlen($suffixFile)) {
            throw new Exceptions\IOException(sprintf('Write to output file (%s) failed', $this->outputFileName));
        }

        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
