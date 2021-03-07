<?php

declare(strict_types=1);

namespace PHPHtmlParser\Dom;

use PHPHtmlParser\Contracts\Dom\CleanerInterface;
use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Options;

class Cleaner implements CleanerInterface
{
    /**
     * Cleans the html of any none-html information.
     *
     * @throws LogicalException
     */
    public function clean(string $str, Options $options, string $defaultCharset): string
    {
        if (!$options->isCleanupInput()) {
            // skip entire cleanup step
            return $str;
        }

        // check if the string is gziped
        $is_gzip = 0 === \mb_strpos($str, "\x1f" . "\x8b" . "\x08", 0, 'US-ASCII');
        if ($is_gzip) {
            $str = \gzdecode($str);
            if ($str === false) {
                throw new LogicalException('gzdecode returned false. Error when trying to decode the string.');
            }
        }

        // we must handle character encoding
        $str = $this->setUpRegexEncoding($str, $options, $defaultCharset);

        // remove white space before closing tags
        $str = \mb_eregi_replace("'\s+>", "'>", $str);
        if ($str === false) {
            throw new LogicalException('mb_eregi_replace returned false instead of a string. Error when attempting to clean single quotes.');
        }
        $str = \mb_eregi_replace('"\s+>', '">', $str);
        if ($str === false) {
            throw new LogicalException('mb_eregi_replace returned false instead of a string. Error when attempting to clean double quotes.');
        }

        // clean out the \n\r
        $replace = ' ';
        if ($options->isPreserveLineBreaks()) {
            $replace = '&#10;';
        }
        $str = \str_replace(["\r\n", "\r", "\n"], $replace, $str);
        if ($str === false) {
            throw new LogicalException('str_replace returned false instead of a string. Error when attempting to clean input string.');
        }

        // strip the doctype
        $str = \mb_eregi_replace('<!doctype(.*?)>', '', $str);
        if ($str === false) {
            throw new LogicalException('mb_eregi_replace returned false instead of a string. Error when attempting to strip the doctype.');
        }

        // strip out comments
        $str = \mb_eregi_replace('<!--(.*?)-->', '', $str);
        if ($str === false) {
            throw new LogicalException('mb_eregi_replace returned false instead of a string. Error when attempting to strip comments.');
        }

        // strip out cdata
        $str = \mb_eregi_replace("<!\[CDATA\[(.*?)\]\]>", '', $str);
        if ($str === false) {
            throw new LogicalException('mb_eregi_replace returned false instead of a string. Error when attempting to strip out cdata.');
        }

        // strip out <script> tags
        if ($options->isRemoveScripts()) {
            $str = \mb_eregi_replace("<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>", '', $str);
            if ($str === false) {
                throw new LogicalException('mb_eregi_replace returned false instead of a string. Error when attempting to remove scripts 1.');
            }
            $str = \mb_eregi_replace("<\s*script\s*>(.*?)<\s*/\s*script\s*>", '', $str);
            if ($str === false) {
                throw new LogicalException('mb_eregi_replace returned false instead of a string. Error when attempting to remove scripts 2.');
            }
        }

        // strip out <style> tags
        if ($options->isRemoveStyles()) {
            $str = \mb_eregi_replace("<\s*style[^>]*[^/]>(.*?)<\s*/\s*style\s*>", '', $str);
            if ($str === false) {
                throw new LogicalException('mb_eregi_replace returned false instead of a string. Error when attempting to strip out style tags 1.');
            }
            $str = \mb_eregi_replace("<\s*style\s*>(.*?)<\s*/\s*style\s*>", '', $str);
            if ($str === false) {
                throw new LogicalException('mb_eregi_replace returned false instead of a string. Error when attempting to strip out style tags 2.');
            }
        }

        // strip smarty scripts
        if ($options->isRemoveSmartyScripts()) {
            $str = \mb_eregi_replace("(\{\w)(.*?)(\})", '', $str);
            if ($str === false) {
                throw new LogicalException('mb_eregi_replace returned false instead of a string. Error when attempting to remove smarty scripts.');
            }
        }

        return $str;
    }

    /**
     * Sets up the mb_regex_encoding and converts the text to that encoding.
     *
     * @throws LogicalException
     */
    private function setUpRegexEncoding(string $str, Options $options, string $defaultCharset): string
    {
        $encoding = $defaultCharset;
        $enforceEncoding = $options->getEnforceEncoding();
        if ($enforceEncoding !== null) {
            //  they want to enforce the given encoding
            $encoding = $enforceEncoding;
        }

        if (!\mb_regex_encoding($encoding)) {
            throw new LogicalException('Character encoding was not able to be changed to ' . $encoding . '.');
        }

        return \mb_convert_encoding($str, $encoding);
    }
}
