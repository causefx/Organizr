<?php
/*
 * Simple Cron job
 */
/* COMMENTED OUT AS THIS IS AN EXAMPLE
// Initiate Class
$plugin = new HealthChecks();
// Set logger to CRON Channel
$plugin->setLoggerChannel('CRON');
// Check to see if plugin cron job is enabled and check if schedule is set in config value
if ($plugin->config['HEALTHCHECKS-cron-run-enabled'] && $plugin->config['HEALTHCHECKS-cron-run-schedule'] !== '') {
	$plugin->logger->debug('Starting cron job for function: HealthChecks run', ['cronJob' => 'HealthChecks']);
	$plugin->logger->debug('Validating cron job schedule', ['schedule' => $plugin->config['HEALTHCHECKS-cron-run-schedule']]);
	// Validate if schedule is in correct cron format
	try {
		$schedule = new Cron\CronExpression($plugin->config['HEALTHCHECKS-cron-run-schedule']);
		$plugin->logger->debug('Cron schedule has passed validation', ['schedule' => $plugin->config['HEALTHCHECKS-cron-run-schedule']]);
	} catch (InvalidArgumentException $e) {
		$plugin->logger->critical($e->getMessage());
	}
	// Setup job for cron
	$scheduler->call(
		function ($plugin) {
			$plugin->logger->debug('Starting cron job for function: HealthChecks run');
			return $plugin->_healthCheckPluginRun();
		}, [$plugin])
		->then(function ($output) use ($plugin) {
			$plugin->logger->debug('Completed cron job', [
				'output' => $output,
			]);
		})
		->at($plugin->config['HEALTHCHECKS-cron-run-schedule']);
} else {
	$plugin->logger->debug('Cron job is not enabled or is set up incorrectly', ['cronJob' => 'HealthChecks']);
}
*/