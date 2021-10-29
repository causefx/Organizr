<?php
require_once 'api/functions.php';
$Organizr = new Organizr();
$Organizr->setLoggerChannel('Cron');
if ($Organizr->isLocalOrServer() && $Organizr->hasDB()) {
	// Set user as Organizr API
	$_GET['apikey'] = $Organizr->config['organizrAPI'];
	// Create a new scheduler
	$scheduler = new GO\Scheduler();
	// Clear any pre-existing jobs if any
	$scheduler->clearJobs();
	$Organizr->logger->debug('Cron process starting');
	// Auto-update Cron
	if ($Organizr->config['autoUpdateCronEnabled'] && $Organizr->config['autoUpdateCronSchedule']) {
		try {
			$schedule = new Cron\CronExpression($Organizr->config['autoUpdateCronSchedule']);
			$Organizr->logger->debug('Cron schedule has passed validation', ['schedule' => $Organizr->config['autoUpdateCronSchedule']]);
			$scheduler->call(
				function () use ($Organizr) {
					$Organizr->logger->debug('Running cron job', ['function' => 'Auto-update']);
					return $Organizr->updateOrganizr();
				})
				->then(function ($output) use ($Organizr) {
					$Organizr->logger->debug('Completed cron job', [
						'output' => $output,
					]);
				})
				->at($Organizr->config['autoUpdateCronSchedule']);
		} catch (InvalidArgumentException $e) {
			$Organizr->logger->warning('Cron schedule has failed validation', ['schedule' => $Organizr->config['autoUpdateCronSchedule']]);
			$Organizr->logger->error($e);
		} catch (Exception $e) {
			$Organizr->logger->error($e);
		}
	}
	// End Auto-update Cron
	// Add plugin cron
	$Organizr->logger->debug('Checking if any plugins have cron jobs');
	foreach ($GLOBALS['cron'] as $cronJob) {
		if (isset($cronJob['enabled']) && isset($cronJob['class']) && isset($cronJob['function']) && isset($cronJob['schedule'])) {
			$Organizr->logger->debug('Starting cron job for function: ' . $cronJob['function'], ['cronJob' => $cronJob]);
			if ($Organizr->config[$cronJob['enabled']]) {
				$Organizr->logger->debug('Checking if cron job class exists', ['cronJob' => $cronJob]);
				if (class_exists($cronJob['class'])) {
					$Organizr->logger->debug('Class exists', ['cronJob' => $cronJob]);
					$Organizr->logger->debug('Validating cron job schedule', ['schedule' => $cronJob['schedule']]);
					try {
						$schedule = new Cron\CronExpression($Organizr->config[$cronJob['schedule']]);
						$Organizr->logger->debug('Cron schedule has passed validation', ['schedule' => $Organizr->config[$cronJob['schedule']]]);
						$plugin = new $cronJob['class']();
						$function = $cronJob['function'];
						$Organizr->logger->debug('Checking if cron job method exists', ['cronJob' => $cronJob]);
						if (method_exists($plugin, $function)) {
							$Organizr->logger->debug('Method exists', ['cronJob' => $cronJob]);
							$scheduler->call(
								function ($plugin, $function) use ($Organizr) {
									$Organizr->logger->debug('Running cron job', ['function' => $function]);
									return $plugin->$function();
								}, [$plugin, $function])
								->then(function ($output) use ($Organizr) {
									$Organizr->logger->debug('Completed cron job', [
										'output' => $output,
									]);
								})
								->at($Organizr->config[$cronJob['schedule']]);
						} else {
							$Organizr->warning('Method error', ['cronJob' => $cronJob['class']]);
						}
					} catch (InvalidArgumentException $e) {
						$Organizr->logger->warning('Cron schedule has failed validation', ['schedule' => $Organizr->config[$cronJob['schedule']]]);
						$Organizr->logger->error($e);
						break;
					} catch (Exception $e) {
						$Organizr->logger->error($e);
						break;
					}
				} else {
					$Organizr->warning('Class error', ['cronJob' => $cronJob['class']]);
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
	// End Run and set file with time
	$Organizr->createCronFile();
} else {
	if ($Organizr->hasDB()) {
		$Organizr->logger->warning('Unauthorized user tried to access cron file');
		die($Organizr->showHTML('Unauthorized', 'Go-on.... Git!!!'));
	}
	die('Unauthorized');
}