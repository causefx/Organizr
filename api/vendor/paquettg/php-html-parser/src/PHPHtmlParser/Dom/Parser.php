<?php

declare(strict_types=1);

namespace PHPHtmlParser\Dom;

use PHPHtmlParser\Content;
use PHPHtmlParser\Contracts\Dom\ParserInterface;
use PHPHtmlParser\Dom\Node\AbstractNode;
use PHPHtmlParser\Dom\Node\HtmlNode;
use PHPHtmlParser\Dom\Node\TextNode;
use PHPHtmlParser\DTO\TagDTO;
use PHPHtmlParser\Enum\StringToken;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\ContentLengthException;
use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Exceptions\StrictException;
use PHPHtmlParser\Options;
use stringEncode\Encode;

class Parser implements ParserInterface
{
    /**
     * Attempts to parse the html in content.
     *
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws ContentLengthException
     * @throws LogicalException
     * @throws StrictException
     */
    public function parse(Options $options, Content $content, int $size): AbstractNode
    {
        // add the root node
        $root = new HtmlNode('root');
        $root->setHtmlSpecialCharsDecode($options->isHtmlSpecialCharsDecode());
        $activeNode = $root;
        while ($activeNode !== null) {
            if ($activeNode && $activeNode->tag->name() === 'script'
                && $options->isCleanupInput() !== true
            ) {
                $str = $content->copyUntil('</');
            } else {
                $str = $content->copyUntil('<');
            }
            if ($str == '') {
                $tagDTO = $this->parseTag($options, $content, $size);
                if (!$tagDTO->isStatus()) {
                    // we are done here
                    $activeNode = null;
                    continue;
                }

                // check if it was a closing tag
                if ($tagDTO->isClosing()) {
                    $foundOpeningTag = true;
                    $originalNode = $activeNode;
                    while ($activeNode->getTag()->name() != $tagDTO->getTag()) {
                        $activeNode = $activeNode->getParent();
                        if ($activeNode === null) {
                            // we could not find opening tag
                            $activeNode = $originalNode;
                            $foundOpeningTag = false;
                            break;
                        }
                    }
                    if ($foundOpeningTag) {
                        $activeNode = $activeNode->getParent();
                    }
                    continue;
                }

                if ($tagDTO->getNode() === null) {
                    continue;
                }

                /** @var AbstractNode $node */
                $node = $tagDTO->getNode();
                $activeNode->addChild($node);

                // check if node is self closing
                if (!$node->getTag()->isSelfClosing()) {
                    $activeNode = $node;
                }
            } elseif ($options->isWhitespaceTextNode() ||
                \trim($str) != ''
            ) {
                // we found text we care about
                $textNode = new TextNode($str, $options->isRemoveDoubleSpace());
                $textNode->setHtmlSpecialCharsDecode($options->isHtmlSpecialCharsDecode());
                $activeNode->addChild($textNode);
            }
        }

        return $root;
    }

    /**
     * Attempts to detect the charset that the html was sent in.
     *
     * @throws ChildNotFoundException
     */
    public function detectCharset(Options $options, string $defaultCharset, AbstractNode $root): bool
    {
        // set the default
        $encode = new Encode();
        $encode->from($defaultCharset);
        $encode->to($defaultCharset);

        $enforceEncoding = $options->getEnforceEncoding();
        if ($enforceEncoding !== null) {
            //  they want to enforce the given encoding
            $encode->from($enforceEncoding);
            $encode->to($enforceEncoding);

            return false;
        }

        /** @var AbstractNode $meta */
        $meta = $root->find('meta[http-equiv=Content-Type]', 0);
        if ($meta == null) {
            if (!$this->detectHTML5Charset($encode, $root)) {
                // could not find meta tag
                $root->propagateEncoding($encode);

                return false;
            }

            return true;
        }
        $content = $meta->getAttribute('content');
        if (\is_null($content)) {
            // could not find content
            $root->propagateEncoding($encode);

            return false;
        }
        $matches = [];
        if (\preg_match('/charset=([^;]+)/', $content, $matches)) {
            $encode->from(\trim($matches[1]));
            $root->propagateEncoding($encode);

            return true;
        }

        // no charset found
        $root->propagateEncoding($encode);

        return false;
    }

    /**
     * Attempt to parse a tag out of the content.
     *
     * @throws StrictException
     * @throws ContentLengthException
     * @throws LogicalException
     * @throws StrictException
     */
    private function parseTag(Options $options, Content $content, int $size): TagDTO
    {
        if ($content->char() != '<') {
            // we are not at the beginning of a tag
            return TagDTO::makeFromPrimitives();
        }

        // check if this is a closing tag
        try {
            $content->fastForward(1);
        } catch (ContentLengthException $exception) {
            // we are at the end of the file
            return TagDTO::makeFromPrimitives();
        }
        if ($content->char() == '/') {
            return $this->makeEndTag($content, $options);
        }
        if ($content->char() == '?') {
            // special setting tag
            $tag = $content->fastForward(1)
                ->copyByToken(StringToken::SLASH(), true);
            $tag = (new Tag($tag))
                ->setOpening('<?')
                ->setClosing(' ?>')
                ->selfClosing();
        } elseif($content->string(3) == '!--') {
            // comment tag
            $tag = $content->fastForward(3)
                ->copyByToken(StringToken::CLOSECOMMENT(), true);
            $tag = (new Tag($tag))
                ->setOpening('<!--')
                ->setClosing('-->')
                ->selfClosing();
        } else {
            $tag = \strtolower($content->copyByToken(StringToken::SLASH(), true));
            if (\trim($tag) == '') {
                // no tag found, invalid < found
                return TagDTO::makeFromPrimitives();
            }
        }
        $node = new HtmlNode($tag);
        $node->setHtmlSpecialCharsDecode($options->isHtmlSpecialCharsDecode());
        $this->setUpAttributes($content, $size, $node, $options, $tag);

        $content->skipByToken(StringToken::BLANK());
        if ($content->char() == '/') {
            // self closing tag
            $node->getTag()->selfClosing();
            $content->fastForward(1);
        } elseif (\in_array($node->getTag()->name(), $options->getSelfClosing(), true)) {
            // Should be a self closing tag, check if we are strict
            if ($options->isStrict()) {
                $character = $content->getPosition();
                throw new StrictException("Tag '" . $node->getTag()->name() . "' is not self closing! (character #$character)");
            }

            // We force self closing on this tag.
            $node->getTag()->selfClosing();

            // Should this tag use a trailing slash?
            if (\in_array($node->getTag()->name(), $options->getNoSlash(), true)) {
                $node->getTag()->noTrailingSlash();
            }
        }

        if ($content->canFastForward(1)) {
            $content->fastForward(1);
        }

        return TagDTO::makeFromPrimitives(true, false, $node);
    }

    /**
     * @throws ChildNotFoundException
     */
    private function detectHTML5Charset(Encode $encode, AbstractNode $root): bool
    {
        /** @var AbstractNode|null $meta */
        $meta = $root->find('meta[charset]', 0);
        if ($meta == null) {
            return false;
        }

        $encode->from(\trim($meta->getAttribute('charset')));
        $root->propagateEncoding($encode);

        return true;
    }

    /**
     * @throws ContentLengthException
     * @throws LogicalException
     */
    private function makeEndTag(Content $content, Options $options): TagDTO
    {
        $tag = $content->fastForward(1)
            ->copyByToken(StringToken::SLASH(), true);
        // move to end of tag
        $content->copyUntil('>');
        $content->fastForward(1);

        // check if this closing tag counts
        $tag = \strtolower($tag);
        if (\in_array($tag, $options->getSelfClosing(), true)) {
            return TagDTO::makeFromPrimitives(true);
        }

        return TagDTO::makeFromPrimitives(true, true, null, \strtolower($tag));
    }

    /**
     * @param string|Tag $tag
     *
     * @throws ContentLengthException
     * @throws LogicalException
     * @throws StrictException
     */
    private function setUpAttributes(Content $content, int $size, HtmlNode $node, Options $options, $tag): void
    {
        while (
            $content->char() != '>' &&
            $content->char() != '/'
        ) {
            $space = $content->skipByToken(StringToken::BLANK(), true);
            if (empty($space)) {
                try {
                    $content->fastForward(1);
                } catch (ContentLengthException $exception) {
                    // reached the end of the content
                    break;
                }
                continue;
            }

            $name = $content->copyByToken(StringToken::EQUAL(), true);
            if ($name == '/') {
                break;
            }

            if (empty($name)) {
                $content->skipByToken(StringToken::BLANK());
                continue;
            }

            $content->skipByToken(StringToken::BLANK());
            if ($content->char() == '=') {
                $content->fastForward(1)
                    ->skipByToken(StringToken::BLANK());
                switch ($content->char()) {
                    case '"':
                        $content->fastForward(1);
                        $string = $content->copyUntil('"', true);
                        do {
                            $moreString = $content->copyUntilUnless('"', '=>');
                            $string .= $moreString;
                        } while (\strlen($moreString) > 0 && $content->getPosition() < $size);
                        $content->fastForward(1);
                        $node->getTag()->setAttribute($name, $string);
                        break;
                    case "'":
                        $content->fastForward(1);
                        $string = $content->copyUntil("'", true);
                        do {
                            $moreString = $content->copyUntilUnless("'", '=>');
                            $string .= $moreString;
                        } while (\strlen($moreString) > 0 && $content->getPosition() < $size);
                        $content->fastForward(1);
                        $node->getTag()->setAttribute($name, $string, false);
                        break;
                    default:
                        $node->getTag()->setAttribute($name, $content->copyByToken(StringToken::ATTR(), true));
                        break;
                }
            } else {
                // no value attribute
                if ($options->isStrict()) {
                    // can't have this in strict html
                    $character = $content->getPosition();
                    throw new StrictException("Tag '$tag' has an attribute '$name' with out a value! (character #$character)");
                }
                $node->getTag()->setAttribute($name, null);
                if ($content->char() != '>') {
                    $content->rewind(1);
                }
            }
        }
    }
}
