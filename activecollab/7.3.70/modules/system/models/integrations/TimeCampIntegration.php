<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * TimeCamp integration.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class TimeCampIntegration extends ThirdPartyIntegration
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'TimeCamp';
    }

    /**
     * Return short integration description.
     *
     * @return string
     */
    public function getDescription()
    {
        return lang('Automatic time tracking for your projects');
    }

    /**
     * Return name that of the organization that is producting this client.
     *
     * @return string
     */
    protected function getClientVendor()
    {
        return 'TimeCamp';
    }

    /**
     * Return name that third party uses to idenfity this client.
     *
     * @return string
     */
    protected function getClientName()
    {
        return 'TimeCamp app';
    }
}
