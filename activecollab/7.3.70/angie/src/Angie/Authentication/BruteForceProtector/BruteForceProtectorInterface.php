<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\BruteForceProtector;

/**
 * @package Angie\Authentication\BruteForceProtector
 */
interface BruteForceProtectorInterface
{
    /**
     * Return true if $ip_address should be blocked from login attempts.
     *
     * @param  string $ip_address
     * @return bool
     */
    public function shouldBlock($ip_address);

    /**
     * Return true if protector is enabled.
     *
     * @return bool
     */
    public function getIsEnabled();

    /**
     * Return maximum number of attempts allowed.
     *
     * @return int
     */
    public function getMaxAttempts();

    /**
     * Return timeframe in which number of attempts is allowed (cooldown period).
     *
     * @return int
     */
    public function getInTimeframe();
}
