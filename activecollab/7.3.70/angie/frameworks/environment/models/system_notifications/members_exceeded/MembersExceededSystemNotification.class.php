<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Members exceeded system notification.
 *
 * @package angie.environment
 * @subpackage models
 */
class MembersExceededSystemNotification extends SystemNotification
{
    /**
     * Return notification title.
     *
     * @return mixed
     */
    public function getTitle()
    {
        return lang('No user seats left!');
    }

    /**
     * Return notification body.
    }
     * @return mixed
     */
    public function getBody()
    {
        $max_members = AngieApplication::accountSettings()->getAccountLimitations()->getMaxMembers();

        return lang("You've invited more users than your subscription allows (:members max). Please switch to a larger plan so everyone can log in.", [
            'members' => !empty($max_members) ? $max_members : lang('unlimited'),
        ]);
    }

    /**
     * Return notification action.
     *
     * @return mixed
     */
    public function getAction()
    {
        return lang('Upgrade Plan');
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
        return false;
    }
}
