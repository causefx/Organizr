<?php

use Monolog\Logger;
use Monolog\Level;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;

/**
 * Custom JSON formatter that outputs fields compatible with Organizr's JS log viewer
 * Maps Monolog 3 field names to the expected legacy format
 */
class OrganizrJsonFormatter extends JsonFormatter
{
	public function format(LogRecord $record): string
	{
		$data = $record->toArray();
		$extra = $data['extra'] ?? [];

		// Build output with legacy field names
		$output = [
			'log_level' => strtoupper($data['level_name'] ?? 'INFO'),
			'message' => $data['message'] ?? '',
			'channel' => $data['channel'] ?? 'Organizr',
			'username' => $extra['trace_id'] ?? '',
			'trace_id' => $this->generateTraceId(),
			'file' => $extra['file'] ?? '',
			'line' => $extra['line'] ?? 0,
			'context' => $data['context'] ?? [],
			'remote_ip_address' => $extra['remote_ip_address'] ?? $extra['ip'] ?? '',
			'server_ip_address' => $extra['server_ip_address'] ?? '',
			'user_agent' => $extra['user_agent'] ?? '',
			'datetime' => $data['datetime']?->format('Y-m-d H:i:s.u') ?? date('Y-m-d H:i:s'),
			'timezone' => $extra['timezone'] ?? 'UTC',
			'process_time' => $extra['process_time'] ?? 0,
		];

		return json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
	}

	private function generateTraceId(): string
	{
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}
}

class OrganizrLogger
{
	public $isReady = false;
	private $channel = 'Organizr';
	private $logLevel = null;
	private $maxFiles = 7;
	private $fileName = '';
	private $traceId = '';
	private $slackWebhookHandler = null;

	public function __construct()
	{
		$this->logLevel = Level::Debug;
	}

	// Log level constants for backward compatibility
	const DEBUG = 100;
	const INFO = 200;
	const NOTICE = 250;
	const WARNING = 300;
	const ERROR = 400;
	const CRITICAL = 500;
	const ALERT = 550;
	const EMERGENCY = 600;

	public function getReadyStatus(): bool
	{
		return $this->isReady;
	}

	public function setReadyStatus(bool $readyStatus)
	{
		$this->isReady = $readyStatus;
	}

	public function setChannel(string $channel)
	{
		$this->channel = $channel;
	}

	public function setLogLevel(int $level)
	{
		$this->logLevel = match($level) {
			self::DEBUG => Level::Debug,
			self::INFO => Level::Info,
			self::NOTICE => Level::Notice,
			self::WARNING => Level::Warning,
			self::ERROR => Level::Error,
			self::CRITICAL => Level::Critical,
			self::ALERT => Level::Alert,
			self::EMERGENCY => Level::Emergency,
			default => Level::Warning,
		};
	}

	public function setMaxFiles(int $maxFiles)
	{
		$this->maxFiles = $maxFiles;
	}

	public function setFileName(string $fileName)
	{
		$this->fileName = $fileName;
	}

	public function setTraceId(string $traceId)
	{
		$this->traceId = $traceId;
	}

	public function getSlackWebhookHandler(): ?SlackWebhookHandler
	{
		return $this->slackWebhookHandler;
	}

	public function setSlackWebhookHandler(SlackWebhookHandler $slackWebhookHandler)
	{
		$this->slackWebhookHandler = $slackWebhookHandler;
	}

	public function build(): OrganizrLoggerInstance
	{
		if (!$this->isReady) {
			$this->channel = 'Organizr';
			$this->logLevel = Level::Debug;
			$this->maxFiles = 1;
		}
		return new OrganizrLoggerInstance(
			$this->channel,
			$this->fileName,
			$this->maxFiles,
			$this->logLevel,
			$this->traceId,
			$this->slackWebhookHandler
		);
	}
}

class OrganizrLoggerInstance extends Logger
{
	private string $traceId = '';
	private string $channelName = 'Organizr';

	public function __construct(
		string $channel,
		string $fileName,
		int $maxFiles,
		Level $logLevel,
		string $traceId = '',
		?SlackWebhookHandler $slackHandler = null
	) {
		parent::__construct($channel);
		$this->channelName = $channel;
		$this->traceId = $traceId;

		// Add rotating file handler with JSON formatting
		if ($fileName) {
			$handler = new RotatingFileHandler($fileName, $maxFiles, $logLevel);
			$formatter = new OrganizrJsonFormatter();
			$handler->setFormatter($formatter);
			$this->pushHandler($handler);
		}

		// Add slack handler if configured
		if ($slackHandler) {
			$this->pushHandler($slackHandler);
		}

		// Add PSR-3 message processor
		$this->pushProcessor(new PsrLogMessageProcessor());

		// Add introspection processor for file/line info
		$this->pushProcessor(new IntrospectionProcessor(Level::Debug, ['Monolog\\']));

		// Add web processor for IP/user agent info
		$this->pushProcessor(new WebProcessor());

		// Add custom processor for trace_id, timezone, process_time
		$startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
		$traceId = $this->traceId;
		$this->pushProcessor(function (LogRecord $record) use ($startTime, $traceId): LogRecord {
			return $record->with(extra: array_merge($record->extra, [
				'trace_id' => $traceId,
				'remote_ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
				'server_ip_address' => $_SERVER['SERVER_ADDR'] ?? '',
				'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
				'timezone' => date_default_timezone_get(),
				'process_time' => microtime(true) - $startTime,
			]));
		});
	}

	public function getChannel(): string
	{
		return $this->channelName;
	}

	public function setChannel(string $channel): void
	{
		$this->channelName = $channel;
	}

	public function getTraceId(): string
	{
		return $this->traceId;
	}

	public function setUsername(string $username): void
	{
		$this->traceId = $username;
	}
}

class SlackWebhookHandlerBuilder
{
	private string $webhookUrl;
	private ?string $channel;
	private $level;

	public function __construct(string $webhookUrl, ?string $channel = null)
	{
		$this->webhookUrl = $webhookUrl;
		$this->channel = $channel;
		$this->level = Level::Warning;
	}

	public function setLevel(int $level)
	{
		$this->level = match($level) {
			OrganizrLogger::DEBUG => Level::Debug,
			OrganizrLogger::INFO => Level::Info,
			OrganizrLogger::NOTICE => Level::Notice,
			OrganizrLogger::WARNING => Level::Warning,
			OrganizrLogger::ERROR => Level::Error,
			OrganizrLogger::CRITICAL => Level::Critical,
			OrganizrLogger::ALERT => Level::Alert,
			OrganizrLogger::EMERGENCY => Level::Emergency,
			default => Level::Warning,
		};
	}

	public function build(): SlackWebhookHandler
	{
		return new SlackWebhookHandler(
			$this->webhookUrl,
			$this->channel,
			'Organizr',
			true,
			null,
			false,
			false,
			$this->level
		);
	}
}
