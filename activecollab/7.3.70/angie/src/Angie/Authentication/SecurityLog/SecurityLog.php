<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\SecurityLog;

use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;
use DateTimeValue;
use DB;

/**
 * Security logs.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class SecurityLog implements SecurityLogInterface
{
    /**
     * @var string
     */
    private $ip_address = '';

    /**
     * @var string
     */
    private $user_agent = '';

    /**
     * SecurityLogs constructor.
     *
     * @param string $ip_address
     * @param string $user_agent
     */
    public function __construct($ip_address = '', $user_agent = '')
    {
        $this->ip_address = $ip_address;
        $this->user_agent = $user_agent;
    }

    public function getIpAddress()
    {
        return $this->ip_address;
    }

    public function &setIpAddress($ip_address)
    {
        $this->ip_address = (string) $ip_address;

        return $this;
    }

    public function getUserAgent()
    {
        return $this->user_agent;
    }

    public function &setUserAgent($user_agent)
    {
        $this->user_agent = (string) $user_agent;

        return $this;
    }

    public function &recordLoginAttempt(AuthenticatedUserInterface $user = null)
    {
        $record = [
            'event' => self::LOGIN_ATTEMPT,
            'user_id' => 0,
            'user_ip' => $this->getIpAddress(),
            'user_agent' => $this->getUserAgent(),
            'created_on' => DateTimeValue::now(),
        ];

        if ($user) {
            $record['user_id'] = $user->getId();
            $record['user_name'] = $user->getFullName();
            $record['user_email'] = $user->getEmail();
        }

        DB::insertRecord('security_logs', $record);

        return $this;
    }

    public function &recordLogin(AuthenticatedUserInterface $user)
    {
        DB::insertRecord('security_logs', [
            'event' => self::LOGIN,
            'user_id' => $user->getId(),
            'user_name' => $user->getFullName(),
            'user_email' => $user->getEmail(),
            'user_ip' => $this->getIpAddress(),
            'user_agent' => $this->getUserAgent(),
            'created_on' => DateTimeValue::now(),
        ]);

        return $this;
    }

    public function &recordLogout(AuthenticatedUserInterface $user)
    {
        DB::insertRecord('security_logs', [
            'event' => self::LOGOUT,
            'user_id' => $user->getId(),
            'user_name' => $user->getFullName(),
            'user_email' => $user->getEmail(),
            'user_ip' => $this->getIpAddress(),
            'user_agent' => $this->getUserAgent(),
            'created_on' => DateTimeValue::now(),
        ]);

        return $this;
    }

    public function countLoginAttempts($ip_address, $in_timeframe)
    {
        $created_since = DateTimeValue::now()->advance(-1 * $in_timeframe);

        return DB::executeFirstCell('SELECT COUNT(id) AS "row_count" FROM security_logs WHERE event = ? AND user_ip = ? AND created_on >= ?', self::LOGIN_ATTEMPT, $ip_address, $created_since);
    }

    public function &cleanUp()
    {
        DB::execute('DELETE FROM security_logs WHERE created_on < ?', DateTimeValue::makeFromString('-1 year'));

        return $this;
    }
}
