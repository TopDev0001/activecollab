<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\Exception;

use Angie\Error;

/**
 * @package Angie\Authentication\Exception
 */
class ResetPasswordException extends Error
{
    const UNKNOWN_ERROR = 0;
    const USER_NOT_ACTIVE = 1;
    const INVALID_CODE = 2;
    const INVALID_PASSWORD = 3;

    /**
     * @param int             $reason
     * @param string|null     $message
     * @param \Exception|null $previous
     */
    public function __construct($reason = self::UNKNOWN_ERROR, $message = null, $previous = null)
    {
        if (empty($message)) {
            switch ($reason) {
                case self::USER_NOT_ACTIVE:
                    $message = lang('Account not active');
                    break;
                case self::INVALID_CODE:
                    $message = lang('Invalid reset code');
                    break;
                case self::INVALID_PASSWORD:
                    $message = lang('Invalid password');
                    break;
                default:
                    $message = lang('Unknown error');
            }
        }

        parent::__construct($message, null, $previous);
    }
}
