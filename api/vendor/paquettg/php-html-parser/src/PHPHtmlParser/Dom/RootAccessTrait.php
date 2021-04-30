<?php

declare(strict_types=1);

namespace PHPHtmlParser\Dom;

use PHPHtmlParser\Dom\Node\AbstractNode;
use PHPHtmlParser\Dom\Node\HtmlNode;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\NotLoadedException;

trait RootAccessTrait
{
    /**
     * Contains the root node of this dom tree.
     *
     * @var HtmlNode
     */
    public $root;

    /**
     * A simple wrapper around the root node.
     *
     * @param string $name
     *
     * @throws NotLoadedException
     *
     * @return mixed
     */
    public function __get($name)
    {
        $this->isLoaded();

        return $this->root->$name;
    }

    /**
     * Simple wrapper function that returns the first child.
     *
     * @throws ChildNotFoundException
     * @throws NotLoadedException
     */
    public function firstChild(): AbstractNode
    {
        $this->isLoaded();

        return $this->root->firstChild();
    }

    /**
     * Simple wrapper function that returns the last child.
     *
     * @throws ChildNotFoundException
     * @throws NotLoadedException
     */
    public function lastChild(): AbstractNode
    {
        $this->isLoaded();

        return $this->root->lastChild();
    }

    /**
     * Simple wrapper function that returns count of child elements.
     *
     * @throws NotLoadedException
     */
    public function countChildren(): int
    {
        $this->isLoaded();

        return $this->root->countChildren();
    }

    /**
     * Get array of children.
     *
     * @throws NotLoadedException
     */
    public function getChildren(): array
    {
        $this->isLoaded();

        return $this->root->getChildren();
    }

    /**
     * Check if node have children nodes.
     *
     * @throws NotLoadedException
     */
    public function hasChildren(): bool
    {
        $this->isLoaded();

        return $this->root->hasChildren();
    }

    abstract public function isLoaded(): void;
}
