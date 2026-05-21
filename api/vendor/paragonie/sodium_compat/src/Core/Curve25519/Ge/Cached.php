<?php
declare(strict_types=1);

if (class_exists('ParagonIE_Sodium_Core_Curve25519_Ge_Cached', false)) {
    return;
}
/**
 * Class ParagonIE_Sodium_Core_Curve25519_Ge_Cached
 */
class ParagonIE_Sodium_Core_Curve25519_Ge_Cached
{
    public ParagonIE_Sodium_Core_Curve25519_Fe $YplusX;
    public ParagonIE_Sodium_Core_Curve25519_Fe $YminusX;
    public ParagonIE_Sodium_Core_Curve25519_Fe $Z;
    public ParagonIE_Sodium_Core_Curve25519_Fe $T2d;

    /**
     * ParagonIE_Sodium_Core_Curve25519_Ge_Cached constructor.
     *
     * @internal You should not use this directly from another application
     *
     * @param ?ParagonIE_Sodium_Core_Curve25519_Fe $YplusX
     * @param ?ParagonIE_Sodium_Core_Curve25519_Fe $YminusX
     * @param ?ParagonIE_Sodium_Core_Curve25519_Fe $Z
     * @param ?ParagonIE_Sodium_Core_Curve25519_Fe $T2d
     */
    public function __construct(
        ?ParagonIE_Sodium_Core_Curve25519_Fe $YplusX = null,
        ?ParagonIE_Sodium_Core_Curve25519_Fe $YminusX = null,
        ?ParagonIE_Sodium_Core_Curve25519_Fe $Z = null,
        ?ParagonIE_Sodium_Core_Curve25519_Fe $T2d = null
    ) {
        if ($YplusX === null) {
            $YplusX = new ParagonIE_Sodium_Core_Curve25519_Fe();
        }
        $this->YplusX = $YplusX;
        if ($YminusX === null) {
            $YminusX = new ParagonIE_Sodium_Core_Curve25519_Fe();
        }
        $this->YminusX = $YminusX;
        if ($Z === null) {
            $Z = new ParagonIE_Sodium_Core_Curve25519_Fe();
        }
        $this->Z = $Z;
        if ($T2d === null) {
            $T2d = new ParagonIE_Sodium_Core_Curve25519_Fe();
        }
        $this->T2d = $T2d;
    }
}
