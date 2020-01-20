<?php

namespace PragmaRX\Google2FA\Support;

use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer as BaconQrCodeWriter;
use PragmaRX\Google2FA\Exceptions\InsecureCallException;

trait QRCode
{
    /**
     * Sending your secret key to Google API is a security issue. Developer must explicitly allow it.
     */
    protected $allowInsecureCallToGoogleApis = false;

    /**
     * Creates a Google QR code url.
     *
     * @param string $company
     * @param string $holder
     * @param string $secret
     * @param int    $size
     *
     * @throws InsecureCallException
     *
     * @return string
     */
    public function getQRCodeGoogleUrl($company, $holder, $secret, $size = 200)
    {
        if (!$this->allowInsecureCallToGoogleApis) {
            throw new InsecureCallException('It\'s not secure to send secret keys to Google Apis, you have to explicitly allow it by calling $google2fa->setAllowInsecureCallToGoogleApis(true).');
        }

        $url = $this->getQRCodeUrl($company, $holder, $secret);

        return Url::generateGoogleQRCodeUrl('https://chart.googleapis.com/', 'chart', 'chs='.$size.'x'.$size.'&chld=M|0&cht=qr&chl=', $url);
    }

    /**
     * Generates a QR code data url to display inline.
     *
     * @param string $company
     * @param string $holder
     * @param string $secret
     * @param int    $size
     * @param string $encoding Default to UTF-8
     *
     * @return string
     */
    public function getQRCodeInline($company, $holder, $secret, $size = 200, $encoding = 'utf-8')
    {
        $url = $this->getQRCodeUrl($company, $holder, $secret);

        $renderer = new Png();
        $renderer->setWidth($size);
        $renderer->setHeight($size);

        $bacon = new BaconQrCodeWriter($renderer);
        $data = $bacon->writeString($url, $encoding);

        return 'data:image/png;base64,'.base64_encode($data);
    }

    /**
     * Creates a QR code url.
     *
     * @param $company
     * @param $holder
     * @param $secret
     *
     * @return string
     */
    public function getQRCodeUrl($company, $holder, $secret)
    {
        return 'otpauth://totp/'.rawurlencode($company).':'.rawurlencode($holder).'?secret='.$secret.'&issuer='.rawurlencode($company).'';
    }

    /**
     * AllowInsecureCallToGoogleApis setter.
     *
     * @param mixed $allowInsecureCallToGoogleApis
     *
     * @return QRCode
     */
    public function setAllowInsecureCallToGoogleApis($allowInsecureCallToGoogleApis)
    {
        $this->allowInsecureCallToGoogleApis = $allowInsecureCallToGoogleApis;

        return $this;
    }
}
