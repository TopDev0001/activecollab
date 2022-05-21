<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Upgrade available system notification.
 *
 * @package angie.environment
 * @subpackage models
 */
class UpgradeAvailableSystemNotification extends SystemNotification
{
    /**
     * Return notification title.
     *
     * @return mixed
     */
    public function getTitle()
    {
        return lang('Update ready');
    }

    /**
     * Return notification body.
     *
     * @return mixed
     */
    public function getBody()
    {
        return lang(
            'ActiveCollab :version is ready. Follow the button below to proceed with the update.',
            [
                'version' => AngieApplication::autoUpgrade()->getLatestAvailableVersion(),
            ]
        );
    }

    /**
     * Return notification action.
     *
     * @return mixed
     */
    public function getAction()
    {
        return lang('Update now');
    }

    /**
     * Return notification url.
     *
     * @return string
     */
    public function getUrl()
    {
        return ROOT_URL . "/system-settings/updates?u={$this->getId()}"; // hardcoded frontend route, set param 'u', to immediately start update
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
