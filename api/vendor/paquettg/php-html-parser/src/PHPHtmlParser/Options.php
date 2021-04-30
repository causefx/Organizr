<?php

declare(strict_types=1);

namespace PHPHtmlParser;

class Options
{
    /**
     * The whitespaceTextNode, by default true, option tells the parser to save textnodes even if the content of the
     * node is empty (only whitespace). Setting it to false will ignore all whitespace only text node found in the document.
     *
     * @var bool
     */
    private $whitespaceTextNode = true;

    /**
     * Strict, by default false, will throw a StrictException if it finds that the html is not strictly compliant
     * (all tags must have a closing tag, no attribute with out a value, etc.).
     *
     * @var bool
     */
    private $strict = false;

    /**
     * The enforceEncoding, by default null, option will enforce an character set to be used for reading the content
     * and returning the content in that encoding. Setting it to null will trigger an attempt to figure out
     * the encoding from within the content of the string given instead.
     *
     * @var ?string
     */
    private $enforceEncoding;

    /**
     * Set this to false to skip the entire clean up phase of the parser. Defaults to true.
     *
     * @var bool
     */
    private $cleanupInput = true;

    /**
     * Set this to false to skip removing the script tags from the document body. This might have adverse effects.
     * Defaults to true.
     *
     * NOTE: Ignored if cleanupInit is true.
     *
     * @var bool
     */
    private $removeScripts = true;

    /**
     * Set this to false to skip removing of style tags from the document body. This might have adverse effects. Defaults to true.
     *
     * NOTE: Ignored if cleanupInit is true.
     *
     * @var bool
     */
    private $removeStyles = true;

    /**
     * Preserves Line Breaks if set to true. If set to false line breaks are cleaned up
     * as part of the input clean up process. Defaults to false.
     *
     * NOTE: Ignored if cleanupInit is true.
     *
     * @var bool
     */
    private $preserveLineBreaks = false;

    /**
     * Set this to false if you want to preserve whitespace inside of text nodes. It is set to true by default.
     *
     * @var bool
     */
    private $removeDoubleSpace = true;

    /**
     * Set this to false if you want to preserve smarty script found in the html content. It is set to true by default.
     *
     * @var bool
     */
    private $removeSmartyScripts = true;

    /**
     * By default this is set to false. Setting this to true will apply the php function htmlspecialchars_decode too all attribute values and text nodes.
     *
     * @var bool
     */
    private $htmlSpecialCharsDecode = false;

    /**
     * A list of tags which will always be self closing.
     *
     * @var string[]
     */
    private $selfClosing = [
        'area',
        'base',
        'basefont',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'keygen',
        'link',
        'meta',
        'param',
        'source',
        'spacer',
        'track',
        'wbr',
    ];

    /**
     * A list of tags where there should be no /> at the end (html5 style).
     *
     * @var string[]
     */
    private $noSlash = [];

    public function isWhitespaceTextNode(): bool
    {
        return $this->whitespaceTextNode;
    }

    public function setWhitespaceTextNode(bool $whitespaceTextNode): Options
    {
        $this->whitespaceTextNode = $whitespaceTextNode;

        return clone $this;
    }

    public function isStrict(): bool
    {
        return $this->strict;
    }

    public function setStrict(bool $strict): Options
    {
        $this->strict = $strict;

        return clone $this;
    }

    public function getEnforceEncoding(): ?string
    {
        return $this->enforceEncoding;
    }

    public function setEnforceEncoding(?string $enforceEncoding): Options
    {
        $this->enforceEncoding = $enforceEncoding;

        return clone $this;
    }

    public function isCleanupInput(): bool
    {
        return $this->cleanupInput;
    }

    public function setCleanupInput(bool $cleanupInput): Options
    {
        $this->cleanupInput = $cleanupInput;

        return clone $this;
    }

    public function isRemoveScripts(): bool
    {
        return $this->removeScripts;
    }

    public function setRemoveScripts(bool $removeScripts): Options
    {
        $this->removeScripts = $removeScripts;

        return clone $this;
    }

    public function isRemoveStyles(): bool
    {
        return $this->removeStyles;
    }

    public function setRemoveStyles(bool $removeStyles): Options
    {
        $this->removeStyles = $removeStyles;

        return clone $this;
    }

    public function isPreserveLineBreaks(): bool
    {
        return $this->preserveLineBreaks;
    }

    public function setPreserveLineBreaks(bool $preserveLineBreaks): Options
    {
        $this->preserveLineBreaks = $preserveLineBreaks;

        return clone $this;
    }

    public function isRemoveDoubleSpace(): bool
    {
        return $this->removeDoubleSpace;
    }

    public function setRemoveDoubleSpace(bool $removeDoubleSpace): Options
    {
        $this->removeDoubleSpace = $removeDoubleSpace;

        return clone $this;
    }

    public function isRemoveSmartyScripts(): bool
    {
        return $this->removeSmartyScripts;
    }

    public function setRemoveSmartyScripts(bool $removeSmartyScripts): Options
    {
        $this->removeSmartyScripts = $removeSmartyScripts;

        return clone $this;
    }

    public function isHtmlSpecialCharsDecode(): bool
    {
        return $this->htmlSpecialCharsDecode;
    }

    public function setHtmlSpecialCharsDecode(bool $htmlSpecialCharsDecode): Options
    {
        $this->htmlSpecialCharsDecode = $htmlSpecialCharsDecode;

        return clone $this;
    }

    /**
     * @return string[]
     */
    public function getSelfClosing(): array
    {
        return $this->selfClosing;
    }

    public function setSelfClosing(array $selfClosing): Options
    {
        $this->selfClosing = $selfClosing;

        return clone $this;
    }

    /**
     * Adds the tag to the list of tags that will always be self closing.
     */
    public function addSelfClosingTag(string $tag): Options
    {
        $this->selfClosing[] = $tag;

        return clone $this;
    }

    /**
     * Adds the tags to the list of tags that will always be self closing.
     *
     * @param string[] $tags
     */
    public function addSelfClosingTags(array $tags): Options
    {
        foreach ($tags as $tag) {
            $this->selfClosing[] = $tag;
        }

        return clone $this;
    }

    /**
     * Removes the tag from the list of tags that will always be self closing.
     */
    public function removeSelfClosingTag(string $tag): Options
    {
        $tags = [$tag];
        $this->selfClosing = \array_diff($this->selfClosing, $tags);

        return clone $this;
    }

    /**
     * Sets the list of self closing tags to empty.
     */
    public function clearSelfClosingTags(): Options
    {
        $this->selfClosing = [];

        return clone $this;
    }

    /**
     * @return string[]
     */
    public function getNoSlash(): array
    {
        return $this->noSlash;
    }

    /**
     * @param string[] $noSlash
     */
    public function setNoSlash(array $noSlash): Options
    {
        $this->noSlash = $noSlash;

        return clone $this;
    }

    /**
     * Adds a tag to the list of self closing tags that should not have a trailing slash.
     */
    public function addNoSlashTag(string $tag): Options
    {
        $this->noSlash[] = $tag;

        return clone $this;
    }

    /**
     * Removes a tag from the list of no-slash tags.
     */
    public function removeNoSlashTag(string $tag): Options
    {
        $tags = [$tag];
        $this->noSlash = \array_diff($this->noSlash, $tags);

        return clone $this;
    }

    /**
     * Empties the list of no-slash tags.
     */
    public function clearNoSlashTags(): Options
    {
        $this->noSlash = [];

        return clone $this;
    }

    public function setFromOptions(Options $options): Options
    {
        return $this->setCleanupInput($options->isCleanupInput())
            ->setEnforceEncoding($options->getEnforceEncoding())
            ->setHtmlSpecialCharsDecode($options->isHtmlSpecialCharsDecode())
            ->setPreserveLineBreaks($options->isPreserveLineBreaks())
            ->setRemoveDoubleSpace($options->isRemoveDoubleSpace())
            ->setRemoveScripts($options->isRemoveScripts())
            ->setRemoveSmartyScripts($options->isRemoveSmartyScripts())
            ->setRemoveStyles($options->isRemoveStyles())
            ->setStrict($options->isStrict())
            ->setWhitespaceTextNode($options->isWhitespaceTextNode())
            ->setSelfClosing($options->getSelfClosing())
            ->setNoSlash($options->getNoSlash());
    }
}
