<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Bridges\Nette;

use Dibi;
use Nette;
use Tracy;


/**
 * Dibi extension for Nette Framework 2.2. Creates 'connection' & 'panel' services.
 */
class DibiExtension22 extends Nette\DI\CompilerExtension
{
	/** @var bool|null */
	private $debugMode;

	/** @var bool|null */
	private $cliMode;


	public function __construct(?bool $debugMode = null, ?bool $cliMode = null)
	{
		$this->debugMode = $debugMode;
		$this->cliMode = $cliMode;
	}


	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->getConfig();

		if ($this->debugMode === null) {
			$this->debugMode = $container->parameters['debugMode'];
		}

		if ($this->cliMode === null) {
			$this->cliMode = $container->parameters['consoleMode'];
		}

		$useProfiler = $config['profiler'] ?? (class_exists(Tracy\Debugger::class) && $this->debugMode && !$this->cliMode);

		unset($config['profiler']);

		if (isset($config['flags'])) {
			$flags = 0;
			foreach ((array) $config['flags'] as $flag) {
				$flags |= constant($flag);
			}

			$config['flags'] = $flags;
		}

		$connection = $container->addDefinition($this->prefix('connection'))
			->setFactory(Dibi\Connection::class, [$config])
			->setAutowired($config['autowired'] ?? true);

		if (class_exists(Tracy\Debugger::class)) {
			$connection->addSetup(
				[new Nette\DI\Statement('Tracy\Debugger::getBlueScreen'), 'addPanel'],
				[[Dibi\Bridges\Tracy\Panel::class, 'renderException']]
			);
		}

		if ($useProfiler) {
			$panel = $container->addDefinition($this->prefix('panel'))
				->setFactory(Dibi\Bridges\Tracy\Panel::class, [
					$config['explain'] ?? true,
					isset($config['filter']) && $config['filter'] === false ? Dibi\Event::ALL : Dibi\Event::QUERY,
				]);
			$connection->addSetup([$panel, 'register'], [$connection]);
		}
	}
}
