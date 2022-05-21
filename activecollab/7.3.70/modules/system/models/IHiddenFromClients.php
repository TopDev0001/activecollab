<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Interface that's implemented by classes that can be used as hidden from clients.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
interface IHiddenFromClients
{
    /**
     * Return is hidden from clients.
     *
     * @return bool
     */
    public function getIsHiddenFromClients();
}
