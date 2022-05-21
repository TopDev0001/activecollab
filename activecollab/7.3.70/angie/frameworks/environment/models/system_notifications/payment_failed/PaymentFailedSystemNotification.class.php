<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Payment Failed system notification.
 *
 * @package angie.environment.payment_failed
 * @subpackage models
 */
class PaymentFailedSystemNotification extends SystemNotification
{
    /**
     * Return notification title.
     *
     * @return mixed
     */
    public function getTitle()
    {
        return lang('Payment failed');
    }

    /**
     * Return notification body.
     *
     * @return mixed
     */
    public function getBody()
    {
        $days_until_suspension = PaymentFailedSystemNotifications::getAccountDaysUntilSuspension();

        if ($days_until_suspension === 0) {
            return lang('Your account will be suspended today');
        } elseif ($days_until_suspension === 1) {
            return lang('Your account will be suspended tomorrow');
        }

        return lang("Your account will be suspended in {$days_until_suspension} days");
    }

    /**
     * Return notification action.
     *
     * @return mixed
     */
    public function getAction()
    {
        return lang('Go to Subscription page');
    }

    /**
     * Return notification url.
     *
     * @return mixed
     */
    public function getUrl()
    {
        return ROOT_URL . '/subscription';
    }

    /**
     * Return is permanent.
     *
     * @return mixed
     */
    public function isPermanent()
    {
        return true;
    }
}
