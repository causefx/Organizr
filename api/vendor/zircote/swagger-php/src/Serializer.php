<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi;

use OpenApi\Annotations\AbstractAnnotation;

/**
 * Class AnnotationDeserializer is used to deserialize a json string
 * to a specific Annotation class and vice versa.
 *
 * @link https://github.com/zircote/swagger-php
 */
class Serializer
{
    const CONTACT = 'OpenApi\Annotations\Contact';
    const DELETE = 'OpenApi\Annotations\Delete';
    const EXTERNALDOCUMENTATION = 'OpenApi\Annotations\ExternalDocumentation';
    const FLOW = 'OpenApi\Annotations\Flow';
    const GET = 'OpenApi\Annotations\Get';
    const HEAD = 'OpenApi\Annotations\Head';
    const HEADER = 'OpenApi\Annotations\Header';
    const INFO = 'OpenApi\Annotations\Info';
    const ITEMS = 'OpenApi\Annotations\Items';
    const LICENSE = 'OpenApi\Annotations\License';
    const OPENAPI = 'OpenApi\Annotations\OpenApi';
    const OPERATION = 'OpenApi\Annotations\Operation';
    const OPTIONS = 'OpenApi\Annotations\Options';
    const PARAMETER = 'OpenApi\Annotations\Parameter';
    const PATCH = 'OpenApi\Annotations\Patch';
    const PATHITEM = 'OpenApi\Annotations\PathItem';
    const POST = 'OpenApi\Annotations\Post';
    const PROPERTY = 'OpenApi\Annotations\Property';
    const PUT = 'OpenApi\Annotations\Put';
    const REQUESTBODY = 'OpenApi\Annotations\RequestBody';
    const RESPONSE = 'OpenApi\Annotations\Response';
    const SCHEMA = 'OpenApi\Annotations\Schema';
    const SECURITYSCHEME = 'OpenApi\Annotations\SecurityScheme';
    const TAG = 'OpenApi\Annotations\Tag';
    const XML = 'OpenApi\Annotations\Xml';

    private static $cachedNames;

    private static function getDefinedNames()
    {
        if (static::$cachedNames === null) {
            static::$cachedNames = [];
            $reflection = new \ReflectionClass(__CLASS__);
            static::$cachedNames = $reflection->getConstants();
        }
        return static::$cachedNames;
    }

    public static function isValidClassName($className)
    {
        return in_array($className, static::getDefinedNames());
    }

    /**
     * Serialize.
     *
     * @param  Annotations\AbstractAnnotation $annotation
     * @return string
     */
    public function serialize(Annotations\AbstractAnnotation $annotation)
    {
        return json_encode($annotation);
    }

    /**
     * Deserialize a string
     *
     * @param $jsonString
     * @param $className
     *
     * @return Annotations\AbstractAnnotation
     *
     * @throws \Exception
     */
    public function deserialize($jsonString, $className)
    {
        if (!$this->isValidClassName($className)) {
            throw new \Exception($className.' is not defined in OpenApi PHP Annotations');
        }
        return $this->doDeserialize(json_decode($jsonString), $className);
    }

    /**
     * Deserialize a file
     *
     * @param $filename
     * @param $className
     *
     * @return Annotations\AbstractAnnotation
     *
     * @throws \Exception
     */
    public function deserializeFile($filename, $className = 'OpenApi\Annotations\OpenApi')
    {
        if (!$this->isValidClassName($className)) {
            throw new \Exception($className.' is not defined in OpenApi PHP Annotations');
        }
        $jsonString = file_get_contents($filename);
        return $this->doDeserialize(json_decode($jsonString), $className);
    }

    /**
     * Do deserialization.
     *
     * @param \stdClass $c
     * @param string    $class The class name of annotation.
     *
     * @return Annotations\AbstractAnnotation
     */
    private function doDeserialize(\stdClass $c, $class)
    {
        $annotation = new $class([]);
        foreach ($c as $property => $value) {
            if ($property === '$ref') {
                $property = 'ref';
            }

            if (substr($property, 0, 2) === 'x-') {
                if ($annotation->x === UNDEFINED) {
                    $annotation->x = [];
                }
                $custom = substr($property, 2);
                $annotation->x[$custom] = $value;
            } else {
                $annotation->$property = $this->doDeserializeProperty($annotation, $property, $value);
            }
        }
        return $annotation;
    }

    /**
     * Deserialize the annotation's property.
     *
     * @param Annotations\AbstractAnnotation $annotation
     * @param string                         $property
     * @param mixed                          $value
     *
     * @return mixed
     */
    private function doDeserializeProperty(Annotations\AbstractAnnotation $annotation, $property, $value)
    {
        // property is primitive type
        if (array_key_exists($property, $annotation::$_types)) {
            return $this->doDeserializeBaseProperty($annotation::$_types[$property], $value);
        }
        // property is embedded annotation
        foreach ($annotation::$_nested as $class => $declaration) {
            // property is an annotation
            if (is_string($declaration) && $declaration === $property) {
                if (is_object($value)) {
                    return $this->doDeserialize($value, $class);
                } else {
                    return $value;
                }
            }

            // property is an annotation array
            if (is_array($declaration) && count($declaration) === 1 && $declaration[0] === $property) {
                $annotationArr = [];
                foreach ($value as $v) {
                    $annotationArr[] = $this->doDeserialize($v, $class);
                }
                return $annotationArr;
            }

            // property is an annotation hash map
            if (is_array($declaration) && count($declaration) === 2 && $declaration[0] === $property) {
                $key = $declaration[1];
                $annotationHash = [];
                foreach ($value as $k => $v) {
                    $annotation = $this->doDeserialize($v, $class);
                    $annotation->$key = $k;
                    $annotationHash[$k] = $annotation;
                }
                return $annotationHash;
            }
        }

        return $value;
    }

    /**
     * Deserialize base annotation property
     *
     * @param string $type The property type
     * @param mixed $value The value to deserialization
     *
     * @return array|\OpenApi\Annotations\AbstractAnnotation
     */
    private function doDeserializeBaseProperty($type, $value)
    {
        $isAnnotationClass = is_string($type) && is_subclass_of(trim($type, '[]'), AbstractAnnotation::class);

        if ($isAnnotationClass) {
            $isArray = strpos($type, '[') === 0 && substr($type, -1) === ']';

            if ($isArray) {
                $annotationArr = [];
                $class = trim($type, '[]');

                foreach ($value as $v) {
                    $annotationArr[] = $this->doDeserialize($v, $class);
                }
                return $annotationArr;
            }

            return $this->doDeserialize($value, $type);
        }

        return $value;
    }
}
