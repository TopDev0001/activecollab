<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\BruteForceProtector;

use Angie\Authentication\SecurityLog\SecurityLogInterface;
use LogicException;

/**
 * @package Angie\Authentication\BruteForceProtector
 */
class BruteForceProtector implements BruteForceProtectorInterface
{
    /**
     * @var SecurityLogInterface
     */
    private $security_log;

    /**
     * @var bool
     */
    private $is_enabled;

    /**
     * @var int
     */
    private $max_attempts;

    /**
     * @var int
     */
    private $in_timeframe;

    /**
     * BruteForceProtector constructor.
     *
     * @param bool $is_enabled
     * @param int  $max_attempts
     * @param int  $in_timeframe
     */
    public function __construct(SecurityLogInterface $security_log, $is_enabled = true, $max_attempts = 5, $in_timeframe = 3600)
    {
        if ($max_attempts < 1) {
            throw new LogicException('Max attempts should be larger than zero.');
        }

        if ($in_timeframe < 1) {
            throw new LogicException('Timestamp should be at least one second.');
        }

        $this->security_log = $security_log;
        $this->is_enabled = $is_enabled;
        $this->max_attempts = $max_attempts;
        $this->in_timeframe = $in_timeframe;
    }

    public function shouldBlock($ip_address)
    {
        if (!$this->is_enabled) {
            return false;
        }

        return $this->security_log->countLoginAttempts($ip_address, $this->in_timeframe) >= $this->max_attempts;
    }

    public function getIsEnabled()
    {
        return $this->is_enabled;
    }

    public function getMaxAttempts()
    {
        return $this->max_attempts;
    }

    public function getInTimeframe()
    {
        return $this->in_timeframe;
    }
}
