<?php
/*
 * Simple Cron job
 */
$GLOBALS['cron'][] = [
	'class' => 'ShuckStop', // Class name of plugin (case-sensitive)
	'enabled' => 'SHUCKSTOP-cron-run-enabled', // Config item for job enable
	'schedule' => 'SHUCKSTOP-cron-run-schedule', // Config item for job schedule
	'function' => '_shuckStopPluginRun', // Function to run during job
];