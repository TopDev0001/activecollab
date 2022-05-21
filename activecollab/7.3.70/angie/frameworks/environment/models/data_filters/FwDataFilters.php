<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Framework level data filters manager.
 *
 * @package angie.frameworks.environment
 * @subpackage models
 */
abstract class FwDataFilters extends BaseDataFilters
{
    /**
     * Returns true if $user can create a new filter.
     *
     * @param  string            $type
     * @return bool
     * @throws InvalidParamError
     */
    public static function canAdd($type, User $user)
    {
        if (empty($type)) {
            throw new InvalidParamError('type', $type, '$type value is required');
        }

        return $user->canUseReports();
    }

    /**
     * Returns true if $user can manage assignment filters.
     *
     * @param  string            $type
     * @return bool
     * @throws InvalidParamError
     */
    public static function canManage($type, User $user)
    {
        if (empty($type)) {
            throw new InvalidParamError('type', $type, '$type value is required');
        }

        return $user->canUseReports();
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

        if (str_starts_with($collection_name, 'filters_for_')) {
            self::prepareDataFiltersFor($collection, $collection_name);
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }

        return $collection;
    }

    /**
     * Prepare collection for user.
     *
     * @param                            $collection_name
     * @throws ImpossibleCollectionError
     * @throws InvalidParamError
     */
    private static function prepareDataFiltersFor(ModelCollection &$collection, $collection_name)
    {
        $parts = explode('_', $collection_name);
        $user_id = array_pop($parts);

        $user = DataObjectPool::get('User', $user_id);

        if ($user instanceof User) {
            $collection->setConditions('((created_by_id = ? AND is_private = ?) OR is_private = ?)', $user->getId(), true, false);
        } else {
            throw new ImpossibleCollectionError("User #{$user_id} not found");
        }
    }

    // ---------------------------------------------------
    //  Finters
    // ---------------------------------------------------

    /**
     * Return saved data filters by given type.
     *
     * @param  string       $type
     * @return DataFilter[]
     */
    public static function findByType($type)
    {
        return DataFilters::find(['conditions' => ['type = ?', $type]]);
    }

    /**
     * Return filters of given type that $user can see.
     *
     * @param  string       $type
     * @return DataFilter[]
     */
    public static function findByUser($type, User $user)
    {
        return DataFilters::find(['conditions' => ['type = ? AND (is_private = ? OR (created_by_id = ? AND is_private = ?))', $type, false, $user->getId(), true]]);
    }

    /**
     * Return ID name map of filters that $user can see.
     *
     * @param  string $type
     * @return array
     */
    public static function getIdNameMap($type, User $user)
    {
        $result = [];

        if ($rows = DB::execute('SELECT id, name FROM data_filters WHERE type = ? AND (is_private = ? OR (created_by_id = ? AND is_private = ?)) ORDER BY name', $type, false, $user->getId(), true)) {
            foreach ($rows as $row) {
                $result[$row['id']] = $row['name'];
            }
        }

        return $result;
    }
}
