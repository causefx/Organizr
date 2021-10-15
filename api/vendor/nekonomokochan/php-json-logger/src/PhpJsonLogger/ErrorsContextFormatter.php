<?php
namespace Nekonomokochan\PhpJsonLogger;

/**
 * Trait ErrorsContextFormatter
 *
 * @package Nekonomokochan\PhpJsonLogger
 */
trait ErrorsContextFormatter
{
    /**
     * @param \Throwable $e
     * @param array $context
     * @return array
     */
    protected function formatPhpJsonLoggerErrorsContext(\Throwable $e, array $context): array
    {
        $context['php_json_logger']['errors']['message'] = $e->getMessage();
        $context['php_json_logger']['errors']['code'] = $e->getCode();
        $context['php_json_logger']['errors']['file'] = $e->getFile();
        $context['php_json_logger']['errors']['line'] = $e->getLine();
        $context['php_json_logger']['errors']['trace'] = $this->formatStackTrace($e->getTrace());

        return $context;
    }

    /**
     * @param array $traces
     * @return array
     */
    protected function formatStackTrace(array $traces): array
    {
        $formattedTraces = [];
        $length = count($traces);

        for ($i = 0; $i < $length; $i++) {
            $format = sprintf(
                '#%s %s(%s): %s%s%s()',
                $i,
                $traces[$i]['file'] ?? '',
                $traces[$i]['line'] ?? '',
                $traces[$i]['class'] ?? '',
                $traces[$i]['type'] ?? '',
                $traces[$i]['function'] ?? ''
            );

            $formattedTraces[] = $format;
        }

        return $formattedTraces;
    }
}
