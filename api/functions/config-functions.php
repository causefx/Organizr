<?php
// Create config file in the return syntax
function createConfig($array, $path = null, $nest = 0)
{
	$path = ($path) ? $path : $GLOBALS['userConfigPath'];
	// Define Initial Value
	$output = array();
	// Sort Items
	ksort($array);
	// Update the current config version
	if (!$nest) {
		// Inject Current Version
		$output[] = "\t'configVersion' => '" . (isset($array['apply_CONFIG_VERSION']) ? $array['apply_CONFIG_VERSION'] : $GLOBALS['installedVersion']) . "'";
	}
	unset($array['configVersion']);
	unset($array['apply_CONFIG_VERSION']);
	// Process Settings
	foreach ($array as $k => $v) {
		$allowCommit = true;
		$item = '';
		switch (gettype($v)) {
			case 'boolean':
				$item = ($v ? 'true' : 'false');
				break;
			case 'integer':
			case 'double':
			case 'NULL':
				$item = $v;
				break;
			case 'string':
				$item = "'" . str_replace(array('\\', "'"), array('\\\\', "\'"), $v) . "'";
				break;
			case 'array':
				$item = createConfig($v, false, $nest + 1);
				break;
			default:
				$allowCommit = false;
		}
		if ($allowCommit) {
			$output[] = str_repeat("\t", $nest + 1) . "'$k' => $item";
		}
	}
	// Build output
	$output = (!$nest ? "<?php\nreturn " : '') . "array(\n" . implode(",\n", $output) . "\n" . str_repeat("\t", $nest) . ')' . (!$nest ? ';' : '');
	if (!$nest && $path) {
		$pathDigest = pathinfo($path);
		@mkdir($pathDigest['dirname'], 0770, true);
		if (file_exists($path)) {
			rename($path, $pathDigest['dirname'] . '/' . $pathDigest['filename'] . '.bak.php');
		}
		$file = fopen($path, 'w');
		fwrite($file, $output);
		fclose($file);
		if (file_exists($path)) {
			return true;
		}
		// writeLog("error", "config was unable to write");
		return false;
	} else {
		// writeLog("success", "config was updated with new values");
		return $output;
	}
}

// Commit new values to the configuration
function updateConfig($new, $current = false)
{
	// Get config if not supplied
	if ($current === false) {
		$current = loadConfig();
	} elseif (is_string($current) && is_file($current)) {
		$current = loadConfig($current);
	}
	// Inject Parts
	foreach ($new as $k => $v) {
		$current[$k] = $v;
	}
	// Return Create
	return createConfig($current);
}

function configLazy()
{
	// Load config or default
	if (file_exists($GLOBALS['userConfigPath'])) {
		$config = fillDefaultConfig(loadConfig($GLOBALS['userConfigPath']));
	} else {
		$config = fillDefaultConfig(loadConfig($GLOBALS['defaultConfigPath']));
	}
	if (is_array($config)) {
		defineConfig($config);
	}
	return $config;
}

function loadConfig($path = null)
{
	$path = ($path) ? $path : $GLOBALS['userConfigPath'];
	if (!is_file($path)) {
		return null;
	} else {
		return (array)call_user_func(function () use ($path) {
			return include($path);
		});
	}
}

function fillDefaultConfig($array)
{
	$path = $GLOBALS['defaultConfigPath'];
	if (is_string($path)) {
		$loadedDefaults = loadConfig($path);
	} else {
		$loadedDefaults = $path;
	}
	// Include all plugin config files
	foreach (glob(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . "*.php") as $filename) {
		$loadedDefaults = array_merge($loadedDefaults, loadConfig($filename));
	}
	return (is_array($loadedDefaults) ? fillDefaultConfig_recurse($array, $loadedDefaults) : false);
}

function fillDefaultConfig_recurse($current, $defaults)
{
	foreach ($defaults as $k => $v) {
		if (!isset($current[$k])) {
			$current[$k] = $v;
		} elseif (is_array($current[$k]) && is_array($v)) {
			$current[$k] = fillDefaultConfig_recurse($current[$k], $v);
		}
	}
	return $current;
}

function defineConfig($array, $anyCase = true, $nest_prefix = false)
{
	foreach ($array as $k => $v) {
		if (is_scalar($v) && !defined($nest_prefix . $k)) {
			$GLOBALS[$nest_prefix . $k] = $v;
		} elseif (is_array($v)) {
			defineConfig($v, $anyCase, $nest_prefix . $k . '_');
		}
	}
}
