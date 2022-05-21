<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\Exception;

use Angie\Error;
use ConfigOptions;
use Exception;

/**
 * @package Angie\Authentication\Exception
 */
class AuthenticationException extends Error
{
    const UNKNOWN_ERROR = 0;
    const USER_NOT_FOUND = 1;
    const USER_NOT_ACTIVE = 2;
    const INVALID_PASSWORD = 3;
    const IN_MAINTENANCE_MODE = 4;
    const FAILED_TO_ISSUE_TOKEN = 5;

    /**
     * Construct authentication error.
     *
     * @param int            $reason
     * @param string         $message
     * @param Exception|null $previous
     */
    public function __construct($reason = self::UNKNOWN_ERROR, $message = null, $previous = null)
    {
        if (empty($message)) {
            switch ($reason) {
                case self::USER_NOT_FOUND:
                    $message = lang('User account not found');
                    break;
                case self::USER_NOT_ACTIVE:
                    $message = lang('User account is no longer active');
                    break;
                case self::INVALID_PASSWORD:
                    $message = lang('Invalid password');
                    break;
                case self::IN_MAINTENANCE_MODE:
                    $message = ConfigOptions::getValue('maintenance_message');

                    if (empty($message)) {
                        $message = lang('System is in maintenance mode');
                    }

                    break;
                case self::FAILED_TO_ISSUE_TOKEN:
                    $message = lang('Failed to issue token');
                    break;
                default:
                    $message = lang('Unknown error. Please contact support for assistance');
            }
        }

        parent::__construct($message, ['reason' => $reason], $previous);
    }
}
