<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class SubscriptionCancelledSystemNotification extends SystemNotification
{
    public function isHandledInternally()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isPermanent()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return lang('Subscription cancelled');
    }

    /**
     * @return string
     */
    public function getBody()
    {
        $days_between = SubscriptionCancelledSystemNotifications::accountStatusExpiresInDays();

        if ($days_between === 0) {
            return lang('Your account is about to be retired today');
        } elseif ($days_between === 1) {
            return lang('Your account is about to be retired tomorrow');
        }

        return lang("Your account is about to be retired in {$days_between} days");
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return lang('Stop retirement');
    }

    public function getUrl()
    {
        return null;
    }
}
