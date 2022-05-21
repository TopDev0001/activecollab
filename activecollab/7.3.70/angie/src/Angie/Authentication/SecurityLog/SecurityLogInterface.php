<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\SecurityLog;

use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;

/**
 * Security logs.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
interface SecurityLogInterface
{
    const LOGIN_ATTEMPT = 'login_attempt';
    const LOGIN = 'login';
    const LOGOUT = 'logout';

    /**
     * @return string
     */
    public function getIpAddress();

    /**
     * @param  string                     $ip_address
     * @return SecurityLogInterface|$this
     */
    public function &setIpAddress($ip_address);

    /**
     * @return string
     */
    public function getUserAgent();

    /**
     * @param  string                     $user_agent
     * @return SecurityLogInterface|$this
     */
    public function &setUserAgent($user_agent);

    /**
     * Record failed login attempt.
     *
     * $user is passed when we know the user instance that visitor is trying to log in as.
     *
     * @return SecurityLogInterface|$this
     */
    public function &recordLoginAttempt(AuthenticatedUserInterface $user = null);

    /**
     * @return SecurityLogInterface|$this
     */
    public function &recordLogin(AuthenticatedUserInterface $user);

    /**
     * @return SecurityLogInterface|$this
     */
    public function &recordLogout(AuthenticatedUserInterface $user);

    /**
     * Return number of login attempts for a particular IP address, in the given timeframe (number of seconds).
     *
     * @param  string $ip_address
     * @param  int    $in_timeframe
     * @return mixed
     */
    public function countLoginAttempts($ip_address, $in_timeframe);

    /**
     * Clean up records older than 1 year.
     *
     * @return $this
     */
    public function &cleanUp();
}
