<?php

declare(strict_types=1);

namespace PHPHtmlParser\DTO\Selector;

final class RuleDTO
{
    /**
     * @var string
     */
    private $tag;

    /**
     * @var string
     */
    private $operator;

    /**
     * @var string|array|null
     */
    private $key;

    /**
     * @var string|array|null
     */
    private $value;

    /**
     * @var bool
     */
    private $noKey;

    /**
     * @var bool
     */
    private $alterNext;

    private function __construct(array $values)
    {
        $this->tag = $values['tag'];
        $this->operator = $values['operator'];
        $this->key = $values['key'];
        $this->value = $values['value'];
        $this->noKey = $values['noKey'];
        $this->alterNext = $values['alterNext'];
    }

    /**
     * @param string|array|null $key
     * @param string|array|null $value
     */
    public static function makeFromPrimitives(string $tag, string $operator, $key, $value, bool $noKey, bool $alterNext): RuleDTO
    {
        return new RuleDTO([
            'tag'       => $tag,
            'operator'  => $operator,
            'key'       => $key,
            'value'     => $value,
            'noKey'     => $noKey,
            'alterNext' => $alterNext,
        ]);
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return string|array|null
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string|array|null
     */
    public function getValue()
    {
        return $this->value;
    }

    public function isNoKey(): bool
    {
        return $this->noKey;
    }

    public function isAlterNext(): bool
    {
        return $this->alterNext;
    }
}
