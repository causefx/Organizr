<?php

if (class_exists('ParagonIE_Sodium_Core32_Curve25519_Fe', false)) {
    return;
}

/**
 * Class ParagonIE_Sodium_Core32_Curve25519_Fe
 *
 * This represents a Field Element
 */
class ParagonIE_Sodium_Core32_Curve25519_Fe implements ArrayAccess
{
    /**
     * @var array<int, ParagonIE_Sodium_Core32_Int32>
     */
    protected $container = array();

    /**
     * @var int
     */
    protected $size = 10;

    /**
     * ParagonIE_Sodium_Core32_Curve25519_Fe constructor.
     * @param int $size
     */
    public function __construct($size = 10)
    {
        $this->size = 10;
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param array<int, ParagonIE_Sodium_Core32_Int32> $array
     * @param bool $save_indexes
     * @return self
     * @throws SodiumException
     * @throws TypeError
     */
    public static function fromArray($array, $save_indexes = null)
    {
        $count = count($array);
        if ($save_indexes) {
            $keys = array_keys($array);
        } else {
            $keys = range(0, $count - 1);
        }
        $array = array_values($array);

        $obj = new ParagonIE_Sodium_Core32_Curve25519_Fe($count);
        if ($save_indexes) {
            for ($i = 0; $i < $count; ++$i) {
                $array[$i]->overflow = 0;
                $obj->offsetSet($keys[$i], $array[$i]);
            }
        } else {
            for ($i = 0; $i < $count; ++$i) {
                $array[$i]->overflow = 0;
                $obj->offsetSet($i, $array[$i]);
            }
        }
        return $obj;
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param array<int, int> $array
     * @param bool $save_indexes
     * @return self
     * @throws SodiumException
     * @throws TypeError
     */
    public static function fromIntArray($array, $save_indexes = null)
    {
        $count = count($array);
        if ($save_indexes) {
            $keys = array_keys($array);
        } else {
            $keys = range(0, $count - 1);
        }
        $array = array_values($array);
        $set = array();
        /** @var int $i */
        /** @var int $v */
        foreach ($array as $i => $v) {
            $set[$i] = ParagonIE_Sodium_Core32_Int32::fromInt($v);
        }

        $obj = new ParagonIE_Sodium_Core32_Curve25519_Fe($count);
        if ($save_indexes) {
            for ($i = 0; $i < $count; ++$i) {
                $set[$i]->overflow = 0;
                $obj->offsetSet($keys[$i], $set[$i]);
            }
        } else {
            for ($i = 0; $i < $count; ++$i) {
                $set[$i]->overflow = 0;
                $obj->offsetSet($i, $set[$i]);
            }
        }
        return $obj;
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     * @throws SodiumException
     * @throws TypeError
     */
    public function offsetSet($offset, $value)
    {
        if (!($value instanceof ParagonIE_Sodium_Core32_Int32)) {
            throw new InvalidArgumentException('Expected an instance of ParagonIE_Sodium_Core32_Int32');
        }
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            ParagonIE_Sodium_Core32_Util::declareScalarType($offset, 'int', 1);
            $this->container[(int) $offset] = $value;
        }
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param mixed $offset
     * @return bool
     * @psalm-suppress MixedArrayOffset
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param mixed $offset
     * @return void
     * @psalm-suppress MixedArrayOffset
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param mixed $offset
     * @return ParagonIE_Sodium_Core32_Int32
     * @psalm-suppress MixedArrayOffset
     */
    public function offsetGet($offset)
    {
        if (!isset($this->container[$offset])) {
            $this->container[(int) $offset] = new ParagonIE_Sodium_Core32_Int32();
        }
        /** @var ParagonIE_Sodium_Core32_Int32 $get */
        $get = $this->container[$offset];
        return $get;
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @return array
     */
    public function __debugInfo()
    {
        if (empty($this->container)) {
            return array();
        }
        $c = array(
            (int) ($this->container[0]->toInt()),
            (int) ($this->container[1]->toInt()),
            (int) ($this->container[2]->toInt()),
            (int) ($this->container[3]->toInt()),
            (int) ($this->container[4]->toInt()),
            (int) ($this->container[5]->toInt()),
            (int) ($this->container[6]->toInt()),
            (int) ($this->container[7]->toInt()),
            (int) ($this->container[8]->toInt()),
            (int) ($this->container[9]->toInt())
        );
        
        /* 
        $c = array(
            ParagonIE_Sodium_Core_Util::bin2hex($this->container[0]->toReverseString()),
            ParagonIE_Sodium_Core_Util::bin2hex($this->container[1]->toReverseString()),
            ParagonIE_Sodium_Core_Util::bin2hex($this->container[2]->toReverseString()),
            ParagonIE_Sodium_Core_Util::bin2hex($this->container[3]->toReverseString()),
            ParagonIE_Sodium_Core_Util::bin2hex($this->container[4]->toReverseString()),
            ParagonIE_Sodium_Core_Util::bin2hex($this->container[5]->toReverseString()),
            ParagonIE_Sodium_Core_Util::bin2hex($this->container[6]->toReverseString()),
            ParagonIE_Sodium_Core_Util::bin2hex($this->container[7]->toReverseString()),
            ParagonIE_Sodium_Core_Util::bin2hex($this->container[8]->toReverseString()),
            ParagonIE_Sodium_Core_Util::bin2hex($this->container[9]->toReverseString())
        );
        $c = array(
            json_encode($this->container[0]->limbs),
            json_encode($this->container[1]->limbs),
            json_encode($this->container[2]->limbs),
            json_encode($this->container[3]->limbs),
            json_encode($this->container[4]->limbs),
            json_encode($this->container[5]->limbs),
            json_encode($this->container[6]->limbs),
            json_encode($this->container[7]->limbs),
            json_encode($this->container[8]->limbs),
            json_encode($this->container[9]->limbs)
        );
        */
        return array(implode(', ', $c));
    }
}
