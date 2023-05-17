<?php

class UptimeKumaMetrics
{
    protected string $raw;
    private array $monitors = [];
    private array $latencies = [];

    public function __construct(string $raw)
    {
        $this->raw = $raw;
    }

    public function process(): self
    {
        $processed = explode(PHP_EOL, $this->raw);

        $monitors = array_filter($processed, function (string $item) {
            return str_starts_with($item, 'monitor_status');
        });
        // TODO: parse the latencies and add them on to the info card
        $latencies = array_filter($processed, function (string $item) {
            return str_starts_with($item, 'monitor_response_time');
        });
        
        $monitors = array_map(function (string $item) {
            try {
                return $this->parseMonitorStatus($item);
            } catch (Exception $e) {
                // do nothing when monitor is disabled
            }
        }, $monitors);
        $this->monitors = array_values(array_filter($monitors));

        return $this;
    }

    public function getMonitors(): array
    {
        return $this->monitors;
    }

    private function parseMonitorStatus(string $status): array
    {  
        if (substr($status, -1) === '2') {
			throw new Exception("monitor diasbled");
		}

		$up = (substr($status, -1)) == '0' ? false : true;
		$status = substr($status, 15);
		$status = substr($status, 0, -4);
		$status = explode(',', $status);
		$data = [
			'name' => $this->getStringBetweenQuotes($status[0]),
			'url' => $this->getStringBetweenQuotes($status[2]),
			'type' => $this->getStringBetweenQuotes($status[1]),
			'status' => $up,
		];

		return $data;
    }

    private function getStringBetweenQuotes(string $input): string
	{
		if (preg_match('/"(.*?)"/', $input, $match) == 1) {
			return $match[1];
		}
		return '';
	} 
}
