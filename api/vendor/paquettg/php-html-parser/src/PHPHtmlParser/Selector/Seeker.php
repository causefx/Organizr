<?php

declare(strict_types=1);

namespace PHPHtmlParser\Selector;

use PHPHtmlParser\Contracts\Selector\SeekerInterface;
use PHPHtmlParser\Dom\Node\AbstractNode;
use PHPHtmlParser\Dom\Node\InnerNode;
use PHPHtmlParser\Dom\Node\LeafNode;
use PHPHtmlParser\DTO\Selector\RuleDTO;
use PHPHtmlParser\Exceptions\ChildNotFoundException;

class Seeker implements SeekerInterface
{
    /**
     * Attempts to find all children that match the rule
     * given.
     *
     * @var InnerNode[]
     *
     * @throws ChildNotFoundException
     */
    public function seek(array $nodes, RuleDTO $rule, array $options): array
    {
        // XPath index
        if ($rule->getTag() !== null && \is_numeric($rule->getKey())) {
            $count = 0;
            foreach ($nodes as $node) {
                if ($rule->getTag() == '*'
                    || $rule->getTag() == $node->getTag()
                        ->name()
                ) {
                    ++$count;
                    if ($count == $rule->getKey()) {
                        // found the node we wanted
                        return [$node];
                    }
                }
            }

            return [];
        }

        $options = $this->flattenOptions($options);

        $return = [];
        foreach ($nodes as $node) {
            // check if we are a leaf
            if ($node instanceof LeafNode || !$node->hasChildren()
            ) {
                continue;
            }

            $children = [];
            $child = $node->firstChild();
            while (!\is_null($child)) {
                // wild card, grab all
                if ($rule->getTag() == '*' && \is_null($rule->getKey())) {
                    $return[] = $child;
                    $child = $this->getNextChild($node, $child);
                    continue;
                }

                $pass = $this->checkTag($rule, $child);
                if ($pass && $rule->getKey() !== null) {
                    $pass = $this->checkKey($rule, $child);
                }
                if ($pass &&
                    $rule->getKey() !== null &&
                    $rule->getValue() !== null &&
                    $rule->getValue() != '*'
                ) {
                    $pass = $this->checkComparison($rule, $child);
                }

                if ($pass) {
                    // it passed all checks
                    $return[] = $child;
                }
                // this child failed to be matched
                if ($child instanceof InnerNode && $child->hasChildren()
                ) {
                    if (!isset($options['checkGrandChildren'])
                        || $options['checkGrandChildren']
                    ) {
                        // we have a child that failed but are not leaves.
                        $matches = $this->seek([$child], $rule, $options);
                        foreach ($matches as $match) {
                            $return[] = $match;
                        }
                    }
                }

                $child = $this->getNextChild($node, $child);
            }

            if ((!isset($options['checkGrandChildren'])
                    || $options['checkGrandChildren'])
                && \count($children) > 0
            ) {
                // we have children that failed but are not leaves.
                $matches = $this->seek($children, $rule, $options);
                foreach ($matches as $match) {
                    $return[] = $match;
                }
            }
        }

        return $return;
    }

    /**
     * Checks comparison condition from rules against node.
     */
    private function checkComparison(RuleDTO $rule, AbstractNode $node): bool
    {
        if ($rule->getKey() == 'plaintext') {
            // plaintext search
            $nodeValue = $node->text();
            $result = $this->checkNodeValue($nodeValue, $rule, $node);
        } else {
            // normal search
            if (!\is_array($rule->getKey())) {
                $nodeValue = $node->getAttribute($rule->getKey());
                $result = $this->checkNodeValue($nodeValue, $rule, $node);
            } else {
                $result = true;
                foreach ($rule->getKey() as $index => $key) {
                    $nodeValue = $node->getAttribute($key);
                    $result = $result &&
                        $this->checkNodeValue($nodeValue, $rule, $node, $index);
                }
            }
        }

        return $result;
    }

    /**
     * Flattens the option array.
     *
     * @return array
     */
    private function flattenOptions(array $optionsArray)
    {
        $options = [];
        foreach ($optionsArray as $optionArray) {
            foreach ($optionArray as $key => $option) {
                $options[$key] = $option;
            }
        }

        return $options;
    }

    /**
     * Returns the next child or null if no more children.
     *
     * @return AbstractNode|null
     */
    private function getNextChild(
        AbstractNode $node,
        AbstractNode $currentChild
    ) {
        try {
            $child = null;
            if ($node instanceof InnerNode) {
                // get next child
                $child = $node->nextChild($currentChild->id());
            }
        } catch (ChildNotFoundException $e) {
            // no more children
            unset($e);
            $child = null;
        }

        return $child;
    }

    /**
     * Checks tag condition from rules against node.
     */
    private function checkTag(RuleDTO $rule, AbstractNode $node): bool
    {
        if (!empty($rule->getTag()) && $rule->getTag() != $node->getTag()->name()
            && $rule->getTag() != '*'
        ) {
            return false;
        }

        return true;
    }

    /**
     * Checks key condition from rules against node.
     */
    private function checkKey(RuleDTO $rule, AbstractNode $node): bool
    {
        if (!\is_array($rule->getKey())) {
            if ($rule->isNoKey()) {
                if ($node->getAttribute($rule->getKey()) !== null) {
                    return false;
                }
            } else {
                if ($rule->getKey() != 'plaintext'
                    && !$node->hasAttribute($rule->getKey())
                ) {
                    return false;
                }
            }
        } else {
            if ($rule->isNoKey()) {
                foreach ($rule->getKey() as $key) {
                    if (!\is_null($node->getAttribute($key))) {
                        return false;
                    }
                }
            } else {
                foreach ($rule->getKey() as $key) {
                    if ($key != 'plaintext'
                        && !$node->hasAttribute($key)
                    ) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function checkNodeValue(
        ?string $nodeValue,
        RuleDTO $rule,
        AbstractNode $node,
        ?int $index = null
    ): bool {
        $check = false;
        if (
            $rule->getValue() !== null &&
            \is_string($rule->getValue()) &&
            $nodeValue !== null
        ) {
            $check = $this->match($rule->getOperator(), $rule->getValue(), $nodeValue);
        }

        // handle multiple classes
        $key = $rule->getKey();
        if (
            !$check &&
            $key == 'class' &&
            \is_array($rule->getValue())
        ) {
            $nodeClasses = \explode(' ', $node->getAttribute('class') ?? '');
            foreach ($rule->getValue() as $value) {
                foreach ($nodeClasses as $class) {
                    if (
                        !empty($class) &&
                        \is_string($rule->getOperator())
                    ) {
                        $check = $this->match($rule->getOperator(), $value, $class);
                    }
                    if ($check) {
                        break;
                    }
                }
                if (!$check) {
                    break;
                }
            }
        } elseif (
            !$check &&
            \is_array($key) &&
            !\is_null($nodeValue) &&
            \is_string($rule->getOperator()) &&
            \is_string($rule->getValue()[$index])
        ) {
            $check = $this->match($rule->getOperator(), $rule->getValue()[$index], $nodeValue);
        }

        return $check;
    }

    /**
     * Attempts to match the given arguments with the given operator.
     */
    private function match(
        string $operator,
        string $pattern,
        string $value
    ): bool {
        $value = \strtolower($value);
        $pattern = \strtolower($pattern);
        switch ($operator) {
            case '=':
                return $value === $pattern;
            case '!=':
                return $value !== $pattern;
            case '^=':
                return \preg_match('/^' . \preg_quote($pattern, '/') . '/',
                        $value) == 1;
            case '$=':
                return \preg_match('/' . \preg_quote($pattern, '/') . '$/',
                        $value) == 1;
            case '*=':
                if ($pattern[0] == '/') {
                    return \preg_match($pattern, $value) == 1;
                }

                return \preg_match('/' . $pattern . '/i', $value) == 1;
            default:
                return false;
        }
    }
}
