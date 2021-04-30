<?php

declare(strict_types=1);

namespace PHPHtmlParser\Dom\Node;

use PHPHtmlParser\Dom\Tag;
use PHPHtmlParser\Exceptions\LogicalException;

/**
 * Class TextNode.
 *
 * @property-read string    $outerhtml
 * @property-read string    $innerhtml
 * @property-read string    $innerText
 * @property-read string    $text
 * @property-read Tag       $tag
 * @property-read InnerNode $parent
 */
class TextNode extends LeafNode
{
    /**
     * This is a text node.
     *
     * @var Tag
     */
    protected $tag;

    /**
     * This is the text in this node.
     *
     * @var string
     */
    protected $text;

    /**
     * This is the converted version of the text.
     *
     * @var ?string
     */
    protected $convertedText;

    /**
     * Sets the text for this node.
     *
     * @param bool $removeDoubleSpace
     */
    public function __construct(string $text, $removeDoubleSpace = true)
    {
        if ($removeDoubleSpace) {
            // remove double spaces
            $replacedText = \mb_ereg_replace('\s+', ' ', $text);
            if ($replacedText === false) {
                throw new LogicalException('mb_ereg_replace returns false when attempting to clean white space from "' . $text . '".');
            }
            $text = $replacedText;
        }

        // restore line breaks
        $text = \str_replace('&#10;', "\n", $text);

        $this->text = $text;
        $this->tag = new Tag('text');
        parent::__construct();
    }

    /**
     * @param bool $htmlSpecialCharsDecode
     */
    public function setHtmlSpecialCharsDecode($htmlSpecialCharsDecode = false): void
    {
        parent::setHtmlSpecialCharsDecode($htmlSpecialCharsDecode);
        $this->tag->setHtmlSpecialCharsDecode($htmlSpecialCharsDecode);
    }

    /**
     * Returns the text of this node.
     */
    public function text(): string
    {
        if ($this->htmlSpecialCharsDecode) {
            $text = \htmlspecialchars_decode($this->text);
        } else {
            $text = $this->text;
        }
        // convert charset
        if (!\is_null($this->encode)) {
            if (!\is_null($this->convertedText)) {
                // we already know the converted value
                return $this->convertedText;
            }
            $text = $this->encode->convert($text);

            // remember the conversion
            $this->convertedText = $text;

            return $text;
        }

        return $text;
    }

    /**
     * Sets the text for this node.
     *
     * @var string
     */
    public function setText(string $text): void
    {
        $this->text = $text;
        if (!\is_null($this->encode)) {
            $text = $this->encode->convert($text);

            // remember the conversion
            $this->convertedText = $text;
        }
    }

    /**
     * This node has no html, just return the text.
     *
     * @uses $this->text()
     */
    public function innerHtml(): string
    {
        return $this->text();
    }

    /**
     * This node has no html, just return the text.
     *
     * @uses $this->text()
     */
    public function outerHtml(): string
    {
        return $this->text();
    }

    /**
     * Checks if the current node is a text node.
     */
    public function isTextNode(): bool
    {
        return true;
    }

    /**
     * Call this when something in the node tree has changed. Like a child has been added
     * or a parent has been changed.
     */
    protected function clear(): void
    {
        $this->convertedText = null;
    }
}
