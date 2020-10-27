<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi;

use Closure;
use Exception;
use OpenApi\Processors\ImportInterfaces;
use SplObjectStorage;
use stdClass;
use OpenApi\Annotations\AbstractAnnotation;
use OpenApi\Annotations\OpenApi;
use OpenApi\Processors\AugmentOperations;
use OpenApi\Processors\AugmentParameters;
use OpenApi\Processors\AugmentProperties;
use OpenApi\Processors\AugmentSchemas;
use OpenApi\Processors\BuildPaths;
use OpenApi\Processors\CleanUnmerged;
use OpenApi\Processors\InheritProperties;
use OpenApi\Processors\MergeIntoComponents;
use OpenApi\Processors\MergeIntoOpenApi;
use OpenApi\Processors\MergeJsonContent;
use OpenApi\Processors\MergeXmlContent;
use OpenApi\Processors\OperationId;
use OpenApi\Processors\ImportTraits;

/**
 * Result of the analyser which pretends to be an array of annotations, but also contains detected classes and helper
 * functions for the processors.
 */
class Analysis
{
    /**
     * @var SplObjectStorage
     */
    public $annotations;

    /**
     * Class definitions
     *
     * @var array
     */
    public $classes = [];

    /**
     * Trait definitions
     *
     * @var array
     */
    public $traits = [];

    /**
     * Interface definitions
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
     * Registry for the post-processing operations.
     *
     * @var Closure[]
     */
    private static $processors;

    /**
     * @param array $annotations
     * @param null  $context
     */
    public function __construct($annotations = [], $context = null)
    {
        $this->annotations = new SplObjectStorage();
        if (count($annotations) !== 0) {
            if ($context === null) {
                $context = Context::detect(1);
            }
            $this->addAnnotations($annotations, $context);
        }
    }

    /**
     * @param AbstractAnnotation $annotation
     * @param Context            $context
     */
    public function addAnnotation($annotation, $context)
    {
        if ($this->annotations->contains($annotation)) {
            return;
        }
        if ($annotation instanceof AbstractAnnotation) {
            $context = $annotation->_context;
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

    /**
     * @param array   $annotations
     * @param Context $context
     */
    public function addAnnotations($annotations, $context)
    {
        foreach ($annotations as $annotation) {
            $this->addAnnotation($annotation, $context);
        }
    }

    /**
     * @param array $definition
     */
    public function addClassDefinition($definition)
    {
        $class = $definition['context']->fullyQualifiedName($definition['class']);
        $this->classes[$class] = $definition;
    }

    /**
     * @param array $definition
     */
    public function addInterfaceDefinition($definition)
    {
        $interface = $definition['context']->fullyQualifiedName($definition['interface']);
        $this->interfaces[$interface] = $definition;
    }

    /**
     * @param array $definition
     */
    public function addTraitDefinition($definition)
    {
        $trait = $definition['context']->fullyQualifiedName($definition['trait']);
        $this->traits[$trait] = $definition;
    }

    /**
     * @param Analysis $analysis
     */
    public function addAnalysis($analysis)
    {
        foreach ($analysis->annotations as $annotation) {
            $this->addAnnotation($annotation, $analysis->annotations[$annotation]);
        }
        $this->classes = array_merge($this->classes, $analysis->classes);
        $this->interfaces = array_merge($this->interfaces, $analysis->interfaces);
        $this->traits = array_merge($this->traits, $analysis->traits);
        if ($this->openapi === null && $analysis->openapi) {
            $this->openapi = $analysis->openapi;
            $analysis->target->_context->analysis = $this;
        }
    }

    public function getSubClasses($class)
    {
        $definitions = [];
        foreach ($this->classes as $subclass => $definition) {
            if ($definition['extends'] === $class) {
                $definitions[$subclass] = $definition;
                $definitions = array_merge($definitions, $this->getSubClasses($subclass));
            }
        }

        return $definitions;
    }

    public function getSuperClasses($class)
    {
        $classDefinition = isset($this->classes[$class]) ? $this->classes[$class] : null;
        if (!$classDefinition || empty($classDefinition['extends'])) { // unknown class, or no inheritance?
            return [];
        }
        $extends = $classDefinition['extends'];
        $extendsDefinition = isset($this->classes[$extends]) ? $this->classes[$extends] : null;
        if (!$extendsDefinition) {
            return [];
        }
        $definitions = array_merge([$extends => $extendsDefinition], $this->getSuperClasses($extends));
        return $definitions;
    }

    /**
     * Returns an array of interfaces used by the given class or by classes which it extends
     *
     * @param string  $class
     *
     * @return array
     */
    public function getInterfacesOfClass($class)
    {
        $definitions = [];

        // in case there is a hierarchy of classes
        $classes = $this->getSuperClasses($class);
        if (is_array($classes)) {
            foreach ($classes as $subClass) {
                if (isset($subClass['interfaces'])) {
                    foreach ($subClass['interfaces'] as $classInterface) {
                        foreach ($this->interfaces as $interface) {
                            if ($classInterface === $interface['interface']) {
                                $interfaceDefinition[$interface['interface']] = $interface;
                                $definitions = array_merge($definitions, $interfaceDefinition);
                            }
                        }
                    }
                }
            }
        }

        // interface used by the given class
        $classDefinition = isset($this->classes[$class]) ? $this->classes[$class] : null;
        if (!$classDefinition || empty($classDefinition['interfaces'])) {
            return $definitions;
        }
        $classInterfaces = $classDefinition['interfaces'];
        foreach ($this->interfaces as $interface) {
            foreach ($classInterfaces as $classInterface => $name) {
                if ($interface['interface'] === $name) {
                    $interfaceDefinition[$name] = $interface;
                    $definitions = array_merge($definitions, $interfaceDefinition);
                }
            }
        }

        return $definitions;
    }

    /**
     * Returns an array of traits used by the given class or by classes which it extends
     *
     * @param string  $class
     *
     * @return array
     */
    public function getTraitsOfClass($class)
    {
        $definitions = [];

        // in case there is a hierarchy of classes
        $classes = $this->getSuperClasses($class);
        if (is_array($classes)) {
            foreach ($classes as $subClass) {
                if (isset($subClass['traits'])) {
                    foreach ($subClass['traits'] as $classTrait) {
                        foreach ($this->traits as $trait) {
                            if ($classTrait === $trait['trait']) {
                                $traitDefinition[$trait['trait']] = $trait;
                                $definitions = array_merge($definitions, $traitDefinition);
                            }
                        }
                    }
                }
            }
        }

        // trait used by the given class
        $classDefinition = isset($this->classes[$class]) ? $this->classes[$class] : null;
        if (!$classDefinition || empty($classDefinition['traits'])) {
            return $definitions;
        }
        $classTraits = $classDefinition['traits'];
        foreach ($this->traits as $trait) {
            foreach ($classTraits as $classTrait => $name) {
                if ($trait['trait'] === $name) {
                    $traitDefinition[$name] = $trait;
                    $definitions = array_merge($definitions, $traitDefinition);
                }
            }
        }

        return $definitions;
    }

    /**
     *
     * @param string  $class
     * @param boolean $strict Innon-strict mode childclasses are also detected.
     *
     * @return array
     */
    public function getAnnotationsOfType($class, $strict = false)
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
     *
     * @param object $annotation
     *
     * @return \OpenApi\Context
     */
    public function getContext($annotation)
    {
        if ($annotation instanceof AbstractAnnotation) {
            return $annotation->_context;
        }
        if ($this->annotations->contains($annotation) === false) {
            throw new Exception('Annotation not found');
        }
        $context = $this->annotations[$annotation];
        if ($context instanceof Context) {
            return $context;
        }
        var_dump($context);
        ob_flush();
        die;
        throw new Exception('Annotation has no context'); // Weird, did you use the addAnnotation/addAnnotations methods?
    }

    /**
     * Build an analysis with only the annotations that are merged into the OpenAPI annotation.
     *
     * @return Analysis
     */
    public function merged()
    {
        if (!$this->openapi) {
            throw new Exception('No openapi target set. Run the MergeIntoOpenApi processor');
        }
        $unmerged = $this->openapi->_unmerged;
        $this->openapi->_unmerged = [];
        $analysis = new Analysis([$this->openapi]);
        $this->openapi->_unmerged = $unmerged;

        return $analysis;
    }

    /**
     * Analysis with only the annotations that not merged.
     *
     * @return Analysis
     */
    public function unmerged()
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
        $result = new stdClass();
        $result->merged = $this->merged();
        $result->unmerged = new Analysis();
        foreach ($this->annotations as $annotation) {
            if ($result->merged->annotations->contains($annotation) === false) {
                $result->unmerged->annotations->attach($annotation, $this->annotations[$annotation]);
            }
        }

        return $result;
    }

    /**
     * Apply the processor(s)
     *
     * @param Closure|Closure[] $processors One or more processors
     */
    public function process($processors = null)
    {
        if ($processors === null) { // Use the default and registered processors.
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
     */
    public static function &processors()
    {
        if (!self::$processors) {
            // Add default processors.
            self::$processors = [
                new MergeIntoOpenApi(),
                new MergeIntoComponents(),
                new ImportInterfaces(),
                new ImportTraits(),
                new AugmentSchemas(),
                new AugmentProperties(),
                new BuildPaths(),
                // new HandleReferences(),

                new InheritProperties(),
                new AugmentOperations(),
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
     * Register a processor
     *
     * @param Closure $processor
     */
    public static function registerProcessor($processor)
    {
        array_push(self::processors(), $processor);
    }

    /**
     * Unregister a processor
     *
     * @param Closure $processor
     */
    public static function unregisterProcessor($processor)
    {
        $processors = &self::processors();
        $key = array_search($processor, $processors, true);
        if ($key === false) {
            throw new Exception('Given processor was not registered');
        }
        unset($processors[$key]);
    }

    public function validate()
    {
        if ($this->openapi) {
            return $this->openapi->validate();
        }
        Logger::notice('No openapi target set. Run the MergeIntoOpenApi processor before validate()');

        return false;
    }
}
