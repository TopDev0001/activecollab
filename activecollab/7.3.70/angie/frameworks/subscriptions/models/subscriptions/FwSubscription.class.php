<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Framework level subscription implementation.
 *
 * @package angie.frameworks.subscriptions
 * @subpackage models
 */
abstract class FwSubscription extends BaseSubscription
{
    /**
     * Get subscribed user.
     *
     * @return IUser
     */
    public function getUser()
    {
        if ($this->getUserId()) {
            return DataObjectPool::get('User', $this->getUserId());
        } else {
            return new AnonymousUser($this->getUserName(), $this->getUserEmail());
        }
    }
}
