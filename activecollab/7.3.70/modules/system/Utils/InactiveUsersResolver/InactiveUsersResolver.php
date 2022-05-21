<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\InactiveUsersResolver;

use DateTimeValue;
use IUser;
use Users;
use UserSessions;

class InactiveUsersResolver implements InactiveUsersResolverInterface
{
    public function getInactiveUsersIds(DateTimeValue $current_time, int $inactive_for_seconds): array
    {
        $active_owners_and_members = Users::findActiveOwnersAndMembersIds();

        return array_unique(
            array_merge(
                $this->findActiveOwnersAndMembersWithoutSession($active_owners_and_members),
                $this->getOwnersAndMembersWithSessionLastActiveBefore(
                    $current_time,
                    $inactive_for_seconds,
                    $active_owners_and_members
                )
            )
        );
    }

    public function isUserInactive(
        IUser $user,
        int $inactive_for_seconds,
        ?DateTimeValue $current_time = null
    ): bool
    {
        if (!$current_time) {
            $current_time = new DateTimeValue();
        }

        $last_used_on = UserSessions::getLatestUsedOnFromSessionsForUser($user);

        if (!$last_used_on) {
            return true;
        }

        return $this->isLastUsedOnBeforeGivenTime(
            $current_time,
            new DateTimeValue($last_used_on),
            $inactive_for_seconds
        );
    }

    private function isLastUsedOnBeforeGivenTime(
        DateTimeValue $current_time,
        DateTimeValue $last_used_on,
        int $inactive_for_seconds
    ): bool
    {
        return $last_used_on->getTimestamp() <= $current_time->advance(-1 * $inactive_for_seconds)->getTimestamp();
    }

    private function findActiveOwnersAndMembersWithoutSession(array $active_owners_and_members): array
    {
        return array_diff(
            $active_owners_and_members,
            UserSessions::findUserIdsWithActiveSessions()
        );
    }

    private function getOwnersAndMembersWithSessionLastActiveBefore(
        DateTimeValue $current_time,
        int $inactive_for_seconds,
        array $active_owners_and_members
    ): array
    {
        $inactive_users_with_session = UserSessions::findInactiveUserIds(
            $current_time->advance(-1 * $inactive_for_seconds, false)
        );

        return array_intersect(
            $active_owners_and_members,
            $inactive_users_with_session
        );
    }
}
