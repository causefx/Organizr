<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Annotations;

/**
 * @Annotation
 * The description of an item in a Schema with type "array"
 */
class Items extends Schema
{
    /**
     * @inheritdoc
     */
    public static $_nested = [
        Discriminator::class => 'discriminator',
        Items::class => 'items',
        Property::class => ['properties', 'property'],
        ExternalDocumentation::class => 'externalDocs',
        Xml::class => 'xml',
        AdditionalProperties::class => 'additionalProperties',
        Attachable::class => ['attachables'],
    ];

    /**
     * @inheritdoc
     */
    public static $_parents = [
        Property::class,
        AdditionalProperties::class,
        Schema::class,
        JsonContent::class,
        XmlContent::class,
        Items::class,
    ];

    /**
     * @inheritdoc
     */
    public function validate(array $parents = [], array $skip = [], string $ref = ''): bool
    {
        if (in_array($this, $skip, true)) {
            return true;
        }

        $valid = parent::validate($parents, $skip);

        $parent = end($parents);
        if ($parent instanceof Schema && $parent->type !== 'array') {
            $this->_context->logger->warning('@OA\\Items() parent type must be "array" in ' . $this->_context);
            $valid = false;
        }

        return $valid;
        // @todo Additional validation when used inside a Header or Parameter context.
    }
}
