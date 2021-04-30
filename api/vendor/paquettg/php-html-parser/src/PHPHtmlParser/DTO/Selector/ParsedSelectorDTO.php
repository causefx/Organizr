<?php

declare(strict_types=1);

namespace PHPHtmlParser\DTO\Selector;

final class ParsedSelectorDTO
{
    /**
     * @var RuleDTO[]
     */
    private $rules = [];

    /**
     * @param RuleDTO[] $ruleDTOs
     */
    private function __construct(array $ruleDTOs)
    {
        foreach ($ruleDTOs as $ruleDTO) {
            if ($ruleDTO instanceof RuleDTO) {
                $this->rules[] = $ruleDTO;
            }
        }
    }

    /**
     * @param RuleDTO[] $ruleDTOs
     */
    public static function makeFromRules(array $ruleDTOs): ParsedSelectorDTO
    {
        return new ParsedSelectorDTO($ruleDTOs);
    }

    /**
     * @return RuleDTO[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
