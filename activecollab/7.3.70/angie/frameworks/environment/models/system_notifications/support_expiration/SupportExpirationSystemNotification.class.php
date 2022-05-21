<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Support Expiration system notification.
 *
 * @package angie.framework.environment
 * @subpackage models
 */
class SupportExpirationSystemNotification extends SystemNotification
{
    /**
     * Return notification title.
     *
     * @return mixed
     */
    public function getTitle()
    {
        return lang('Support expiration!');
    }

    /**
     * Return notification body.
     *
     * @return mixed
     */
    public function getBody()
    {
        $expires_on = DateValue::makeFromTimestamp(AngieApplication::autoUpgrade()->getSupportSubscriptionExpiresOn());

        if (empty($expires_on)) {
            $expires_on = new DateValue();
        }

        $today = new DateValue();
        $days_between = $today->daysBetween($expires_on);

        if ($today->getTimestamp() > $expires_on->getTimestamp()) {
            if ($days_between > 1) {
                $body = lang('Support and upgrades for your ActiveCollab expired :days days ago', ['days' => $days_between]) . '.';
            } else {
                $body = lang('Support and upgrades for your ActiveCollab expired one day ago') . '.';
            }
        } else {
            if ($days_between > 1) {
                $body = lang('Support and upgrades for your ActiveCollab are about to expire in :days days', ['days' => $days_between]) . '.';
            } else {
                $body = lang('Support and upgrades for your ActiveCollab are about to expire in one day') . '.';
            }
        }

        return $body;
    }

    /**
     * Return notification action.
     *
     * @return string
     */
    public function getAction()
    {
        return lang('Renew');
    }

    /**
     * Return notification url.
     *
     * @return string
     */
    public function getUrl()
    {
        return AngieApplication::autoUpgrade()->getRenewSupportUrl();
    }

    /**
     * Return is permanent.
     *
     * @return bool
     */
    public function isPermanent()
    {
        return false;
    }
}
