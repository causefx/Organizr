<?php
require_once 'api/functions.php';
$Organizr = new Organizr();
if ($Organizr->isLocalOrServer() && $Organizr->hasDB()) {
	// Set user as Organizr API
	$_GET['apikey'] = $Organizr->config['organizrAPI'];
	// Create a new scheduler
	$scheduler = new GO\Scheduler();
	// Clear any pre-existing jobs if any
	$scheduler->clearJobs();
	$Organizr->log('Cron')->debug('Cron process starting');
	// Auto-update Cron
	if ($Organizr->config['autoUpdateCronEnabled'] && $Organizr->config['autoUpdateCronSchedule']) {
		try {
			$schedule = new Cron\CronExpression($Organizr->config['autoUpdateCronSchedule']);
			$Organizr->log('Cron')->debug('Cron schedule has passed validation', ['schedule' => $Organizr->config['autoUpdateCronSchedule']]);
			$scheduler->call(
				function () use ($Organizr) {
					$Organizr->log('Cron')->debug('Running cron job', ['function' => 'Auto-update']);
					return $Organizr->updateOrganizr();
				})
				->then(function ($output) use ($Organizr) {
					$Organizr->log('Cron')->debug('Completed cron job', [
						'output' => $output,
					]);
				})
				->at($Organizr->config['autoUpdateCronSchedule']);
		} catch (InvalidArgumentException $e) {
			$Organizr->log('Cron')->warning('Cron schedule has failed validation', ['schedule' => $Organizr->config['autoUpdateCronSchedule']]);
			$Organizr->log('Cron')->error($e);
		} catch (Exception $e) {
			$Organizr->log('Cron')->error($e);
		}
	}
	// End Auto-update Cron
	// Auto-backup Cron
	if ($Organizr->config['autoBackupCronEnabled'] && $Organizr->config['autoBackupCronSchedule']) {
		try {
			$schedule = new Cron\CronExpression($Organizr->config['autoBackupCronSchedule']);
			$Organizr->log('Cron')->debug('Cron schedule has passed validation', ['schedule' => $Organizr->config['autoBackupCronSchedule']]);
			$scheduler->call(
				function () use ($Organizr) {
					$Organizr->log('Cron')->debug('Running cron job', ['function' => 'Auto-backup']);
					return $Organizr->backupOrganizr();
				})
				->then(function ($output) use ($Organizr) {
					$Organizr->log('Cron')->debug('Completed cron job', [
						'output' => $output,
					]);
				})
				->at($Organizr->config['autoBackupCronSchedule']);
		} catch (InvalidArgumentException $e) {
			$Organizr->log('Cron')->warning('Cron schedule has failed validation', ['schedule' => $Organizr->config['autoBackupCronSchedule']]);
			$Organizr->log('Cron')->error($e);
		} catch (Exception $e) {
			$Organizr->log('Cron')->error($e);
		}
	}
	// End Auto-backup Cron
	// Add plugin cron
	$Organizr->log('Cron')->debug('Checking if any plugins have cron jobs');
	foreach ($GLOBALS['cron'] as $cronJob) {
		if (isset($cronJob['enabled']) && isset($cronJob['class']) && isset($cronJob['function']) && isset($cronJob['schedule'])) {
			$Organizr->log('Cron')->debug('Starting cron job for function: ' . $cronJob['function'], ['cronJob' => $cronJob]);
			if ($Organizr->config[$cronJob['enabled']]) {
				$Organizr->log('Cron')->debug('Checking if cron job class exists', ['cronJob' => $cronJob]);
				if (class_exists($cronJob['class'])) {
					$Organizr->log('Cron')->debug('Class exists', ['cronJob' => $cronJob]);
					$Organizr->log('Cron')->debug('Validating cron job schedule', ['schedule' => $cronJob['schedule']]);
					try {
						$schedule = new Cron\CronExpression($Organizr->config[$cronJob['schedule']]);
						$Organizr->log('Cron')->debug('Cron schedule has passed validation', ['schedule' => $Organizr->config[$cronJob['schedule']]]);
						$plugin = new $cronJob['class']();
						$function = $cronJob['function'];
						$Organizr->log('Cron')->debug('Checking if cron job method exists', ['cronJob' => $cronJob]);
						if (method_exists($plugin, $function)) {
							$Organizr->log('Cron')->debug('Method exists', ['cronJob' => $cronJob]);
							$scheduler->call(
								function ($plugin, $function) use ($Organizr) {
									$Organizr->log('Cron')->debug('Running cron job', ['function' => $function]);
									return $plugin->$function();
								}, [$plugin, $function])
								->then(function ($output) use ($Organizr) {
									$Organizr->log('Cron')->debug('Completed cron job', [
										'output' => $output,
									]);
								})
								->at($Organizr->config[$cronJob['schedule']]);
						} else {
							$Organizr->log('Cron')->warning('Method error', ['cronJob' => $cronJob['class']]);
						}
					} catch (InvalidArgumentException $e) {
						$Organizr->log('Cron')->warning('Cron schedule has failed validation', ['schedule' => $Organizr->config[$cronJob['schedule']]]);
						$Organizr->log('Cron')->error($e);
						break;
					} catch (Exception $e) {
						$Organizr->log('Cron')->error($e);
						break;
					}
				} else {
					$Organizr->log('Cron')->warning('Class error', ['cronJob' => $cronJob['class']]);
				}
			} else {
				$Organizr->log('Cron')->debug('Cron job is not enabled', ['cronJob' => $cronJob]);
			}
		} else {
			$Organizr->log('Cron')->warning('Cron job was setup incorrectly', ['cronJob' => $cronJob]);
		}
	}
	$Organizr->log('Cron')->debug('Finished processing plugin cron jobs');
	/*
	 * Include plugin advanced cron
	 */
	$Organizr->log('Cron')->debug('Checking if any Plugins have advanced cron jobs');
	try {
		$directoryIterator = new RecursiveDirectoryIterator($Organizr->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins', FilesystemIterator::SKIP_DOTS);
		$iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
		foreach ($iteratorIterator as $info) {
			if ($info->getFilename() == 'advancedCron.php') {
				require_once $info->getPathname();
			}
		}
	} catch (UnexpectedValueException $e) {
		$Organizr->log('Cron')->error($e);
	}
	/*
	 * Include custom plugin advanced cron
	 */
	try {
		if (file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'plugins')) {
			$folder = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'plugins';
			$directoryIterator = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
			$iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
			foreach ($iteratorIterator as $info) {
				if ($info->getFilename() == 'advancedCron.php') {
					require_once $info->getPathname();
				}
			}
		}
	} catch (UnexpectedValueException $e) {
		$Organizr->log('Cron')->error($e);
	}
	$Organizr->log('Cron')->debug('Finished processing advanced plugin cron jobs');
	// Run cron jobs
	$scheduler->run();
	// Debug stuff
	//$Organizr->prettyPrint($scheduler->getVerboseOutput());
	//$Organizr->prettyPrint($scheduler->getFailedJobs());
	$Organizr->log('Cron')->debug('Cron process completion', ['verbose' => $scheduler->getVerboseOutput()]);
	if (!empty($scheduler->getFailedJobs())) {
		$Organizr->log('Cron')->warning('Cron jobs have failed', ['jobs' => $scheduler->getFailedJobs(), 'verbose' => $scheduler->getVerboseOutput()]);
	}
	// End Run and set file with time
	$Organizr->createCronFile();
} else {
	if ($Organizr->hasDB()) {
		$Organizr->log('Cron')->warning('Unauthorized user tried to access cron file');
		die($Organizr->showHTML('Unauthorized', 'Go-on.... Git!!!'));
	}
	die('Unauthorized');
}