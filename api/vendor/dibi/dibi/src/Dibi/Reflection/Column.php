<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Reflection;

use Dibi;


/**
 * Reflection metadata class for a table or result set column.
 *
 * @property-read string $name
 * @property-read string $fullName
 * @property-read Table $table
 * @property-read string $type
 * @property-read mixed $nativeType
 * @property-read int|null $size
 * @property-read bool $nullable
 * @property-read bool $autoIncrement
 * @property-read mixed $default
 */
class Column
{
	use Dibi\Strict;

	/** @var Dibi\Reflector|null when created by Result */
	private $reflector;

	/** @var array (name, nativetype, [table], [fullname], [size], [nullable], [default], [autoincrement], [vendor]) */
	private $info;


	public function __construct(?Dibi\Reflector $reflector, array $info)
	{
		$this->reflector = $reflector;
		$this->info = $info;
	}


	public function getName(): string
	{
		return $this->info['name'];
	}


	public function getFullName(): string
	{
		return $this->info['fullname'] ?? null;
	}


	public function hasTable(): bool
	{
		return !empty($this->info['table']);
	}


	public function getTable(): Table
	{
		if (empty($this->info['table']) || !$this->reflector) {
			throw new Dibi\Exception('Table is unknown or not available.');
		}

		return new Table($this->reflector, ['name' => $this->info['table']]);
	}


	public function getTableName(): ?string
	{
		return isset($this->info['table']) && $this->info['table'] != null // intentionally ==
			? $this->info['table']
			: null;
	}


	public function getType(): ?string
	{
		return Dibi\Helpers::getTypeCache()->{$this->info['nativetype']};
	}


	public function getNativeType(): string
	{
		return $this->info['nativetype'];
	}


	public function getSize(): ?int
	{
		return isset($this->info['size']) ? (int) $this->info['size'] : null;
	}


	public function isNullable(): bool
	{
		return !empty($this->info['nullable']);
	}


	public function isAutoIncrement(): bool
	{
		return !empty($this->info['autoincrement']);
	}


	/** @return mixed */
	public function getDefault()
	{
		return $this->info['default'] ?? null;
	}


	/** @return mixed */
	public function getVendorInfo(string $key)
	{
		return $this->info['vendor'][$key] ?? null;
	}
}
