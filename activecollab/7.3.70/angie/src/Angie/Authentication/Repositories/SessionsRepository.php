<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\Repositories;

use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;
use ActiveCollab\Authentication\Session\RepositoryInterface;
use ActiveCollab\Authentication\Session\SessionInterface;
use Angie\Authentication\Exception\AuthenticationException;
use AngieApplication;
use DateTimeInterface;
use DB;
use InvalidArgumentException;
use User;
use UserSession;
use UserSessions;

/**
 * @package Angie\Authentication\Repositories
 */
class SessionsRepository implements RepositoryInterface
{
    public function getById($session_id)
    {
        return UserSessions::findOneBy('session_id', $session_id);
    }

    public function getUsageById($session_id)
    {
        return (int) DB::executeFirstCell('SELECT requests_count FROM user_sessions WHERE session_id = ?', $session_id);
    }

    public function getUsageBySession(SessionInterface $session)
    {
        if ($session instanceof UserSession) {
            return $session->getRequestsCount();
        }

        throw new InvalidArgumentException('Invalid session instance');
    }

    public function recordUsageById($session_id)
    {
        if ($id = DB::executeFirstCell('SELECT id FROM user_sessions WHERE session_id = ?', $session_id)) {
            DB::executeFirstCell('UPDATE user_sessions SET requests_count = requests_count + 1 WHERE id = ?', $id);
            AngieApplication::cache()->removeByObject([UserSession::class, $id]);
        }
    }

    public function recordUsageBySession(SessionInterface $session)
    {
        if ($session instanceof UserSession) {
            $session->setRequestsCount($session->getRequestsCount() + 1);
            $session->save();
        } else {
            throw new InvalidArgumentException('Invalid session instance');
        }
    }

    public function createSession(AuthenticatedUserInterface $user, array $credentials = [], DateTimeInterface $expires_at = null)
    {
        if ($user instanceof User) {
            if (!$user->isActive()) {
                throw new AuthenticationException(AuthenticationException::USER_NOT_ACTIVE);
            }

            $session_ttl = isset($credentials['remember']) ? 1209600 : 3600; // Two weeks, or one hour.

            return UserSessions::startSession($user, $session_ttl);
        } else {
            throw new InvalidArgumentException('Invalid user instance');
        }
    }

    public function terminateSession(SessionInterface $session)
    {
        if ($session instanceof UserSession) {
            $session->delete();
        } else {
            throw new InvalidArgumentException('Invalid session instance');
        }
    }
}
