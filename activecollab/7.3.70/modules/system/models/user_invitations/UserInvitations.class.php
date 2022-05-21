<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Application level user invitiations manager class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class UserInvitations extends BaseUserInvitations
{
    /**
     * @return UserInvitation|null
     */
    public static function findFor(User $user)
    {
        return self::findOne(
            [
                'conditions' => [
                    '`user_id` = ?',
                    $user->getId(),
                ],
            ]
        );
    }

    /**
     * @param  int                 $user_id
     * @param  string              $code
     * @return UserInvitation|null
     */
    public static function findByUserIdAndCode($user_id, $code)
    {
        if ($user_id && $code) {
            return self::findOne(
                [
                    'conditions' => [
                        '`user_id` = ? AND `code` = ?',
                        $user_id,
                        $code,
                    ],
                ]
            );
        }

        return null;
    }

    /**
     * @var array
     */
    private static $user_invitation_map = [];

    /**
     * @param int[] $user_ids
     */
    public static function preloadUserInvitationMap($user_ids)
    {
        self::$user_invitation_map = !empty($user_ids)
            ? array_fill_keys($user_ids, 0)
            : [];

        if ($rows = DB::execute('SELECT MAX(id) AS "invitation_id", user_id FROM user_invitations WHERE user_id IN (?) GROUP BY user_id', $user_ids)) {
            foreach ($rows as $row) {
                self::$user_invitation_map[$row['user_id']] = $row['invitation_id'];
            }
        }
    }

    public static function getInvitationIdForUser(User $user)
    {
        if (isset(self::$user_invitation_map[$user->getId()])) {
            return self::$user_invitation_map[$user->getId()];
        } else {
            return AngieApplication::cache()->getByObject($user, 'invitation_id', function () use ($user) {
                return DB::executeFirstRow('SELECT id FROM user_invitations WHERE user_id = ?', $user->getId());
            });
        }
    }

    /**
     * Reset manager state (between tests for example).
     */
    public static function resetState()
    {
        self::$user_invitation_map = [];
    }

    public static function deleteByUser(User $user): void
    {
        $invitation_ids = DB::executeFirstColumn(
            'SELECT `id` FROM `user_invitations` WHERE `user_id` = ?',
            $user->getId()
        );

        if ($invitation_ids) {
            DB::execute('DELETE FROM user_invitations WHERE id IN (?)', $invitation_ids);
            self::clearCacheFor($invitation_ids);
        }
    }

    /**
     * Clean up old user invitations.
     */
    public static function cleanUp()
    {
        DB::execute('DELETE FROM user_invitations WHERE created_on < ?', DateValue::makeFromString('-30 days'));
    }
}
