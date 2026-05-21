<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Psr7;

use function array_merge;
use function is_string;

class Header
{
    private string $originalName;

    private string $normalizedName;

    private array $values;

    public function __construct(string $originalName, string $normalizedName, array $values)
    {
        $this->originalName = $originalName;
        $this->normalizedName = $normalizedName;
        $this->values = $values;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getNormalizedName(): string
    {
        return $this->normalizedName;
    }

    public function addValue(string $value): self
    {
        $this->values[] = $value;

        return $this;
    }

    public function addValues(array|string $values): self
    {
        if (is_string($values)) {
            return $this->addValue($values);
        }

        $this->values = array_merge($this->values, $values);

        return $this;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
