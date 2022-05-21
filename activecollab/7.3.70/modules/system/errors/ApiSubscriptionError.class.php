<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * API subscription error.
 *
 * @package angie.frameworks.authentication
 * @subpackage models
 */
class ApiSubscriptionError extends Error
{
    /**
     * Subscription error code.
     *
     * @var int
     */
    private $subscription_error_code;

    /**
     * Construct API subscription error.
     *
     * @param int            $code
     * @param string         $message
     * @param Exception|null $previous
     */
    public function __construct($code, $message = null, $previous = null)
    {
        switch ($code) {
            case ApiSubscriptions::ERROR_CLIENT_NOT_SET:
                $message = 'Client information missing';
                break;
            case ApiSubscriptions::ERROR_USER_DOES_NOT_EXIST:
                $message = 'User does not exist';
                break;
            case ApiSubscriptions::ERROR_INVALID_PASSWORD:
                $message = 'Invalid password';
                break;
            case ApiSubscriptions::ERROR_NOT_ALLOWED:
                $message = 'API subscriptions not allowed for this user';
                break;
            default:
                $code = ApiSubscriptions::ERROR_OPERATION_FAILED;
                $message = 'Operation failed';
        }

        $this->subscription_error_code = $code;

        parent::__construct($message, ['code' => $code], $previous);
    }

    /**
     * Return subscription error code.
     *
     * @return int
     */
    public function getSubscriptionErrorCode()
    {
        return $this->subscription_error_code;
    }
}
