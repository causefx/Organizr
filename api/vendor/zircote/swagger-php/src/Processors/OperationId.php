<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Operation;
use OpenApi\Generator;

/**
 * Generate the OperationId based on the context of the OpenApi annotation.
 */
class OperationId
{
    protected $hash;

    /**
     * @param bool $hash if `true` hash generated ids instead of clear text
     */
    public function __construct(bool $hash = true)
    {
        $this->hash = $hash;
    }

    public function isHash(): bool
    {
        return $this->hash;
    }

    public function setHash(bool $hash): OperationId
    {
        $this->hash = $hash;

        return $this;
    }

    public function __invoke(Analysis $analysis)
    {
        $allOperations = $analysis->getAnnotationsOfType(Operation::class);

        /** @var Operation $operation */
        foreach ($allOperations as $operation) {
            if ($operation->operationId !== Generator::UNDEFINED) {
                continue;
            }
            $context = $operation->_context;
            if ($context && $context->method) {
                $source = $context->class ?? $context->interface ?? $context->trait;
                $operationId = null;
                if ($source) {
                    if ($context->namespace) {
                        $operationId = $context->namespace . '\\' . $source . '::' . $context->method;
                    } else {
                        $operationId = $source . '::' . $context->method;
                    }
                } else {
                    $operationId = $context->method;
                }
                $operationId = strtoupper($operation->method) . '::' . $operation->path . '::' . $operationId;
                $operation->operationId = $this->hash ? md5($operationId) : $operationId;
            }
        }
    }
}
