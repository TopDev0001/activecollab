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
class FirewallException extends Error
{
    const UNKNOWN_ERROR = 0;
    const USER_FAILED_LOGIN = 1;
    const TOO_MANY_ATTEMPTS = 2;
    const FIXED_RULE = 3;
    const INVALID_ADDRESS_FORMAT = 4;
    const NETWORK_ERROR = 5;
    const CONFIG_ERROR = 6;

    /**
     * Construct firewall error.
     *
     * @param int    $reason
     * @param string $message
     */
    public function __construct($reason = self::UNKNOWN_ERROR, $message = null)
    {
        if (empty($message)) {
            switch ($reason) {
                case self::USER_FAILED_LOGIN:
                    $message = 'This account is blocked by firewall due too many failed logins.';
                    break;
                case self::TOO_MANY_ATTEMPTS:
                    $message = 'This IP address is blocked by firewall due too many failed logins.';
                    break;
                case self::FIXED_RULE:
                    $message = 'This IP address is blocked by firewall.';
                    break;
                case self::INVALID_ADDRESS_FORMAT:
                    $message = 'This IP address is not valid.';
                    break;
                case self::NETWORK_ERROR:
                    $message = 'Firewall cannot be initialized because IP address cannot be recognized as valid IPv4 nor IPv6.';
                    break;
                case self::CONFIG_ERROR:
                    $message = 'Firewall config error.';
                    break;
                default:
                    $message = 'Unknown error. Please contact support for assistance';
            }
        }

        parent::__construct($message, ['reason' => $reason]);
    }
}
