<?php
/*
 * (c) Jerry Anselmi <jerry.anselmi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyTools\FixingPermissionsBundle\Exception;

/**
 * AccessDeniedException is thrown when the account has not the required role.
 *
 * @author Jerry Anselmi <jerry.anselmi@gmail.com>
 */
class InvalidPasswordException extends \RuntimeException
{
    protected $message = "The password is invalid.";
}
