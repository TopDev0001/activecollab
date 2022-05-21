<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Events\User\SessionStartedEvent;

/**
 * UserSessions class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class UserSessions extends BaseUserSessions
{
    public static function prepareCollection(string $collection_name, $user)
    {
        if (str_starts_with($collection_name, 'user_sessions_for')) {
            $bits = explode('_', $collection_name);
            $subscriber = DataObjectPool::get(User::class, array_pop($bits));

            if ($subscriber instanceof User && $subscriber->isActive()) {
                $collection = parent::prepareCollection($collection_name, $user);
                $collection->setConditions('user_id = ?', $subscriber->getId());

                return $collection;
            } else {
                throw new ImpossibleCollectionError('User not found or not active');
            }
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }
    }

    /**
     * Return number of sessions that user has open.
     *
     * @return int
     */
    public static function countByUser(User $user)
    {
        return self::count(['user_id = ?', $user->getId()]);
    }

    /**
     * Get log-in subscription for the given user.
     *
     * @param  int|null               $session_ttl
     * @return UserSession|DataObject
     */
    public static function startSession(User $user, $session_ttl = null)
    {
        $last_login_on = $user->getLastLoginOn();

        $session = self::create([
            'user_id' => $user->getId(),
            'session_ttl' => (int) $session_ttl,
        ]);

        AngieApplication::eventsDispatcher()->trigger(new SessionStartedEvent(
            $user,
            $last_login_on
        ));

        return $session;
    }

    public static function terminateUserSessions(User $user): void
    {
        self::delete(
            [
                'user_id = ?', $user->getId(),
            ]
        );
    }

    /**
     * Generate a new session ID.
     *
     * @return string
     */
    public static function generateSessionId()
    {
        return self::generateRandomValueForField('session_id', 40);
    }

    /**
     * Generate CSRF validator.
     *
     * @return string
     */
    public static function generateCsrfValidator()
    {
        return self::generateRandomValueForField('csrf_validator', 40);
    }

    /**
     * Generate random value for the given field.
     *
     * @param  string $field
     * @param  int    $value_length
     * @return string
     */
    private static function generateRandomValueForField($field, $value_length)
    {
        do {
            $result = make_string($value_length);
        } while (DB::executeFirstCell("SELECT COUNT(id) FROM user_sessions WHERE {$field} = ?", $result) > 0);

        return $result;
    }

    /**
     * Drop expired subscriptions.
     */
    public static function deleteExpired()
    {
        if ($expired_session_ids = DB::executeFirstColumn("SELECT id, last_used_on + INTERVAL session_ttl SECOND AS 'session_expires_on' FROM user_sessions HAVING session_expires_on < ?", DateTimeValue::now())) {
            DB::execute('DELETE FROM user_sessions WHERE id IN (?)', $expired_session_ids);
        }
    }

    public static function findUserIdsWithActiveSessions(): array
    {
        return (array) DB::executeFirstColumn('SELECT DISTINCT user_id FROM user_sessions');
    }

    public static function findInactiveUserIds(DateTimeValue $last_used_before): array
    {
        return (array) DB::executeFirstColumn(
            'SELECT user_id
            FROM (SELECT MAX(last_used_on) AS last_used, user_id FROM user_sessions GROUP BY user_id) AS last_usage_by_users
            WHERE last_usage_by_users.last_used <= ?',
            $last_used_before
        );
    }

    public static function getLatestUsedOnFromSessionsForUser(IUser $user)
    {
        return DB::executeFirstCell(
            'SELECT MAX(last_used_on) FROM user_sessions WHERE user_id = ?',
            $user->getId()
        );
    }
}
