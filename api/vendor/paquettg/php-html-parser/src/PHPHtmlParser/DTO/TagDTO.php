<?php

declare(strict_types=1);

namespace PHPHtmlParser\DTO;

use PHPHtmlParser\Dom\Node\HtmlNode;

final class TagDTO
{
    /**
     * @var bool
     */
    private $status;

    /**
     * @var bool
     */
    private $closing;

    /**
     * @var ?HtmlNode
     */
    private $node;

    /**
     * @var ?string
     */
    private $tag;

    private function __construct(array $values = [])
    {
        $this->status = $values['status'] ?? false;
        $this->closing = $values['closing'] ?? false;
        $this->node = $values['node'] ?? null;
        $this->tag = $values['tag'] ?? null;
    }

    public static function makeFromPrimitives(bool $status = false, bool $closing = false, ?HtmlNode $node = null, ?string $tag = null): TagDTO
    {
        return new TagDTO([
            'status'  => $status,
            'closing' => $closing,
            'node'    => $node,
            'tag'     => $tag,
        ]);
    }

    public function isStatus(): bool
    {
        return $this->status;
    }

    public function isClosing(): bool
    {
        return $this->closing;
    }

    /**
     * @return mixed
     */
    public function getNode(): ?HtmlNode
    {
        return $this->node;
    }

    /**
     * @return mixed
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }
}
