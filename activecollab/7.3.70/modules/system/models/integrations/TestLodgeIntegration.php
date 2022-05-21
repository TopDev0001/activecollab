<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * TestLodge integration.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class TestLodgeIntegration extends ThirdPartyIntegration
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'TestLodge';
    }

    /**
     * Return short integration description.
     *
     * @return string
     */
    public function getDescription()
    {
        return lang('Online test case management tool');
    }

    /**
     * Return name that of the organization that is producting this client.
     *
     * @return string
     */
    protected function getClientVendor()
    {
        return 'TestLodge';
    }

    /**
     * Return name that third party uses to idenfity this client.
     *
     * @return string
     */
    protected function getClientName()
    {
        return 'testlodge.com';
    }
}
