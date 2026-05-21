<?php
declare(strict_types=1);

if (class_exists('ParagonIE_Sodium_File', false)) {
    return;
}
/**
 * Class ParagonIE_Sodium_File
 */
class ParagonIE_Sodium_File extends ParagonIE_Sodium_Core_Util
{
    /* PHP's default buffer size is 8192 for fread()/fwrite(). */
    const BUFFER_SIZE = 8192;

    /**
     * Box a file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_box(), but produces
     * the same result.
     *
     * @param string $inputFile  Absolute path to a file on the filesystem
     * @param string $outputFile Absolute path to a file on the filesystem
     * @param string $nonce      Number to be used only once
     * @param string $keyPair    ECDH secret key and ECDH public key concatenated
     *
     * @return bool
     * @throws SodiumException
     * @throws TypeError
     */
    public static function box(
        string $inputFile,
        string $outputFile,
        string $nonce,
        #[SensitiveParameter]
        string $keyPair
    ): bool {
        if (self::strlen($nonce) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_NONCEBYTES) {
            throw new TypeError('Argument 3 must be CRYPTO_BOX_NONCEBYTES bytes');
        }
        if (self::strlen($keyPair) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_KEYPAIRBYTES) {
            throw new TypeError('Argument 4 must be CRYPTO_BOX_KEYPAIRBYTES bytes');
        }

        $size = @filesize($inputFile);
        if (!is_int($size)) {
            throw new SodiumException('Could not obtain the file size');
        }

        $ifp = fopen($inputFile, 'rb');
        if (!is_resource($ifp)) {
            throw new SodiumException('Could not open input file for reading');
        }

        $ofp = @fopen($outputFile, 'wb');
        if (!is_resource($ofp)) {
            fclose($ifp);
            throw new SodiumException('Could not open output file for writing');
        }

        $res = self::box_encrypt($ifp, $ofp, $size, $nonce, $keyPair);
        fclose($ifp);
        fclose($ofp);
        return $res;
    }

    /**
     * Open a boxed file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_box_open(), but produces
     * the same result.
     *
     * Warning: Does not protect against TOCTOU attacks. You should
     * just load the file into memory and use crypto_box_open() if
     * you are worried about those.
     *
     * @param string $inputFile
     * @param string $outputFile
     * @param string $nonce
     * @param string $keypair
     * @return bool
     * @throws SodiumException
     * @throws TypeError
     */
    public static function box_open(
        string $inputFile,
        string $outputFile,
        string $nonce,
        #[SensitiveParameter]
        string $keypair
    ): bool {
        /* Input validation: */
        if (self::strlen($nonce) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_NONCEBYTES) {
            throw new TypeError('Argument 4 must be CRYPTO_BOX_NONCEBYTES bytes');
        }
        if (self::strlen($keypair) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_KEYPAIRBYTES) {
            throw new TypeError('Argument 4 must be CRYPTO_BOX_KEYPAIRBYTES bytes');
        }

        if (!file_exists($inputFile)) {
            throw new SodiumException('Input file does not exist');
        }
        $size = filesize($inputFile);
        if (!is_int($size)) {
            throw new SodiumException('Could not obtain the file size');
        }

        $ifp = fopen($inputFile, 'rb');
        if (!is_resource($ifp)) {
            throw new SodiumException('Could not open input file for reading');
        }

        $ofp = @fopen($outputFile, 'wb');
        if (!is_resource($ofp)) {
            fclose($ifp);
            throw new SodiumException('Could not open output file for writing');
        }

        $res = self::box_decrypt($ifp, $ofp, $size, $nonce, $keypair);
        fclose($ifp);
        fclose($ofp);
        try {
            ParagonIE_Sodium_Compat::memzero($nonce);
            ParagonIE_Sodium_Compat::memzero($ephKeypair);
        } catch (SodiumException) {
            if (isset($ephKeypair)) {
                unset($ephKeypair);
            }
        }
        return $res;
    }

    /**
     * Seal a file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_box_seal(), but produces
     * the same result.
     *
     * @param string $inputFile  Absolute path to a file on the filesystem
     * @param string $outputFile Absolute path to a file on the filesystem
     * @param string $publicKey  ECDH public key
     *
     * @return bool
     * @throws SodiumException
     * @throws TypeError
     */
    public static function box_seal(
        string $inputFile,
        string $outputFile,
        string $publicKey
    ): bool {
        /* Input validation: */
        if (self::strlen($publicKey) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_PUBLICKEYBYTES) {
            throw new TypeError('Argument 3 must be CRYPTO_BOX_PUBLICKEYBYTES bytes');
        }

        if (!file_exists($inputFile)) {
            throw new SodiumException('Input file does not exist');
        }
        $size = filesize($inputFile);
        if (!is_int($size)) {
            throw new SodiumException('Could not obtain the file size');
        }

        $ifp = fopen($inputFile, 'rb');
        if (!is_resource($ifp)) {
            throw new SodiumException('Could not open input file for reading');
        }

        $ofp = @fopen($outputFile, 'wb');
        if (!is_resource($ofp)) {
            fclose($ifp);
            throw new SodiumException('Could not open output file for writing');
        }

        $ephKeypair = ParagonIE_Sodium_Compat::crypto_box_keypair();
        $msgKeypair = ParagonIE_Sodium_Compat::crypto_box_keypair_from_secretkey_and_publickey(
            ParagonIE_Sodium_Compat::crypto_box_secretkey($ephKeypair),
            $publicKey
        );
        $ephemeralPK = ParagonIE_Sodium_Compat::crypto_box_publickey($ephKeypair);
        $nonce = ParagonIE_Sodium_Compat::crypto_generichash(
            $ephemeralPK . $publicKey,
            '',
            24
        );

        $firstWrite = fwrite(
            $ofp,
            $ephemeralPK,
            ParagonIE_Sodium_Compat::CRYPTO_BOX_PUBLICKEYBYTES
        );
        if (!is_int($firstWrite)) {
            fclose($ifp);
            fclose($ofp);
            ParagonIE_Sodium_Compat::memzero($ephKeypair);
            throw new SodiumException('Could not write to output file');
        }
        if ($firstWrite !== ParagonIE_Sodium_Compat::CRYPTO_BOX_PUBLICKEYBYTES) {
            ParagonIE_Sodium_Compat::memzero($ephKeypair);
            fclose($ifp);
            fclose($ofp);
            throw new SodiumException('Error writing public key to output file');
        }

        $res = self::box_encrypt($ifp, $ofp, $size, $nonce, $msgKeypair);
        fclose($ifp);
        fclose($ofp);
        try {
            ParagonIE_Sodium_Compat::memzero($nonce);
            ParagonIE_Sodium_Compat::memzero($ephKeypair);
        } catch (SodiumException) {
            /** @psalm-suppress PossiblyUndefinedVariable */
            unset($ephKeypair);
        }
        return $res;
    }

    /**
     * Open a sealed file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_box_seal_open(), but produces
     * the same result.
     *
     * Warning: Does not protect against TOCTOU attacks. You should
     * just load the file into memory and use crypto_box_seal_open() if
     * you are worried about those.
     *
     * @param string $inputFile
     * @param string $outputFile
     * @param string $ecdhKeypair
     * @return bool
     * @throws SodiumException
     * @throws TypeError
     */
    public static function box_seal_open(
        string $inputFile,
        string $outputFile,
        #[SensitiveParameter]
        string $ecdhKeypair
    ): bool {
        /* Input validation: */
        if (self::strlen($ecdhKeypair) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_KEYPAIRBYTES) {
            throw new TypeError('Argument 3 must be CRYPTO_BOX_KEYPAIRBYTES bytes');
        }

        $publicKey = ParagonIE_Sodium_Compat::crypto_box_publickey($ecdhKeypair);

        if (!file_exists($inputFile)) {
            throw new SodiumException('Input file does not exist');
        }
        $size = filesize($inputFile);
        if (!is_int($size)) {
            throw new SodiumException('Could not obtain the file size');
        }

        $ifp = fopen($inputFile, 'rb');
        if (!is_resource($ifp)) {
            throw new SodiumException('Could not open input file for reading');
        }

        $ofp = @fopen($outputFile, 'wb');
        if (!is_resource($ofp)) {
            fclose($ifp);
            throw new SodiumException('Could not open output file for writing');
        }

        $ephemeralPK = fread($ifp, ParagonIE_Sodium_Compat::CRYPTO_BOX_PUBLICKEYBYTES);
        if (!is_string($ephemeralPK)) {
            throw new SodiumException('Could not read input file');
        }
        if (self::strlen($ephemeralPK) !== ParagonIE_Sodium_Compat::CRYPTO_BOX_PUBLICKEYBYTES) {
            fclose($ifp);
            fclose($ofp);
            throw new SodiumException('Could not read public key from sealed file');
        }

        $nonce = ParagonIE_Sodium_Compat::crypto_generichash(
            $ephemeralPK . $publicKey,
            '',
            24
        );
        $msgKeypair = ParagonIE_Sodium_Compat::crypto_box_keypair_from_secretkey_and_publickey(
            ParagonIE_Sodium_Compat::crypto_box_secretkey($ecdhKeypair),
            $ephemeralPK
        );

        $res = self::box_decrypt($ifp, $ofp, $size, $nonce, $msgKeypair);
        fclose($ifp);
        fclose($ofp);
        try {
            ParagonIE_Sodium_Compat::memzero($nonce);
            ParagonIE_Sodium_Compat::memzero($ephKeypair);
        } catch (SodiumException) {
            if (isset($ephKeypair)) {
                unset($ephKeypair);
            }
        }
        return $res;
    }

    /**
     * Calculate the BLAKE2b hash of a file.
     *
     * @param string      $filePath     Absolute path to a file on the filesystem
     * @param string|null $key          BLAKE2b key
     * @param int         $outputLength Length of hash output
     *
     * @return string                   BLAKE2b hash
     * @throws SodiumException
     * @throws TypeError
     */
    public static function generichash(
        string $filePath,
        #[SensitiveParameter]
        ?string $key = '',
        int $outputLength = 32
    ): string {
        if (is_null($key)) {
            $key = '';
        }

        /* Input validation: */
        if (!empty($key)) {
            if (self::strlen($key) < ParagonIE_Sodium_Compat::CRYPTO_GENERICHASH_KEYBYTES_MIN) {
                throw new TypeError('Argument 2 must be at least CRYPTO_GENERICHASH_KEYBYTES_MIN bytes');
            }
            if (self::strlen($key) > ParagonIE_Sodium_Compat::CRYPTO_GENERICHASH_KEYBYTES_MAX) {
                throw new TypeError('Argument 2 must be at most CRYPTO_GENERICHASH_KEYBYTES_MAX bytes');
            }
        }
        if ($outputLength < ParagonIE_Sodium_Compat::CRYPTO_GENERICHASH_BYTES_MIN) {
            throw new SodiumException('Argument 3 must be at least CRYPTO_GENERICHASH_BYTES_MIN');
        }
        if ($outputLength > ParagonIE_Sodium_Compat::CRYPTO_GENERICHASH_BYTES_MAX) {
            throw new SodiumException('Argument 3 must be at least CRYPTO_GENERICHASH_BYTES_MAX');
        }

        if (!file_exists($filePath)) {
            throw new SodiumException('File does not exist');
        }
        $size = filesize($filePath);
        if (!is_int($size)) {
            throw new SodiumException('Could not obtain the file size');
        }

        $fp = fopen($filePath, 'rb');
        if (!is_resource($fp)) {
            throw new SodiumException('Could not open input file for reading');
        }
        $ctx = ParagonIE_Sodium_Compat::crypto_generichash_init($key, $outputLength);
        while ($size > 0) {
            $blockSize = min($size, 64);
            $read = fread($fp, $blockSize);
            if (!is_string($read)) {
                throw new SodiumException('Could not read input file');
            }
            ParagonIE_Sodium_Compat::crypto_generichash_update($ctx, $read);
            $size -= $blockSize;
        }

        fclose($fp);
        return ParagonIE_Sodium_Compat::crypto_generichash_final($ctx, $outputLength);
    }

    /**
     * Encrypt a file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_secretbox(), but produces
     * the same result.
     *
     * @param string $inputFile  Absolute path to a file on the filesystem
     * @param string $outputFile Absolute path to a file on the filesystem
     * @param string $nonce      Number to be used only once
     * @param string $key        Encryption key
     *
     * @return bool
     * @throws SodiumException
     * @throws TypeError
     */
    public static function secretbox(
        string $inputFile,
        string $outputFile,
        string $nonce,
        #[SensitiveParameter]
        string $key
    ): bool {
        /* Input validation: */
        if (self::strlen($nonce) !== ParagonIE_Sodium_Compat::CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new TypeError('Argument 3 must be CRYPTO_SECRETBOX_NONCEBYTES bytes');
        }
        if (self::strlen($key) !== ParagonIE_Sodium_Compat::CRYPTO_SECRETBOX_KEYBYTES) {
            throw new TypeError('Argument 4 must be CRYPTO_SECRETBOX_KEYBYTES bytes');
        }

        if (!file_exists($inputFile)) {
            throw new SodiumException('Input file does not exist');
        }
        $size = filesize($inputFile);
        if (!is_int($size)) {
            throw new SodiumException('Could not obtain the file size');
        }

        $ifp = fopen($inputFile, 'rb');
        if (!is_resource($ifp)) {
            throw new SodiumException('Could not open input file for reading');
        }

        $ofp = @fopen($outputFile, 'wb');
        if (!is_resource($ofp)) {
            fclose($ifp);
            throw new SodiumException('Could not open output file for writing');
        }

        $res = self::secretbox_encrypt($ifp, $ofp, $size, $nonce, $key);
        fclose($ifp);
        fclose($ofp);
        return $res;
    }

    /**
     * Seal a file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_secretbox_open(), but produces
     * the same result.
     *
     * Warning: Does not protect against TOCTOU attacks. You should
     * just load the file into memory and use crypto_secretbox_open() if
     * you are worried about those.
     *
     * @param string $inputFile
     * @param string $outputFile
     * @param string $nonce
     * @param string $key
     * @return bool
     * @throws SodiumException
     * @throws TypeError
     */
    public static function secretbox_open(
        string $inputFile,
        string $outputFile,
        string $nonce,
        #[SensitiveParameter]
        string $key
    ): bool {
        /* Input validation: */
        if (self::strlen($nonce) !== ParagonIE_Sodium_Compat::CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new TypeError('Argument 3 must be CRYPTO_SECRETBOX_NONCEBYTES bytes');
        }
        if (self::strlen($key) !== ParagonIE_Sodium_Compat::CRYPTO_SECRETBOX_KEYBYTES) {
            throw new TypeError('Argument 4 must be CRYPTO_SECRETBOX_KEYBYTES bytes');
        }

        if (!file_exists($inputFile)) {
            throw new SodiumException('Input file does not exist');
        }
        $size = filesize($inputFile);
        if (!is_int($size)) {
            throw new SodiumException('Could not obtain the file size');
        }

        $ifp = fopen($inputFile, 'rb');
        if (!is_resource($ifp)) {
            throw new SodiumException('Could not open input file for reading');
        }

        $ofp = @fopen($outputFile, 'wb');
        if (!is_resource($ofp)) {
            fclose($ifp);
            throw new SodiumException('Could not open output file for writing');
        }

        $res = self::secretbox_decrypt($ifp, $ofp, $size, $nonce, $key);
        fclose($ifp);
        fclose($ofp);
        try {
            ParagonIE_Sodium_Compat::memzero($key);
        } catch (SodiumException) {
            /** @psalm-suppress PossiblyUndefinedVariable */
            unset($key);
        }
        return $res;
    }

    /**
     * Sign a file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_sign_detached(), but produces
     * the same result.
     *
     * @param string $filePath  Absolute path to a file on the filesystem
     * @param string $secretKey Secret signing key
     *
     * @return string           Ed25519 signature
     * @throws SodiumException
     * @throws TypeError
     */
    public static function sign(
        string $filePath,
        #[SensitiveParameter]
        string $secretKey
    ): string {
        /* Input validation: */
        if (self::strlen($secretKey) !== ParagonIE_Sodium_Compat::CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new TypeError('Argument 2 must be CRYPTO_SIGN_SECRETKEYBYTES bytes');
        }

        if (!file_exists($filePath)) {
            throw new SodiumException('File does not exist');
        }
        $size = filesize($filePath);
        if (!is_int($size)) {
            throw new SodiumException('Could not obtain the file size');
        }

        $fp = fopen($filePath, 'rb');
        if (!is_resource($fp)) {
            throw new SodiumException('Could not open input file for reading');
        }

        /** @var string $az */
        $az = hash('sha512', self::substr($secretKey, 0, 32), true);

        $az[0] = self::intToChr(self::chrToInt($az[0]) & 248);
        $az[31] = self::intToChr((self::chrToInt($az[31]) & 63) | 64);

        $hs = hash_init('sha512');
        hash_update($hs, self::substr($az, 32, 32));
        $hs = self::updateHashWithFile($hs, $fp, $size);

        /** @var string $nonceHash */
        $nonceHash = hash_final($hs, true);

        /** @var string $pk */
        $pk = self::substr($secretKey, 32, 32);

        /** @var string $nonce */
        $nonce = ParagonIE_Sodium_Core_Ed25519::sc_reduce($nonceHash) . self::substr($nonceHash, 32);

        /** @var string $sig */
        $sig = ParagonIE_Sodium_Core_Ed25519::ge_p3_tobytes(
            ParagonIE_Sodium_Core_Ed25519::ge_scalarmult_base($nonce)
        );

        $hs = hash_init('sha512');
        hash_update($hs, self::substr($sig, 0, 32));
        hash_update($hs, self::substr($pk, 0, 32));
        $hs = self::updateHashWithFile($hs, $fp, $size);

        /** @var string $hramHash */
        $hramHash = hash_final($hs, true);

        /** @var string $hram */
        $hram = ParagonIE_Sodium_Core_Ed25519::sc_reduce($hramHash);

        /** @var string $sigAfter */
        $sigAfter = ParagonIE_Sodium_Core_Ed25519::sc_muladd($hram, $az, $nonce);

        /** @var string $sig */
        $sig = self::substr($sig, 0, 32) . self::substr($sigAfter, 0, 32);

        try {
            ParagonIE_Sodium_Compat::memzero($az);
        } catch (SodiumException) {
            $az = null;
        }
        fclose($fp);
        return $sig;
    }

    /**
     * Verify a file (rather than a string). Uses less memory than
     * ParagonIE_Sodium_Compat::crypto_sign_verify_detached(), but
     * produces the same result.
     *
     * @param string $sig       Ed25519 signature
     * @param string $filePath  Absolute path to a file on the filesystem
     * @param string $publicKey Signing public key
     *
     * @return bool
     * @throws SodiumException
     * @throws TypeError
     * @throws Exception
     */
    public static function verify(string $sig, string $filePath, string $publicKey): bool
    {
        /* Input validation: */
        if (self::strlen($sig) !== ParagonIE_Sodium_Compat::CRYPTO_SIGN_BYTES) {
            throw new TypeError('Argument 1 must be CRYPTO_SIGN_BYTES bytes');
        }
        if (self::strlen($publicKey) !== ParagonIE_Sodium_Compat::CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new TypeError('Argument 3 must be CRYPTO_SIGN_PUBLICKEYBYTES bytes');
        }
        if (self::strlen($sig) < 64) {
            throw new SodiumException('Signature is too short');
        }


        /* Security checks */
        if (
            (ParagonIE_Sodium_Core_Ed25519::chrToInt($sig[63]) & 240)
                &&
            ParagonIE_Sodium_Core_Ed25519::check_S_lt_L(self::substr($sig, 32, 32))
        ) {
            throw new SodiumException('S < L - Invalid signature');
        }
        if (ParagonIE_Sodium_Core_Ed25519::small_order($sig)) {
            throw new SodiumException('Signature is on too small of an order');
        }
        if ((self::chrToInt($sig[63]) & 224) !== 0) {
            throw new SodiumException('Invalid signature');
        }
        $d = 0;
        for ($i = 0; $i < 32; ++$i) {
            $d |= self::chrToInt($publicKey[$i]);
        }
        if ($d === 0) {
            throw new SodiumException('All zero public key');
        }

        if (!file_exists($filePath)) {
            throw new SodiumException('File does not exist');
        }
        $size = filesize($filePath);
        if (!is_int($size)) {
            throw new SodiumException('Could not obtain the file size');
        }

        $fp = fopen($filePath, 'rb');
        if (!is_resource($fp)) {
            throw new SodiumException('Could not open input file for reading');
        }

        /* The original value of ParagonIE_Sodium_Compat::$fastMult */
        $orig = ParagonIE_Sodium_Compat::$fastMult;

        // Set ParagonIE_Sodium_Compat::$fastMult to true to speed up verification.
        ParagonIE_Sodium_Compat::$fastMult = true;

        $A = ParagonIE_Sodium_Core_Ed25519::ge_frombytes_negate_vartime($publicKey);
        if (ParagonIE_Sodium_Core_Ed25519::small_order($publicKey)) {
            throw new SodiumException('Public key has small order');
        }
        if (!ParagonIE_Sodium_Core_Ed25519::is_on_main_subgroup($A)) {
            throw new SodiumException('Public key is not on main subgroup');
        }

        $hs = hash_init('sha512');
        hash_update($hs, self::substr($sig, 0, 32));
        hash_update($hs, self::substr($publicKey, 0, 32));

        $hs = self::updateHashWithFile($hs, $fp, $size);
        $hDigest = hash_final($hs, true);
        $h = ParagonIE_Sodium_Core_Ed25519::sc_reduce($hDigest) . self::substr($hDigest, 32);
        $R = ParagonIE_Sodium_Core_Ed25519::ge_double_scalarmult_vartime(
            $h,
            $A,
            self::substr($sig, 32)
        );

        /** @var string $rcheck */
        $rcheck = ParagonIE_Sodium_Core_Ed25519::ge_tobytes($R);

        // Close the file handle
        fclose($fp);

        // Reset ParagonIE_Sodium_Compat::$fastMult to what it was before.
        ParagonIE_Sodium_Compat::$fastMult = $orig;
        return self::verify_32($rcheck, self::substr($sig, 0, 32));
    }

    /**
     * @param resource $ifp
     * @param resource $ofp
     * @param int      $mlen
     * @param string   $nonce
     * @param string   $boxKeypair
     * @return bool
     * @throws SodiumException
     * @throws TypeError
     */
    protected static function box_encrypt(
        $ifp,
        $ofp,
        int $mlen,
        string $nonce,
        #[SensitiveParameter]
        string $boxKeypair
    ): bool {
        return self::secretbox_encrypt(
            $ifp,
            $ofp,
            $mlen,
            $nonce,
            ParagonIE_Sodium_Crypto::box_beforenm(
                ParagonIE_Sodium_Crypto::box_secretkey($boxKeypair),
                ParagonIE_Sodium_Crypto::box_publickey($boxKeypair)
            )
        );
    }


    /**
     * @param resource $ifp
     * @param resource $ofp
     * @param int      $mlen
     * @param string   $nonce
     * @param string   $boxKeypair
     * @return bool
     * @throws SodiumException
     * @throws TypeError
     */
    protected static function box_decrypt(
        $ifp,
        $ofp,
        int $mlen,
        string $nonce,
        #[SensitiveParameter]
        string $boxKeypair
    ): bool {
        return self::secretbox_decrypt(
            $ifp,
            $ofp,
            $mlen,
            $nonce,
            ParagonIE_Sodium_Crypto::box_beforenm(
                ParagonIE_Sodium_Crypto::box_secretkey($boxKeypair),
                ParagonIE_Sodium_Crypto::box_publickey($boxKeypair)
            )
        );
    }

    /**
     * Encrypt a file
     *
     * @param resource $ifp
     * @param resource $ofp
     * @param int $mlen
     * @param string $nonce
     * @param string $key
     * @return bool
     * @throws SodiumException
     * @throws TypeError
     */
    protected static function secretbox_encrypt(
        $ifp,
        $ofp,
        int $mlen,
        string $nonce,
        #[SensitiveParameter]
        string $key
    ): bool {
        $plaintext = fread($ifp, 32);
        if (!is_string($plaintext)) {
            throw new SodiumException('Could not read input file');
        }
        $first32 = self::ftell($ifp);

        $subkey = ParagonIE_Sodium_Core_HSalsa20::hsalsa20($nonce, $key);
        $realNonce = ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8);
        $block0 = str_repeat("\x00", 32);

        /* Length of the plaintext message */
        $mlen0 = $mlen;
        if ($mlen0 > 64 - ParagonIE_Sodium_Crypto::secretbox_xsalsa20poly1305_ZEROBYTES) {
            $mlen0 = 64 - ParagonIE_Sodium_Crypto::secretbox_xsalsa20poly1305_ZEROBYTES;
        }
        $block0 .= ParagonIE_Sodium_Core_Util::substr($plaintext, 0, $mlen0);

        /** @var string $block0 */
        $block0 = ParagonIE_Sodium_Core_Salsa20::salsa20_xor(
            $block0,
            $realNonce,
            $subkey
        );

        $state = new ParagonIE_Sodium_Core_Poly1305_State(
            ParagonIE_Sodium_Core_Util::substr(
                $block0,
                0,
                ParagonIE_Sodium_Crypto::onetimeauth_poly1305_KEYBYTES
            )
        );

        // Pre-write 16 blank bytes for the Poly1305 tag
        $start = self::ftell($ofp);
        fwrite($ofp, str_repeat("\x00", 16));

        /** @var string $c */
        $cBlock = ParagonIE_Sodium_Core_Util::substr(
            $block0,
            ParagonIE_Sodium_Crypto::secretbox_xsalsa20poly1305_ZEROBYTES
        );
        $state->update($cBlock);
        fwrite($ofp, $cBlock);
        $mlen -= 32;

        $iter = 1;
        $incr = self::BUFFER_SIZE >> 6;
        /*
         * Set the cursor to the end of the first half-block. All future bytes will
         * generated from salsa20_xor_ic, starting from 1 (second block).
         */
        fseek($ifp, $first32);

        while ($mlen > 0) {
            $blockSize = min($mlen, self::BUFFER_SIZE);
            $plaintext = fread($ifp, $blockSize);
            if (!is_string($plaintext)) {
                throw new SodiumException('Could not read input file');
            }
            $cBlock = ParagonIE_Sodium_Core_Salsa20::salsa20_xor_ic(
                $plaintext,
                $realNonce,
                $iter,
                $subkey
            );
            fwrite($ofp, $cBlock, $blockSize);
            $state->update($cBlock);

            $mlen -= $blockSize;
            $iter += $incr;
        }
        try {
            ParagonIE_Sodium_Compat::memzero($block0);
            ParagonIE_Sodium_Compat::memzero($subkey);
        } catch (SodiumException) {
            $block0 = null;
            $subkey = null;
        }
        $end = self::ftell($ofp);

        /*
         * Write the Poly1305 authentication tag that provides integrity
         * over the ciphertext (encrypt-then-MAC)
         */
        fseek($ofp, $start);
        fwrite($ofp, $state->finish(), ParagonIE_Sodium_Compat::CRYPTO_SECRETBOX_MACBYTES);
        fseek($ofp, $end);
        unset($state);

        return true;
    }

    /**
     * Decrypt a file
     *
     * @param resource $ifp
     * @param resource $ofp
     * @param int $mlen
     * @param string $nonce
     * @param string $key
     * @return bool
     * @throws SodiumException
     * @throws TypeError
     */
    protected static function secretbox_decrypt(
        $ifp,
        $ofp,
        int $mlen,
        string $nonce,
        #[SensitiveParameter]
        string $key
    ): bool {
        $tag = fread($ifp, 16);
        if (!is_string($tag)) {
            throw new SodiumException('Could not read input file');
        }

        $subkey = ParagonIE_Sodium_Core_HSalsa20::hsalsa20($nonce, $key);
        $realNonce = ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8);
        $block0 = ParagonIE_Sodium_Core_Salsa20::salsa20(
            64,
            ParagonIE_Sodium_Core_Util::substr($nonce, 16, 8),
            $subkey
        );

        /* Verify the Poly1305 MAC -before- attempting to decrypt! */
        $state = new ParagonIE_Sodium_Core_Poly1305_State(self::substr($block0, 0, 32));
        if (!self::onetimeauth_verify($state, $ifp, $tag, $mlen)) {
            throw new SodiumException('Invalid MAC');
        }

        /*
         * Set the cursor to the end of the first half-block. All future bytes will
         * generated from salsa20_xor_ic, starting from 1 (second block).
         */
        $first32 = fread($ifp, 32);
        if (!is_string($first32)) {
            throw new SodiumException('Could not read input file');
        }
        $first32len = self::strlen($first32);
        fwrite(
            $ofp,
            self::xorStrings(
                self::substr($block0, 32, $first32len),
                self::substr($first32, 0, $first32len)
            )
        );
        $mlen -= 32;
        $iter = 1;
        $incr = self::BUFFER_SIZE >> 6;

        /* Decrypts ciphertext, writes to output file. */
        while ($mlen > 0) {
            $blockSize = min($mlen, self::BUFFER_SIZE);
            $ciphertext = fread($ifp, $blockSize);
            if (!is_string($ciphertext)) {
                throw new SodiumException('Could not read input file');
            }
            $pBlock = ParagonIE_Sodium_Core_Salsa20::salsa20_xor_ic(
                $ciphertext,
                $realNonce,
                $iter,
                $subkey
            );
            fwrite($ofp, $pBlock, $blockSize);
            $mlen -= $blockSize;
            $iter += $incr;
        }
        return true;
    }

    /**
     * @param ParagonIE_Sodium_Core_Poly1305_State $state
     * @param resource $ifp
     * @param string $tag
     * @param int $mlen
     * @return bool
     * @throws SodiumException
     * @throws TypeError
     */
    protected static function onetimeauth_verify(
        ParagonIE_Sodium_Core_Poly1305_State $state,
        $ifp,
        string $tag = '',
        int $mlen = 0
    ): bool {
        $pos = self::ftell($ifp);
        while ($mlen > 0) {
            $blockSize = min($mlen, self::BUFFER_SIZE);
            $ciphertext = fread($ifp, $blockSize);
            if (!is_string($ciphertext)) {
                throw new SodiumException('Could not read input file');
            }
            $state->update($ciphertext);
            $mlen -= $blockSize;
        }
        $res = ParagonIE_Sodium_Core_Util::verify_16($tag, $state->finish());

        fseek($ifp, $pos);
        return $res;
    }

    /**
     * Update a hash context with the contents of a file, without
     * loading the entire file into memory.
     *
     * @param HashContext $hash
     * @param resource $fp
     * @param int $size
     * @return HashContext Resource on PHP < 7.2, HashContext object on PHP >= 7.2
     * @throws SodiumException
     * @throws TypeError
     */
    public static function updateHashWithFile(HashContext $hash, $fp, int $size = 0): HashContext
    {
        /**
         * @psalm-suppress DocblockTypeContradiction
         * We can't statically type resources.
         */
        if (!is_resource($fp)) {
            throw new TypeError('Argument 2 must be a resource, ' . gettype($fp) . ' given.');
        }
        $originalPosition = self::ftell($fp);

        // Move file pointer to beginning of file
        fseek($fp, 0);
        for ($i = 0; $i < $size; $i += self::BUFFER_SIZE) {
            /** @var string|bool $message */
            $message = fread(
                $fp,
                ($size - $i) > self::BUFFER_SIZE
                    ? $size - $i
                    : self::BUFFER_SIZE
            );
            if (!is_string($message)) {
                throw new SodiumException('Unexpected error reading from file.');
            }
            hash_update($hash, $message);
        }
        // Reset file pointer's position
        fseek($fp, $originalPosition);
        return $hash;
    }

    /**
     * @param resource $resource
     * @return int
     * @throws SodiumException
     */
    private static function ftell($resource): int
    {
        $return = ftell($resource);
        if (!is_int($return)) {
            throw new SodiumException('ftell() returned false');
        }
        return $return;
    }
}
