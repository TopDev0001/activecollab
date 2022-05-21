<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Free Trial system notification.
 *
 * @package angie.environment.free_trial
 * @subpackage models
 */
class FreeTrialSystemNotification extends SystemNotification
{
    /**
     * Return notification title.
     *
     * @return mixed
     */
    public function getTitle()
    {
        return lang('Free Trial Ends Soon');
    }

    /**
     * Return notification body.
     *
     * @return mixed
     */
    public function getBody()
    {
        $days_to_expiration = AngieApplication::accountSettings()->getAccountStatus()->getDaysToStatusExpiration();

        if ($days_to_expiration == 0) {
            $message = lang('Your free trial ends today.');
        } else {
            if ($days_to_expiration == 1) {
                $message = lang('Your free trial ends tomorrow.');
            } else {
                $message = lang('Your free trial ends in :days days.', ['days' => $days_to_expiration]);
            }
        }

        $message .= ' ' . lang('Make sure you buy ActiveCollab so your team can continue working.');

        return $message;
    }

    /**
     * Return notification action.
     *
     * @return mixed
     */
    public function getAction()
    {
        return lang('Buy Now!');
    }

    /**
     * Return notification url.
     *
     * @return mixed
     */
    public function getUrl()
    {
        return ROOT_URL . '/subscription/choose-plan';
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
