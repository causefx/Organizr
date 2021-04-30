<?php

declare(strict_types=1);

namespace PHPHtmlParser\DTO\Selector;

final class ParsedSelectorCollectionDTO
{
    /**
     * @var ParsedSelectorDTO[]
     */
    private $parsedSelectorDTO = [];

    /**
     * @param ParsedSelectorDTO[] $parsedSelectorDTOs
     */
    private function __construct(array $parsedSelectorDTOs)
    {
        foreach ($parsedSelectorDTOs as $parsedSelectorDTO) {
            if ($parsedSelectorDTO instanceof ParsedSelectorDTO) {
                $this->parsedSelectorDTO[] = $parsedSelectorDTO;
            }
        }
    }

    /**
     * @param ParsedSelectorDTO[] $parsedSelectorDTOs
     */
    public static function makeCollection(array $parsedSelectorDTOs): ParsedSelectorCollectionDTO
    {
        return new ParsedSelectorCollectionDTO($parsedSelectorDTOs);
    }

    /**
     * @return ParsedSelectorDTO[]
     */
    public function getParsedSelectorDTO(): array
    {
        return $this->parsedSelectorDTO;
    }
}
