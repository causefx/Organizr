<?php
/*
 * Simple Cron job
 */
$GLOBALS['cron'][] = [
	'class' => 'HealthChecks', // Class name of plugin (case-sensitive)
	'enabled' => 'HEALTHCHECKS-cron-run-enabled', // Config item for job enable
	'schedule' => 'HEALTHCHECKS-cron-run-schedule', // Config item for job schedule
	'function' => '_healthCheckPluginRun', // Function to run during job
];