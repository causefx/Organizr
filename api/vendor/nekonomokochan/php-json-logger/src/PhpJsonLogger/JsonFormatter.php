<?php
namespace Nekonomokochan\PhpJsonLogger;

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;

/**
 * Class JsonFormatter
 *
 * @package Nekonomokochan\PhpJsonLogger
 */
class JsonFormatter extends BaseJsonFormatter
{
    use ServerEnvExtractor;

    /**
     * @param array $record
     * @return array|mixed|string
     */
    public function format(array $record)
    {
        $formattedRecord = [
            'log_level'         => $record['level_name'],
            'message'           => $record['message'],
            'channel'           => $record['channel'],
            'trace_id'          => $record['extra']['trace_id'],
            'file'              => $record['extra']['file'],
            'line'              => $record['extra']['line'],
            'context'           => $record['context'],
            'remote_ip_address' => $this->extractRemoteIpAddress(),
            'server_ip_address' => $this->extractServerIpAddress(),
            'user_agent'        => $this->extractUserAgent(),
            'datetime'          => $record['datetime']->format('Y-m-d H:i:s.u'),
            'timezone'          => $record['datetime']->getTimezone()->getName(),
            'process_time'      => $this->calculateProcessTime($record['extra']['created_time']),
        ];

        unset($formattedRecord['context']['php_json_logger']);

        if (isset($record['context']['php_json_logger']['errors'])) {
            $formattedRecord['errors'] = $record['context']['php_json_logger']['errors'];
        }

        $json = $this->toJson($this->normalize($formattedRecord), true) . ($this->appendNewline ? "\n" : '');

        return $json;
    }

    /**
     * @param float $createdTime
     * @return float
     */
    private function calculateProcessTime(float $createdTime): float
    {
        $time = microtime(true);

        return ($time - $createdTime) * 1000;
    }
}
