<?php declare(strict_types=1);

namespace OpenApi\Tests\Annotations;

use OpenApi\Annotations\Attachable;
use OpenApi\Generator;

/**
 * @Annotation
 */
class CustomAttachable extends Attachable
{
    /**
     * The attribute value.
     *
     * @var mixed
     */
    public $value = Generator::UNDEFINED;

    /**
     * @inheritdoc
     */
    public static $_required = ['value'];
}
