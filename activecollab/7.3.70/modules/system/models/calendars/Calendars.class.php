<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class Calendars extends FwCalendars
{
    public static function canAdd(User $user): bool
    {
        if ($user instanceof Client) {
            return false;
        }

        return parent::canAdd($user);
    }

    /**
     * Return calendar ID-s by conditions.
     *
     * @param  null  $additional_conditions
     * @return int[]
     */
    public static function findIdsByUser(IUser $user, $additional_conditions = null)
    {
        $conditions = [DB::prepare('calendar_users.calendar_id = calendars.id AND calendar_users.user_id = ?', $user->getId())];

        if ($additional_conditions) {
            $conditions[] = "($additional_conditions)";
        }

        return DB::executeFirstColumn('SELECT DISTINCT calendars.id FROM calendars, calendar_users WHERE ' . implode(' AND ', $conditions));
    }

    /**
     * Return new collection.
     *
     * @param  User|null         $user
     * @return ModelCollection
     * @throws InvalidParamError
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if (str_starts_with($collection_name, 'calendars_by_user')) {
            self::prepareCalendarsByUser($collection, $collection_name);
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }

        return $collection;
    }

    /**
     * Return calendars filtered by user.
     *
     * @param                            $collection_name
     * @throws ImpossibleCollectionError
     */
    private static function prepareCalendarsByUser(ModelCollection &$collection, $collection_name)
    {
        $parts = explode('_', $collection_name);
        $user_id = array_pop($parts);

        $user = DataObjectPool::get('User', $user_id);

        if ($user instanceof User) {
            $collection->setJoinTable('calendar_users');
            $collection->setConditions('calendars.is_trashed = ? AND calendar_users.calendar_id = calendars.id AND calendar_users.user_id = ?', false, $user->getId());
        } else {
            throw new ImpossibleCollectionError("User #{$user_id} not found");
        }
    }
}
