<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Annotations;

/**
 * @Annotation
 *
 * A container for custom data to be attached to an annotation.
 * These will be ignored by swagger-php but can be used for custom processing.
 */
class Attachable extends AbstractAnnotation
{
    /**
     * @inheritdoc
     */
    public static $_parents = [
        AdditionalProperties::class,
        Components::class,
        Contact::class,
        Delete::class,
        Discriminator::class,
        Examples::class,
        ExternalDocumentation::class,
        Flow::class,
        Get::class,
        Head::class,
        Header::class,
        Info::class,
        Items::class,
        JsonContent::class,
        License::class,
        Link::class,
        MediaType::class,
        OpenApi::class,
        Operation::class,
        Options::class,
        Parameter::class,
        Patch::class,
        PathItem::class,
        Post::class,
        Property::class,
        Put::class,
        RequestBody::class,
        Response::class,
        Schema::class,
        SecurityScheme::class,
        Server::class,
        ServerVariable::class,
        Tag::class,
        Trace::class,
        Xml::class,
        XmlContent::class,
    ];

    /**
     * Allows to type hint a specific parent annotation class.
     *
     * Allows to implement custom annotations that are limited to a subset of potential parent
     * annotation classes.
     * This is most likely going to be a v4 (PHP 8.1) PHP attribute feature only.
     *
     * @return array List of valid parent annotation classes. If `null`` the default nesting rules apply.
     */
    public function allowedParents(): ?array
    {
        return null;
    }
}
