<?php
declare(strict_types=1);

namespace Lcobucci\Clock;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
