<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Hubstaff integration.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class HubstaffIntegration extends ThirdPartyIntegration
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'Hubstaff';
    }

    /**
     * Return short integration description.
     *
     * @return string
     */
    public function getDescription()
    {
        return lang('Time tracking and team monitoring');
    }

    /**
     * Return name that of the organization that is producting this client.
     *
     * @return string
     */
    protected function getClientVendor()
    {
        return 'Hubstaff';
    }

    /**
     * Return name that third party uses to idenfity this client.
     *
     * @return string
     */
    protected function getClientName()
    {
        return 'Hubstaff app';
    }
}
