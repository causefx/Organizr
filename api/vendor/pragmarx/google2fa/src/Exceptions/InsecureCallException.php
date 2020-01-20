<?php

namespace PragmaRX\Google2FA\Exceptions;

use Exception;

class InsecureCallException extends Exception
{
    protected $message = 'It\'s not secure to send secret keys to Google Apis, you have to explicitly allow it by calling $google2fa->setAllowInsecureCallToGoogleApis(true).';
}
