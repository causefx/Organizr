<?php
declare(strict_types=1);

if (class_exists('ParagonIE_Sodium_Core_Curve25519_Fe', false)) {
    return;
}

/**
 * Class ParagonIE_Sodium_Core_Curve25519_Fe
 *
 * This represents a Field Element
 *
 * @psalm-suppress MissingTemplateParam
 */
class ParagonIE_Sodium_Core_Curve25519_Fe implements ArrayAccess
{
    public function __construct(
        public int $e0 = 0,
        public int $e1 = 0,
        public int $e2 = 0,
        public int $e3 = 0,
        public int $e4 = 0,
        public int $e5 = 0,
        public int $e6 = 0,
        public int $e7 = 0,
        public int $e8 = 0,
        public int $e9 = 0,
    ) {}

    /**
     * @internal You should not use this directly from another application
     *
     * @param array<int, int> $array
     * @return self
     * @throws SodiumException
     */
    public static function fromArray(array $array): self
    {
        if (count($array) !== 10) {
            throw new SodiumException('Fewer than 10 items received');
        }
        $values = array_values($array);
        return new self(
            $values[0],
            $values[1],
            $values[2],
            $values[3],
            $values[4],
            $values[5],
            $values[6],
            $values[7],
            $values[8],
            $values[9],
        );
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param int|null $offset
     * @param int $value
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        switch ($offset) {
            case 0:
                $this->e0 = $value;
                break;
            case 1:
                $this->e1 = $value;
                break;
            case 2:
                $this->e2 = $value;
                break;
            case 3:
                $this->e3 = $value;
                break;
            case 4:
                $this->e4 = $value;
                break;
            case 5:
                $this->e5 = $value;
                break;
            case 6:
                $this->e6 = $value;
                break;
            case 7:
                $this->e7 = $value;
                break;
            case 8:
                $this->e8 = $value;
                break;
            case 9:
                $this->e9 = $value;
                break;
            default:
                throw new OutOfBoundsException('Invalid offset.');
        }
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param int $offset
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return $offset >= 0 && $offset < 10;
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param int $offset
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        switch ($offset) {
            case 0:
                $this->e0 = 0;
                break;
            case 1:
                $this->e1 = 0;
                break;
            case 2:
                $this->e2 = 0;
                break;
            case 3:
                $this->e3 = 0;
                break;
            case 4:
                $this->e4 = 0;
                break;
            case 5:
                $this->e5 = 0;
                break;
            case 6:
                $this->e6 = 0;
                break;
            case 7:
                $this->e7 = 0;
                break;
            case 8:
                $this->e8 = 0;
                break;
            case 9:
                $this->e9 = 0;
                break;
            default:
                throw new OutOfBoundsException('Invalid offset.');
        }
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param int $offset
     * @return int
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset): int
    {
        return match ($offset) {
            0 => $this->e0,
            1 => $this->e1,
            2 => $this->e2,
            3 => $this->e3,
            4 => $this->e4,
            5 => $this->e5,
            6 => $this->e6,
            7 => $this->e7,
            8 => $this->e8,
            9 => $this->e9,
            default => throw new OutOfBoundsException('Invalid offset.'),
        };
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @return array
     */
    public function __debugInfo()
    {
        return array(
            implode(', ', [
                $this->e0, $this->e1, $this->e2, $this->e3, $this->e4,
                $this->e5, $this->e6, $this->e7, $this->e8, $this->e9
            ])
        );
    }
}
