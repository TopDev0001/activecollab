<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\SecurityLog\EventHandlers;

use Angie\Authentication\SecurityLog\SecurityLogInterface;

/**
 * @package Angie\Authentication\SecurityLog\EventHandlers
 */
abstract class EventHander
{
    /**
     * @var SecurityLogInterface
     */
    private $security_log;

    public function __construct(SecurityLogInterface $security_log)
    {
        $this->security_log = $security_log;
    }

    /**
     * @return SecurityLogInterface
     */
    protected function getSecurityLog()
    {
        return $this->security_log;
    }
}
