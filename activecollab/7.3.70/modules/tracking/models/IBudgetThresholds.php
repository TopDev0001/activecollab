<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Indicator that this object can have budget thresholds.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
interface IBudgetThresholds
{
    /**
     * Return parent object ID.
     *
     * @return int
     */
    public function getId();
}
