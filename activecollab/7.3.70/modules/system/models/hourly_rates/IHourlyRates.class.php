<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Hourly rates interface.
 *
 * @package activeCollab.modules.system
 * @subpackage models
 */
interface IHourlyRates
{
    /**
     * Return hourly rates.
     *
     * @return array
     */
    public function getHourlyRates();

    /**
     * Set custom hourly rates.
     *
     * @param array $hourly_rates
     */
    public function setHourlyRates($hourly_rates);
}
