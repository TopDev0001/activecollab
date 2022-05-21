<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\TeamEvents\TeamUpdatedEvent;

/**
 * Teams manager class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class Teams extends BaseTeams
{
    /**
     * Return new collection.
     *
     * @param  User|null                 $user
     * @return ModelCollection
     * @throws ImpossibleCollectionError
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        if (str_starts_with($collection_name, 'open_assignments_for_team')) {
            $bits = explode('_', $collection_name);
            $team_id = array_pop($bits);

            $team = DataObjectPool::get('Team', $team_id);

            if ($team instanceof Team && $team->countMembers()) {
                $collection = new OpenAssignmentsForTeamCollection($collection_name);
                $collection->setWhosAsking($user)->setTeam($team);
            } else {
                throw new ImpossibleCollectionError("Team #{$team_id} not found, or team is empty");
            }
        } else {
            $collection = parent::prepareCollection($collection_name, $user);

            $collection->setPreExecuteCallback(function ($ids) {
                Users::preloadMemberIdsFromConnectionTable('Team', $ids, 'team_users', 'team_id');
            });
        }

        return $collection;
    }

    public static function canAdd(User $user): bool
    {
        return $user->isPowerUser();
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): Team
    {
        $team = parent::create($attributes, $save, $announce); // @TODO Announcement should be sent after team members are added

        if ($team instanceof Team && $team->isLoaded()) {
            $team->tryToSetMembersFrom($attributes);
        }

        return $team;
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): Team
    {
        $team = parent::update($instance, $attributes, $save);

        if ($team instanceof Team && $team->isLoaded()) {
            $team->tryToSetMembersFrom($attributes);
        }

        DataObjectPool::announce(new TeamUpdatedEvent($team));

        return $team;
    }

    /**
     * Revoke user from all teams where it is a member.
     *
     * @throws InsufficientPermissionsError
     */
    public static function revokeMember(User $user, User $by)
    {
        if (!$user->canChangeRole($by, false)) {
            throw new InsufficientPermissionsError();
        }

        self::revokeMemberWithoutCheck($user);
    }

    /**
     * @throws InvalidParamError
     */
    public static function revokeMemberWithoutCheck(User $user): void
    {
        /** @var Team[] $teams */
        if ($teams = self::findBySQL('SELECT t.* FROM teams AS t LEFT JOIN team_users AS u ON t.id = u.team_id WHERE u.user_id = ?', $user->getId())) {
            foreach ($teams as $team) {
                $team->removeMembers([$user]);
                DataObjectPool::announce(new TeamUpdatedEvent($team));
            }
        }
    }
}
