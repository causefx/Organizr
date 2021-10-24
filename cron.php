<?php
require_once 'api/functions.php';
$Organizr = new Organizr();
$Organizr->setLoggerChannel('Cron');
if ($Organizr->isLocalOrServer()) {
	// Create a new scheduler
	$scheduler = new GO\Scheduler();
	// Clear any pre-existing jobs if any
	$scheduler->clearJobs();
	$Organizr->logger->debug('Cron process starting');
	// Add plugin cron
	$Organizr->logger->debug('Checking if any plugins have cron jobs');
	foreach ($GLOBALS['cron'] as $cronJob) {
		if (isset($cronJob['enabled']) && isset($cronJob['class']) && isset($cronJob['function']) && isset($cronJob['frequency'])) {
			if ($Organizr->config[$cronJob['enabled']]) {
				if (class_exists($cronJob['class'])) {
					$Organizr->logger->debug('Starting cron job for function: ' . $cronJob['function'], ['cronJob' => $cronJob]);
					$Organizr->logger->debug('Validating cron job frequency', ['frequency' => $cronJob['frequency']]);
					try {
						$frequency = new Cron\CronExpression($Organizr->config[$cronJob['frequency']]);
						$Organizr->logger->debug('Cron frequency has passed validation', ['frequency' => $Organizr->config[$cronJob['frequency']]]);
					} catch (InvalidArgumentException $e) {
						$Organizr->logger->warn('Cron frequency has failed validation', ['frequency' => $Organizr->config[$cronJob['frequency']]]);
						$Organizr->logger->error($e);
						break;
					}
					$plugin = new $cronJob['class']();
					$function = $cronJob['function'];
					if (method_exists($plugin, $function)) {
						$scheduler->call(
							function ($plugin, $function) use ($Organizr) {
								$Organizr->logger->debug('Starting cron job for function: ' . $function);
								return $plugin->$function();
							}, [$plugin, $function])
							->then(function ($output) use ($Organizr) {
								$Organizr->logger->debug('Completed cron job', [
									'output' => $output,
								]);
							})
							->at($Organizr->config[$cronJob['frequency']]);
					}
				}
			} else {
				$Organizr->logger->debug('Cron job is not enabled', ['cronJob' => $cronJob]);
			}
		} else {
			$Organizr->logger->warning('Cron job was setup incorrectly', ['cronJob' => $cronJob]);
		}
	}
	$Organizr->logger->debug('Finished processing plugin cron jobs');
	/*
	 * Include plugin advanced cron
	 */
	$Organizr->logger->debug('Checking if any Plugins have advanced cron jobs');
	try {
		$directoryIterator = new RecursiveDirectoryIterator($Organizr->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins', FilesystemIterator::SKIP_DOTS);
		$iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
		foreach ($iteratorIterator as $info) {
			if ($info->getFilename() == 'advancedCron.php') {
				require_once $info->getPathname();
			}
		}
	} catch (UnexpectedValueException $e) {
		$Organizr->logger->error($e);
	}
	$Organizr->logger->debug('Finished processing advanced plugin cron jobs');
	// Run cron jobs
	$scheduler->run();
	// Debug stuff
	//$Organizr->prettyPrint($scheduler->getVerboseOutput());
	//$Organizr->prettyPrint($scheduler->getFailedJobs());
	$Organizr->logger->debug('Cron process completion', ['verbose' => $scheduler->getVerboseOutput()]);
	if (!empty($scheduler->getFailedJobs())) {
		$Organizr->logger->warning('Cron jobs have failed', ['jobs' => $scheduler->getFailedJobs()]);
	}
} else {
	$Organizr->logger->warning('Unauthorized user tried to access cron file');
	die($Organizr->showHTML('Unauthorized', 'Go-on.... Git!!!'));
}