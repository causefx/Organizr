<?php
declare(strict_types=1);

namespace Lcobucci\Clock;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;

use function date_default_timezone_get;

final class FrozenClock implements Clock
{
    public function __construct(private DateTimeImmutable $now)
    {
    }

    public static function fromUTC(): self
    {
        return new self(new DateTimeImmutable('now', new DateTimeZone('UTC')));
    }

    public static function fromSystemTimezone(): self
    {
        return new self(new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get())));
    }

    public function setTo(DateTimeImmutable $now): void
    {
        $this->now = $now;
    }

    /**
     * Adjusts the current time by a given modifier.
     *
     * @param non-empty-string $modifier @see https://www.php.net/manual/en/datetime.formats.php
     *
     * @throws DateMalformedStringException When an invalid date/time string is passed.
     */
    public function adjustTime(string $modifier): void
    {
        $this->now = $this->now->modify($modifier);
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
