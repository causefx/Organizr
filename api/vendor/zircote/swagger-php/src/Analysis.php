<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi;

use OpenApi\Annotations\AbstractAnnotation;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Schema;
use OpenApi\Processors\AugmentParameters;
use OpenApi\Processors\AugmentProperties;
use OpenApi\Processors\AugmentSchemas;
use OpenApi\Processors\BuildPaths;
use OpenApi\Processors\CleanUnmerged;
use OpenApi\Processors\DocBlockDescriptions;
use OpenApi\Processors\ExpandInterfaces;
use OpenApi\Processors\ExpandClasses;
use OpenApi\Processors\ExpandTraits;
use OpenApi\Processors\MergeIntoComponents;
use OpenApi\Processors\MergeIntoOpenApi;
use OpenApi\Processors\MergeJsonContent;
use OpenApi\Processors\MergeXmlContent;
use OpenApi\Processors\OperationId;

/**
 * Result of the analyser.
 *
 * Pretends to be an array of annotations, but also contains detected classes
 * and helper functions for the processors.
 */
class Analysis
{
    /**
     * @var \SplObjectStorage
     */
    public $annotations;

    /**
     * Class definitions.
     *
     * @var array
     */
    public $classes = [];

    /**
     * Trait definitions.
     *
     * @var array
     */
    public $traits = [];

    /**
     * Interface definitions.
     *
     * @var array
     */
    public $interfaces = [];

    /**
     * The target OpenApi annotation.
     *
     * @var OpenApi
     */
    public $openapi;

    /**
     * @var Context
     */
    public $context;

    /**
     * Registry for the post-processing operations.
     *
     * @var callable[]
     */
    private static $processors;

    public function __construct(array $annotations = [], Context $context = null)
    {
        $this->annotations = new \SplObjectStorage();
        $this->context = $context;

        $this->addAnnotations($annotations, $context);
    }

    public function addAnnotation($annotation, ?Context $context): void
    {
        if ($this->annotations->contains($annotation)) {
            return;
        }
        if ($annotation instanceof AbstractAnnotation) {
            $context = $annotation->_context ?: $this->context;
            if ($this->openapi === null && $annotation instanceof OpenApi) {
                $this->openapi = $annotation;
            }
        } else {
            if ($context->is('annotations') === false) {
                $context->annotations = [];
            }
            if (in_array($annotation, $context->annotations, true) === false) {
                $context->annotations[] = $annotation;
            }
        }
        $this->annotations->attach($annotation, $context);
        $blacklist = property_exists($annotation, '_blacklist') ? $annotation::$_blacklist : [];
        foreach ($annotation as $property => $value) {
            if (in_array($property, $blacklist)) {
                if ($property === '_unmerged') {
                    foreach ($value as $item) {
                        $this->addAnnotation($item, $context);
                    }
                }
                continue;
            } elseif (is_array($value)) {
                foreach ($value as $item) {
                    if ($item instanceof AbstractAnnotation) {
                        $this->addAnnotation($item, $context);
                    }
                }
            } elseif ($value instanceof AbstractAnnotation) {
                $this->addAnnotation($value, $context);
            }
        }
    }

    public function addAnnotations(array $annotations, ?Context $context): void
    {
        foreach ($annotations as $annotation) {
            $this->addAnnotation($annotation, $context);
        }
    }

    public function addClassDefinition(array $definition): void
    {
        $class = $definition['context']->fullyQualifiedName($definition['class']);
        $this->classes[$class] = $definition;
    }

    public function addInterfaceDefinition(array $definition): void
    {
        $interface = $definition['context']->fullyQualifiedName($definition['interface']);
        $this->interfaces[$interface] = $definition;
    }

    public function addTraitDefinition(array $definition): void
    {
        $trait = $definition['context']->fullyQualifiedName($definition['trait']);
        $this->traits[$trait] = $definition;
    }

    public function addAnalysis(Analysis $analysis): void
    {
        foreach ($analysis->annotations as $annotation) {
            $this->addAnnotation($annotation, $analysis->annotations[$annotation]);
        }
        $this->classes = array_merge($this->classes, $analysis->classes);
        $this->interfaces = array_merge($this->interfaces, $analysis->interfaces);
        $this->traits = array_merge($this->traits, $analysis->traits);
        if ($this->openapi === null && $analysis->openapi !== null) {
            $this->openapi = $analysis->openapi;
        }
    }

    /**
     * Get all sub classes of the given parent class.
     *
     * @param string $parent the parent class
     *
     * @return array map of class => definition pairs of sub-classes
     */
    public function getSubClasses(string $parent): array
    {
        $definitions = [];
        foreach ($this->classes as $class => $classDefinition) {
            if ($classDefinition['extends'] === $parent) {
                $definitions[$class] = $classDefinition;
                $definitions = array_merge($definitions, $this->getSubClasses($class));
            }
        }

        return $definitions;
    }

    /**
     * Get a list of all super classes for the given class.
     *
     * @param string $class  the class name
     * @param bool   $direct flag to find only the actual class parents
     *
     * @return array map of class => definition pairs of parent classes
     */
    public function getSuperClasses(string $class, bool $direct = false): array
    {
        $classDefinition = isset($this->classes[$class]) ? $this->classes[$class] : null;
        if (!$classDefinition || empty($classDefinition['extends'])) {
            // unknown class, or no inheritance
            return [];
        }

        $extends = $classDefinition['extends'];
        $extendsDefinition = isset($this->classes[$extends]) ? $this->classes[$extends] : null;
        if (!$extendsDefinition) {
            return [];
        }

        $parentDetails = [$extends => $extendsDefinition];

        if ($direct) {
            return $parentDetails;
        }

        return array_merge($parentDetails, $this->getSuperClasses($extends));
    }

    /**
     * Get the list of interfaces used by the given class or by classes which it extends.
     *
     * @param string $class  the class name
     * @param bool   $direct flag to find only the actual class interfaces
     *
     * @return array map of class => definition pairs of interfaces
     */
    public function getInterfacesOfClass(string $class, bool $direct = false): array
    {
        $classes = $direct ? [] : array_keys($this->getSuperClasses($class));
        // add self
        $classes[] = $class;

        $definitions = [];
        foreach ($classes as $clazz) {
            if (isset($this->classes[$clazz])) {
                $definition = $this->classes[$clazz];
                if (isset($definition['implements'])) {
                    foreach ($definition['implements'] as $interface) {
                        if (array_key_exists($interface, $this->interfaces)) {
                            $definitions[$interface] = $this->interfaces[$interface];
                        }
                    }
                }
            }
        }

        if (!$direct) {
            // expand recursively for interfaces extending other interfaces
            $collect = function ($interfaces, $cb) use (&$definitions) {
                foreach ($interfaces as $interface) {
                    if (isset($this->interfaces[$interface]['extends'])) {
                        $cb($this->interfaces[$interface]['extends'], $cb);
                        foreach ($this->interfaces[$interface]['extends'] as $fqdn) {
                            $definitions[$fqdn] = $this->interfaces[$fqdn];
                        }
                    }
                }
            };
            $collect(array_keys($definitions), $collect);
        }

        return $definitions;
    }

    /**
     * Get the list of traits used by the given class/trait or by classes which it extends.
     *
     * @param string $source the source name
     * @param bool   $direct flag to find only the actual class traits
     *
     * @return array map of class => definition pairs of traits
     */
    public function getTraitsOfClass(string $source, bool $direct = false): array
    {
        $sources = $direct ? [] : array_keys($this->getSuperClasses($source));
        // add self
        $sources[] = $source;

        $definitions = [];
        foreach ($sources as $sourze) {
            if (isset($this->classes[$sourze]) || isset($this->traits[$sourze])) {
                $definition = isset($this->classes[$sourze]) ? $this->classes[$sourze] : $this->traits[$sourze];
                if (isset($definition['traits'])) {
                    foreach ($definition['traits'] as $trait) {
                        if (array_key_exists($trait, $this->traits)) {
                            $definitions[$trait] = $this->traits[$trait];
                        }
                    }
                }
            }
        }

        if (!$direct) {
            // expand recursively for traits using other tratis
            $collect = function ($traits, $cb) use (&$definitions) {
                foreach ($traits as $trait) {
                    if (isset($this->traits[$trait]['traits'])) {
                        $cb($this->traits[$trait]['traits'], $cb);
                        foreach ($this->traits[$trait]['traits'] as $fqdn) {
                            $definitions[$fqdn] = $this->traits[$fqdn];
                        }
                    }
                }
            };
            $collect(array_keys($definitions), $collect);
        }

        return $definitions;
    }

    /**
     * @param bool $strict in non-strict mode child classes are also detected
     *
     * @return AbstractAnnotation[]
     */
    public function getAnnotationsOfType(string $class, bool $strict = false): array
    {
        $annotations = [];
        if ($strict) {
            foreach ($this->annotations as $annotation) {
                if (get_class($annotation) === $class) {
                    $annotations[] = $annotation;
                }
            }
        } else {
            foreach ($this->annotations as $annotation) {
                if ($annotation instanceof $class) {
                    $annotations[] = $annotation;
                }
            }
        }

        return $annotations;
    }

    /**
     * @param string $fqdn the source class/interface/trait
     */
    public function getSchemaForSource(string $fqdn): ?Schema
    {
        $sourceDefinitions = [
            $this->classes,
            $this->interfaces,
            $this->traits,
        ];

        foreach ($sourceDefinitions as $definitions) {
            if (array_key_exists($fqdn, $definitions)) {
                $definition = $definitions[$fqdn];
                if (is_iterable($definition['context']->annotations)) {
                    foreach ($definition['context']->annotations as $annotation) {
                        if (get_class($annotation) === Schema::class) {
                            return $annotation;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param object $annotation
     *
     * @return \OpenApi\Context
     */
    public function getContext($annotation): Context
    {
        if ($annotation instanceof AbstractAnnotation) {
            return $annotation->_context;
        }
        if ($this->annotations->contains($annotation) === false) {
            throw new \Exception('Annotation not found');
        }
        $context = $this->annotations[$annotation];
        if ($context instanceof Context) {
            return $context;
        }
        // Weird, did you use the addAnnotation/addAnnotations methods?
        throw new \Exception('Annotation has no context');
    }

    /**
     * Build an analysis with only the annotations that are merged into the OpenAPI annotation.
     */
    public function merged(): Analysis
    {
        if ($this->openapi === null) {
            throw new \Exception('No openapi target set. Run the MergeIntoOpenApi processor');
        }
        $unmerged = $this->openapi->_unmerged;
        $this->openapi->_unmerged = [];
        $analysis = new Analysis([$this->openapi], $this->context);
        $this->openapi->_unmerged = $unmerged;

        return $analysis;
    }

    /**
     * Analysis with only the annotations that not merged.
     */
    public function unmerged(): Analysis
    {
        return $this->split()->unmerged;
    }

    /**
     * Split the annotation into two analysis.
     * One with annotations that are merged and one with annotations that are not merged.
     *
     * @return object {merged: Analysis, unmerged: Analysis}
     */
    public function split()
    {
        $result = new \stdClass();
        $result->merged = $this->merged();
        $result->unmerged = new Analysis([], $this->context);
        foreach ($this->annotations as $annotation) {
            if ($result->merged->annotations->contains($annotation) === false) {
                $result->unmerged->annotations->attach($annotation, $this->annotations[$annotation]);
            }
        }

        return $result;
    }

    /**
     * Apply the processor(s).
     *
     * @param \Closure|\Closure[] $processors One or more processors
     */
    public function process($processors = null): void
    {
        if ($processors === null) {
            // Use the default and registered processors.
            $processors = self::processors();
        }
        if (is_array($processors) === false && is_callable($processors)) {
            $processors = [$processors];
        }
        foreach ($processors as $processor) {
            $processor($this);
        }
    }

    /**
     * Get direct access to the processors array.
     *
     * @return array reference
     *
     * @deprecated Superseded by `Generator` methods
     */
    public static function &processors()
    {
        if (!self::$processors) {
            // Add default processors.
            self::$processors = [
                new DocBlockDescriptions(),
                new MergeIntoOpenApi(),
                new MergeIntoComponents(),
                new ExpandClasses(),
                new ExpandInterfaces(),
                new ExpandTraits(),
                new AugmentSchemas(),
                new AugmentProperties(),
                new BuildPaths(),
                new AugmentParameters(),
                new MergeJsonContent(),
                new MergeXmlContent(),
                new OperationId(),
                new CleanUnmerged(),
            ];
        }

        return self::$processors;
    }

    /**
     * Register a processor.
     *
     * @param \Closure $processor
     *
     * @deprecated Superseded by `Generator` methods
     */
    public static function registerProcessor($processor): void
    {
        array_push(self::processors(), $processor);
    }

    /**
     * Unregister a processor.
     *
     * @param \Closure $processor
     *
     * @deprecated Superseded by `Generator` methods
     */
    public static function unregisterProcessor($processor): void
    {
        $processors = &self::processors();
        $key = array_search($processor, $processors, true);
        if ($key === false) {
            throw new \Exception('Given processor was not registered');
        }
        unset($processors[$key]);
    }

    public function validate(): bool
    {
        if ($this->openapi !== null) {
            return $this->openapi->validate();
        }
        $this->context->logger->warning('No openapi target set. Run the MergeIntoOpenApi processor before validate()');

        return false;
    }
}
