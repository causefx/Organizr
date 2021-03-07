<?php

declare(strict_types=1);

namespace PHPHtmlParser\Dom\Node;

use PHPHtmlParser\Contracts\Selector\SelectorInterface;
use PHPHtmlParser\Dom\Tag;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\ParentNotFoundException;
use PHPHtmlParser\Exceptions\Tag\AttributeNotFoundException;
use PHPHtmlParser\Finder;
use PHPHtmlParser\Selector\Selector;
use stringEncode\Encode;

/**
 * Dom node object.
 *
 * @property-read string    $outerhtml
 * @property-read string    $innerhtml
 * @property-read string    $innerText
 * @property-read string    $text
 * @property-read Tag       $tag
 * @property-read InnerNode $parent
 */
abstract class AbstractNode
{
    /**
     * Contains the tag name/type.
     *
     * @var ?Tag
     */
    protected $tag;

    /**
     * Contains a list of attributes on this tag.
     *
     * @var array
     */
    protected $attr = [];

    /**
     * Contains the parent Node.
     *
     * @var ?InnerNode
     */
    protected $parent;

    /**
     * The unique id of the class. Given by PHP.
     *
     * @var int
     */
    protected $id;

    /**
     * The encoding class used to encode strings.
     *
     * @var mixed
     */
    protected $encode;

    /**
     * An array of all the children.
     *
     * @var array
     */
    protected $children = [];

    /**
     * @var bool
     */
    protected $htmlSpecialCharsDecode = false;
    /**
     * @var int
     */
    private static $count = 0;

    /**
     * Creates a unique id for this node.
     */
    public function __construct()
    {
        $this->id = self::$count;
        ++self::$count;
    }

    /**
     * Attempts to clear out any object references.
     */
    public function __destruct()
    {
        $this->tag = null;
        $this->parent = null;
        $this->attr = [];
        $this->children = [];
    }

    /**
     * Magic get method for attributes and certain methods.
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        // check attribute first
        if ($this->getAttribute($key) !== null) {
            return $this->getAttribute($key);
        }
        switch (\strtolower($key)) {
            case 'outerhtml':
                return $this->outerHtml();
            case 'innerhtml':
                return $this->innerHtml();
            case 'innertext':
                return $this->innerText();
            case 'text':
                return $this->text();
            case 'tag':
                return $this->getTag();
            case 'parent':
                return $this->getParent();
        }
    }

    /**
     * Simply calls the outer text method.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->outerHtml();
    }

    /**
     * @param bool $htmlSpecialCharsDecode
     */
    public function setHtmlSpecialCharsDecode($htmlSpecialCharsDecode = false): void
    {
        $this->htmlSpecialCharsDecode = $htmlSpecialCharsDecode;
    }

    /**
     * Returns the id of this object.
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * Returns the parent of node.
     *
     * @return InnerNode
     */
    public function getParent(): ?InnerNode
    {
        return $this->parent;
    }

    /**
     * Sets the parent node.
     *
     * @throws ChildNotFoundException
     * @throws CircularException
     */
    public function setParent(InnerNode $parent): AbstractNode
    {
        // remove from old parent
        if ($this->parent !== null) {
            if ($this->parent->id() == $parent->id()) {
                // already the parent
                return $this;
            }

            $this->parent->removeChild($this->id);
        }

        $this->parent = $parent;

        // assign child to parent
        $this->parent->addChild($this);

        return $this;
    }

    /**
     * Removes this node and all its children from the
     * DOM tree.
     *
     * @return void
     */
    public function delete()
    {
        if ($this->parent !== null) {
            $this->parent->removeChild($this->id);
        }
        $this->parent->clear();
        $this->clear();
    }

    /**
     * Sets the encoding class to this node.
     *
     * @return void
     */
    public function propagateEncoding(Encode $encode)
    {
        $this->encode = $encode;
        $this->tag->setEncoding($encode);
    }

    /**
     * Checks if the given node id is an ancestor of
     * the current node.
     */
    public function isAncestor(int $id): bool
    {
        if ($this->getAncestor($id) !== null) {
            return true;
        }

        return false;
    }

    /**
     * Attempts to get an ancestor node by the given id.
     *
     * @return AbstractNode|null
     */
    public function getAncestor(int $id)
    {
        if ($this->parent !== null) {
            if ($this->parent->id() == $id) {
                return $this->parent;
            }

            return $this->parent->getAncestor($id);
        }
    }

    /**
     * Checks if the current node has a next sibling.
     */
    public function hasNextSibling(): bool
    {
        try {
            $this->nextSibling();

            // sibling found, return true;
            return true;
        } catch (ParentNotFoundException $e) {
            // no parent, no next sibling
            unset($e);

            return false;
        } catch (ChildNotFoundException $e) {
            // no sibling found
            unset($e);

            return false;
        }
    }

    /**
     * Attempts to get the next sibling.
     *
     * @throws ChildNotFoundException
     * @throws ParentNotFoundException
     */
    public function nextSibling(): AbstractNode
    {
        if ($this->parent === null) {
            throw new ParentNotFoundException('Parent is not set for this node.');
        }

        return $this->parent->nextChild($this->id);
    }

    /**
     * Attempts to get the previous sibling.
     *
     * @throws ChildNotFoundException
     * @throws ParentNotFoundException
     */
    public function previousSibling(): AbstractNode
    {
        if ($this->parent === null) {
            throw new ParentNotFoundException('Parent is not set for this node.');
        }

        return $this->parent->previousChild($this->id);
    }

    /**
     * Gets the tag object of this node.
     */
    public function getTag(): Tag
    {
        return $this->tag;
    }

    /**
     * Replaces the tag for this node.
     *
     * @param string|Tag $tag
     */
    public function setTag($tag): AbstractNode
    {
        if (\is_string($tag)) {
            $tag = new Tag($tag);
        }

        $this->tag = $tag;

        // clear any cache
        $this->clear();

        return $this;
    }

    /**
     * A wrapper method that simply calls the getAttribute method
     * on the tag of this node.
     */
    public function getAttributes(): array
    {
        $attributes = $this->tag->getAttributes();
        foreach ($attributes as $name => $attributeDTO) {
            $attributes[$name] = $attributeDTO->getValue();
        }

        return $attributes;
    }

    /**
     * A wrapper method that simply calls the getAttribute method
     * on the tag of this node.
     */
    public function getAttribute(string $key): ?string
    {
        try {
            $attributeDTO = $this->tag->getAttribute($key);
        } catch (AttributeNotFoundException $e) {
            // no attribute with this key exists, returning null.
            unset($e);

            return null;
        }

        return $attributeDTO->getValue();
    }

    /**
     * A wrapper method that simply calls the hasAttribute method
     * on the tag of this node.
     */
    public function hasAttribute(string $key): bool
    {
        return $this->tag->hasAttribute($key);
    }

    /**
     * A wrapper method that simply calls the setAttribute method
     * on the tag of this node.
     */
    public function setAttribute(string $key, ?string $value, bool $doubleQuote = true): AbstractNode
    {
        $this->tag->setAttribute($key, $value, $doubleQuote);

        //clear any cache
        $this->clear();

        return $this;
    }

    /**
     * A wrapper method that simply calls the removeAttribute method
     * on the tag of this node.
     */
    public function removeAttribute(string $key): void
    {
        $this->tag->removeAttribute($key);

        //clear any cache
        $this->clear();
    }

    /**
     * A wrapper method that simply calls the removeAllAttributes
     * method on the tag of this node.
     */
    public function removeAllAttributes(): void
    {
        $this->tag->removeAllAttributes();

        //clear any cache
        $this->clear();
    }

    /**
     * Function to locate a specific ancestor tag in the path to the root.
     *
     * @throws ParentNotFoundException
     */
    public function ancestorByTag(string $tag): AbstractNode
    {
        // Start by including ourselves in the comparison.
        $node = $this;

        do {
            if ($node->tag->name() == $tag) {
                return $node;
            }

            $node = $node->getParent();
        } while ($node !== null);

        throw new ParentNotFoundException('Could not find an ancestor with "' . $tag . '" tag');
    }

    /**
     * Find elements by css selector.
     *
     * @throws ChildNotFoundException
     *
     * @return mixed|Collection|null
     */
    public function find(string $selectorString, ?int $nth = null, ?SelectorInterface $selector = null)
    {
        if (\is_null($selector)) {
            $selector = new Selector($selectorString);
        }

        $nodes = $selector->find($this);

        if ($nth !== null) {
            // return nth-element or array
            if (isset($nodes[$nth])) {
                return $nodes[$nth];
            }

            return;
        }

        return $nodes;
    }

    /**
     * Find node by id.
     *
     * @throws ChildNotFoundException
     * @throws ParentNotFoundException
     *
     * @return bool|AbstractNode
     */
    public function findById(int $id)
    {
        $finder = new Finder($id);

        return $finder->find($this);
    }

    /**
     * Gets the inner html of this node.
     */
    abstract public function innerHtml(): string;

    /**
     * Gets the html of this node, including it's own
     * tag.
     */
    abstract public function outerHtml(): string;

    /**
     * Gets the text of this node (if there is any text).
     */
    abstract public function text(): string;

    /**
     * Check is node type textNode.
     */
    public function isTextNode(): bool
    {
        return false;
    }

    /**
     * Call this when something in the node tree has changed. Like a child has been added
     * or a parent has been changed.
     */
    abstract protected function clear(): void;
}
