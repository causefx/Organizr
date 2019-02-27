<?php

namespace Adldap\Models;

use Adldap\AdldapException;

/**
 * Class UserPasswordIncorrectException
 *
 * Thrown when a users password is being changed
 * and their current password given is incorrect.
 *
 * @package Adldap\Models
 */
class UserPasswordIncorrectException extends AdldapException
{
    //
}
