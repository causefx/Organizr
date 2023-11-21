<?php

class UptimeKumaMetrics
{
    protected string $raw;
    private array $monitors = [];

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
            return $this->parseMonitorStatus($item);
        }, $monitors);
        $this->addLatencyToMonitors($monitors, $latencies);
        $this->monitors = array_values(array_filter($monitors));

        return $this;
    }

    public function getMonitors(): array
    {
        return $this->monitors;
    }

    private function parseMonitorStatus(string $status): ?array
    {  
        if (substr($status, -1) === '2') {
            return null;
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

    private function addLatencyToMonitors(array &$monitors, array $latencies)
    {
        $latencies = $this->getLatenciesByName($latencies);
        foreach ($monitors as &$monitor) {
            $monitor['latency'] = $latencies[$monitor['name']] ?? null;
        }
    }

    private function getLatenciesByName(array $latencies): array
    {
        $l = [];

        foreach ($latencies as $latency) {
            if (preg_match('/monitor_name="(.*)",monitor_type.* ([0-9]{1,})$/', $latency, $match)) {
                $l[$match[1]] = (int) $match[2];
            }
            continue;
        }

        return $l;
    }

    private function getStringBetweenQuotes(string $input): string
	{
		if (preg_match('/"(.*?)"/', $input, $match) == 1) {
			return $match[1];
		}
		return '';
	} 
}
