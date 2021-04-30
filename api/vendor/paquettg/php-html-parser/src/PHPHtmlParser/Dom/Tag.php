<?php

declare(strict_types=1);

namespace PHPHtmlParser\Dom;

use PHPHtmlParser\DTO\Tag\AttributeDTO;
use PHPHtmlParser\Exceptions\Tag\AttributeNotFoundException;
use stringEncode\Encode;

/**
 * Class Tag.
 */
class Tag
{
    /**
     * The name of the tag.
     *
     * @var string
     */
    protected $name;

    /**
     * The attributes of the tag.
     *
     * @var AttributeDTO[]
     */
    protected $attr = [];

    /**
     * Is this tag self closing.
     *
     * @var bool
     */
    protected $selfClosing = false;

    /**
     * If self-closing, will this use a trailing slash. />.
     *
     * @var bool
     */
    protected $trailingSlash = true;

    /**
     * Tag noise.
     */
    protected $noise = '';

    /**
     * The encoding class to... encode the tags.
     *
     * @var Encode|null
     */
    protected $encode;

    /**
     * @var bool
     */
    private $HtmlSpecialCharsDecode = false;

    /**
     * What the opening of this tag will be.
     *
     * @var string
     */
    private $opening = '<';

    /**
     * What the closing tag for self-closing elements should be.
     *
     * @var string
     */
    private $closing = ' />';

    /**
     * Sets up the tag with a name.
     *
     * @param $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of this tag.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Sets the tag to be self closing.
     */
    public function selfClosing(): Tag
    {
        $this->selfClosing = true;

        return clone $this;
    }

    public function setOpening(string $opening): Tag
    {
        $this->opening = $opening;

        return clone $this;
    }

    public function setClosing(string $closing): Tag
    {
        $this->closing = $closing;

        return clone $this;
    }

    /**
     * Sets the tag to not use a trailing slash.
     */
    public function noTrailingSlash(): Tag
    {
        $this->trailingSlash = false;

        return clone $this;
    }

    /**
     * Checks if the tag is self closing.
     */
    public function isSelfClosing(): bool
    {
        return $this->selfClosing;
    }

    /**
     * Sets the encoding type to be used.
     */
    public function setEncoding(Encode $encode): void
    {
        $this->encode = $encode;
    }

    /**
     * @param bool $htmlSpecialCharsDecode
     */
    public function setHtmlSpecialCharsDecode($htmlSpecialCharsDecode = false): void
    {
        $this->HtmlSpecialCharsDecode = $htmlSpecialCharsDecode;
    }

    /**
     * Sets the noise for this tag (if any).
     */
    public function noise(string $noise): Tag
    {
        $this->noise = $noise;

        return clone $this;
    }

    /**
     * Set an attribute for this tag.
     */
    public function setAttribute(string $key, ?string $attributeValue, bool $doubleQuote = true): Tag
    {
        $attributeDTO = AttributeDTO::makeFromPrimitives(
            $attributeValue,
            $doubleQuote
        );
        if ($this->HtmlSpecialCharsDecode) {
            $attributeDTO->htmlspecialcharsDecode();
        }
        $this->attr[\strtolower($key)] = $attributeDTO;

        return clone $this;
    }

    /**
     * Set inline style attribute value.
     *
     * @param mixed $attr_key
     * @param mixed $attr_value
     */
    public function setStyleAttributeValue($attr_key, $attr_value): void
    {
        $style_array = $this->getStyleAttributeArray();
        $style_array[$attr_key] = $attr_value;

        $style_string = '';
        foreach ($style_array as $key => $value) {
            $style_string .= $key . ':' . $value . ';';
        }

        $this->setAttribute('style', $style_string);
    }

    /**
     * Get style attribute in array.
     */
    public function getStyleAttributeArray(): array
    {
        try {
            $value = $this->getAttribute('style')->getValue();
            if (\is_null($value)) {
                return [];
            }
            $value = \explode(';', \substr(\trim($value), 0, -1));
            $result = [];
            foreach ($value as $attr) {
                $attr = \explode(':', $attr);
                $result[$attr[0]] = $attr[1];
            }

            return $result;
        } catch (AttributeNotFoundException $e) {
            unset($e);

            return [];
        }
    }

    /**
     * Removes an attribute from this tag.
     *
     * @param mixed $key
     *
     * @return void
     */
    public function removeAttribute($key)
    {
        $key = \strtolower($key);
        unset($this->attr[$key]);
    }

    /**
     * Removes all attributes on this tag.
     *
     * @return void
     */
    public function removeAllAttributes()
    {
        $this->attr = [];
    }

    /**
     * Sets the attributes for this tag.
     *
     * @return $this
     */
    public function setAttributes(array $attr)
    {
        foreach ($attr as $key => $info) {
            if (\is_array($info)) {
                $this->setAttribute($key, $info['value'], $info['doubleQuote']);
            } else {
                $this->setAttribute($key, $info);
            }
        }

        return $this;
    }

    /**
     * Returns all attributes of this tag.
     *
     * @throws \stringEncode\Exception
     *
     * @return AttributeDTO[]
     */
    public function getAttributes(): array
    {
        $return = [];
        foreach (\array_keys($this->attr) as $attr) {
            try {
                $return[$attr] = $this->getAttribute($attr);
            } catch (AttributeNotFoundException $e) {
                // attribute that was in the array was not found in the array....
                unset($e);
            }
        }

        return $return;
    }

    /**
     * Returns an attribute by the key.
     *
     * @throws AttributeNotFoundException
     * @throws \stringEncode\Exception
     */
    public function getAttribute(string $key): AttributeDTO
    {
        $key = \strtolower($key);
        if (!isset($this->attr[$key])) {
            throw new AttributeNotFoundException('Attribute with key "' . $key . '" not found.');
        }
        $attributeDTO = $this->attr[$key];
        if (!\is_null($this->encode)) {
            // convert charset
            $attributeDTO->encodeValue($this->encode);
        }

        return $attributeDTO;
    }

    /**
     * Returns TRUE if node has attribute.
     *
     * @return bool
     */
    public function hasAttribute(string $key)
    {
        return isset($this->attr[$key]);
    }

    /**
     * Generates the opening tag for this object.
     *
     * @return string
     */
    public function makeOpeningTag()
    {
        $return = $this->opening . $this->name;

        // add the attributes
        foreach (\array_keys($this->attr) as $key) {
            try {
                $attributeDTO = $this->getAttribute($key);
            } catch (AttributeNotFoundException $e) {
                // attribute that was in the array not found in the array... let's continue.
                continue;
            } catch (\TypeError $e) {
              $val = null;
            }
            $val = $attributeDTO->getValue();
            if (\is_null($val)) {
                $return .= ' ' . $key;
            } elseif ($attributeDTO->isDoubleQuote()) {
                $return .= ' ' . $key . '="' . $val . '"';
            } else {
                $return .= ' ' . $key . '=\'' . $val . '\'';
            }
        }

        if ($this->selfClosing && $this->trailingSlash) {
            return $return . $this->closing;
        }

        return $return . '>';
    }

    /**
     * Generates the closing tag for this object.
     *
     * @return string
     */
    public function makeClosingTag()
    {
        if ($this->selfClosing) {
            return '';
        }

        return '</' . $this->name . '>';
    }
}
