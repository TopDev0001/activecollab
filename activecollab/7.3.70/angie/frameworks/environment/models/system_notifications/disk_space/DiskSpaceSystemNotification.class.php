<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Disk space system notification.
 *
 * @package angie.environment
 * @subpackage models
 */
class DiskSpaceSystemNotification extends SystemNotification
{
    /**
     * Return notification title.
     *
     * @return mixed
     */
    public function getTitle()
    {
        return '';
    }

    /**
     * Return notification body.
     *
     * @return mixed
     */
    public function getBody()
    {
        $max_disk_space = AngieApplication::accountConfigReader()->getMaxDiskSpace();

        return lang(
            'Storage limit reached (:space).', [
            'space' => !empty($max_disk_space)
                ? format_file_size($max_disk_space)
                : format_file_size(0),
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
        return AngieApplication::accountSettings()->getPricingModel()->isLegacy()
            ? lang('Upgrade plan')
            : lang('Upgrade Storage');
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
