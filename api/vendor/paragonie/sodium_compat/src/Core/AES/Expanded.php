<?php
declare(strict_types=1);

if (class_exists('ParagonIE_Sodium_Core_AES_Expanded', false)) {
    return;
}

/**
 * @internal This should only be used by sodium_compat
 */
class ParagonIE_Sodium_Core_AES_Expanded extends ParagonIE_Sodium_Core_AES_KeySchedule
{}
