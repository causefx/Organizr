<?php
declare(strict_types=1);

if (class_exists('ParagonIE_Sodium_Core_Curve25519_Ge_P3', false)) {
    return;
}

/**
 * Class ParagonIE_Sodium_Core_Curve25519_Ge_P3
 */
class ParagonIE_Sodium_Core_Curve25519_Ge_P3
{
    public ParagonIE_Sodium_Core_Curve25519_Fe $X;
    public ParagonIE_Sodium_Core_Curve25519_Fe $Y;
    public ParagonIE_Sodium_Core_Curve25519_Fe $Z;
    public ParagonIE_Sodium_Core_Curve25519_Fe $T;

    /**
     * ParagonIE_Sodium_Core_Curve25519_Ge_P3 constructor.
     *
     * @internal You should not use this directly from another application
     *
     * @param ?ParagonIE_Sodium_Core_Curve25519_Fe $x
     * @param ?ParagonIE_Sodium_Core_Curve25519_Fe $y
     * @param ?ParagonIE_Sodium_Core_Curve25519_Fe $z
     * @param ?ParagonIE_Sodium_Core_Curve25519_Fe $t
     */
    public function __construct(
        ?ParagonIE_Sodium_Core_Curve25519_Fe $x = null,
        ?ParagonIE_Sodium_Core_Curve25519_Fe $y = null,
        ?ParagonIE_Sodium_Core_Curve25519_Fe $z = null,
        ?ParagonIE_Sodium_Core_Curve25519_Fe $t = null
    ) {
        if ($x === null) {
            $x = new ParagonIE_Sodium_Core_Curve25519_Fe();
        }
        $this->X = $x;
        if ($y === null) {
            $y = new ParagonIE_Sodium_Core_Curve25519_Fe();
        }
        $this->Y = $y;
        if ($z === null) {
            $z = new ParagonIE_Sodium_Core_Curve25519_Fe();
        }
        $this->Z = $z;
        if ($t === null) {
            $t = new ParagonIE_Sodium_Core_Curve25519_Fe();
        }
        $this->T = $t;
    }
}
